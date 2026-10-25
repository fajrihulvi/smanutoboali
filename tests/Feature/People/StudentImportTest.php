<?php

use App\Modules\Core\Enums\Role;
use App\Modules\People\Enums\ImportStatus;
use App\Modules\People\Exports\StudentImportTemplate;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\StudentImport;
use App\Modules\People\Services\StudentImportRowProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Excel;

const IMPOR_BASE = '/operator/master-data/siswa/impor';

/**
 * @param  list<array<string, string>>  $rows
 */
function importCsv(array $rows): UploadedFile
{
    $lines = [implode(',', StudentImportRowProcessor::COLUMNS)];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(
            fn (string $column) => '"'.str_replace('"', '""', $row[$column] ?? '').'"',
            StudentImportRowProcessor::COLUMNS,
        ));
    }

    return UploadedFile::fake()->createWithContent('siswa.csv', implode("\n", $lines)."\n");
}

beforeEach(function () {
    Storage::fake('private');
});

it('operator membuka halaman impor dan mengunduh template', function () {
    $this->actingAsRole(Role::Operator);

    $this->get(IMPOR_BASE)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('People::Operator/Students/Import')
        ->has('imports.data', 0));

    $this->get(IMPOR_BASE.'/template')
        ->assertOk()
        ->assertDownload('template-import-siswa.xlsx');
});

it('mengimpor siswa + orang tua dan melaporkan baris gagal', function () {
    Student::factory()->create(['nis' => '99999999']);
    $this->actingAsRole(Role::Operator);

    $file = importCsv([
        // Baris 2: valid, tanggal format DD/MM/YYYY, telepon tanpa 0 di depan.
        ['nis' => '20260001', 'nama' => 'Aisyah Putri', 'jenis_kelamin' => 'Perempuan', 'tanggal_lahir' => '17/05/2013',
            'agama' => 'islam', 'ayah_nama' => 'Budi Santoso', 'ayah_telepon' => '81234567890', 'ayah_email' => 'Budi@Contoh.id',
            'ibu_nama' => 'Siti Aminah', 'ibu_telepon' => '081298765432'],
        // Baris 3: adik — orang tua dipakai ulang (email ayah sama, nama+telepon ibu sama).
        ['nis' => '20260002', 'nama' => 'Rafi Pratama', 'jenis_kelamin' => 'L',
            'ayah_nama' => 'Budi Santoso', 'ayah_email' => 'budi@contoh.id',
            'ibu_nama' => 'Siti Aminah', 'ibu_telepon' => '081298765432'],
        // Baris 4: NIS sudah ada.
        ['nis' => '99999999', 'nama' => 'Duplikat', 'jenis_kelamin' => 'L'],
        // Baris 5: jenis kelamin & tanggal salah.
        ['nis' => '20260003', 'nama' => 'Salah Data', 'jenis_kelamin' => 'X', 'tanggal_lahir' => '31-31-2013'],
        // Baris 6: kosong, dilewati.
        [],
    ]);

    $this->post(IMPOR_BASE, ['file' => $file])->assertSessionHasNoErrors()->assertSessionHas('success');

    $import = StudentImport::query()->firstOrFail();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->total_rows)->toBe(4)
        ->and($import->success_rows)->toBe(2)
        ->and($import->failed_rows)->toBe(2)
        ->and(collect($import->errors)->pluck('row')->all())->toBe([4, 5])
        ->and($import->errors[0]['messages'][0])->toContain('NIS 99999999 sudah terdaftar')
        ->and(implode(' ', $import->errors[1]['messages']))->toContain('Jenis kelamin harus L atau P');

    $aisyah = Student::query()->where('nis', '20260001')->firstOrFail();
    $rafi = Student::query()->where('nis', '20260002')->firstOrFail();

    expect($aisyah->birth_date?->toDateString())->toBe('2013-05-17')
        ->and($aisyah->gender->value)->toBe('P')
        ->and($aisyah->religion?->value)->toBe('ISLAM')
        ->and(Guardian::query()->count())->toBe(2)
        ->and(Guardian::query()->where('name', 'Budi Santoso')->value('phone'))->toBe('081234567890')
        ->and($rafi->guardians()->pluck('name')->sort()->values()->all())->toBe(['Budi Santoso', 'Siti Aminah'])
        ->and($aisyah->guardians()->wherePivot('is_primary', true)->value('guardians.name'))->toBe('Budi Santoso');
});

it('menolak file dengan isi palsu walau berekstensi .csv', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(IMPOR_BASE, ['file' => UploadedFile::fake()->createWithContent('siswa.csv', '<?php system($_GET["c"]); ?>')])
        ->assertSessionHasErrors('file');

    expect(StudentImport::query()->count())->toBe(0);
});

it('menolak ekstensi selain xlsx/csv', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(IMPOR_BASE, ['file' => UploadedFile::fake()->create('siswa.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('file');
});

it('role lain tidak dapat mengimpor', function (Role $role) {
    $this->actingAsRole($role);

    $this->get(IMPOR_BASE)->assertForbidden();
    $this->post(IMPOR_BASE, ['file' => importCsv([])])->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);

it('mengimpor file .xlsx dari template yang diunduh', function () {
    $this->actingAsRole(Role::Operator);

    // Buat file .xlsx sungguhan dari template aplikasi (1 baris contoh).
    $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
    file_put_contents($path, Maatwebsite\Excel\Facades\Excel::raw(new StudentImportTemplate, Excel::XLSX));
    $file = new UploadedFile($path, 'template-import-siswa.xlsx', null, null, true);

    $this->post(IMPOR_BASE, ['file' => $file])->assertSessionHasNoErrors();

    $import = StudentImport::query()->firstOrFail();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->success_rows)->toBe(1)
        ->and(Student::query()->where('nis', '20260001')->value('nisn'))->toBe('0112233445');
});

describe('proses import tanpa menunggu worker antrean', function () {
    it('selesai pada request unggah itu juga (setelah respons), antrean cadangan tidak mengimpor ulang', function () {
        config(['queue.default' => 'database']);
        $this->actingAsRole(Role::Operator);

        $this->post(IMPOR_BASE, ['file' => importCsv([
            ['nis' => '20260101', 'nama' => 'Tanpa Cron', 'jenis_kelamin' => 'L'],
        ])])->assertSessionHasNoErrors();

        $import = StudentImport::query()->sole();
        expect($import->status)->toBe(ImportStatus::Completed)
            ->and($import->success_rows)->toBe(1)
            ->and(DB::table('jobs')->count())->toBe(1);

        // Worker cron mengambil job cadangan: import sudah diklaim, tidak ada yang diproses ulang.
        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true])->assertSuccessful();

        expect(DB::table('jobs')->count())->toBe(0)
            ->and($import->fresh()->success_rows)->toBe(1)
            ->and(Student::query()->where('nis', '20260101')->count())->toBe(1);
    });

    it('hanya satu proses yang bisa mengklaim import', function () {
        $import = StudentImport::query()->create(['original_name' => 'a.csv', 'status' => ImportStatus::Pending]);

        expect($import->claimForProcessing())->toBeTrue()
            ->and($import->status)->toBe(ImportStatus::Processing)
            ->and(StudentImport::query()->findOrFail($import->id)->claimForProcessing())->toBeFalse();
    });

    it('import yang terhenti lebih dari 15 menit ditandai gagal saat halaman dibuka', function () {
        $stuck = StudentImport::query()->create(['original_name' => 'besar.xlsx', 'status' => ImportStatus::Processing]);
        $stuck->forceFill(['updated_at' => now()->subMinutes(20)])->saveQuietly();
        $running = StudentImport::query()->create(['original_name' => 'jalan.xlsx', 'status' => ImportStatus::Processing]);
        $this->actingAsRole(Role::Operator);

        $this->get(IMPOR_BASE)->assertOk();

        expect($stuck->fresh()->status)->toBe(ImportStatus::Failed)
            ->and($stuck->fresh()->failure_message)->toContain('terhenti')
            ->and($running->fresh()->status)->toBe(ImportStatus::Processing);
    });
});
