<?php

use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Exports\SubjectImportTemplate;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectImport;
use App\Modules\Academic\Services\SubjectImportRowProcessor;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Enums\ImportStatus;
use App\Support\Excel\SafeValueBinder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Activitylog\Models\Activity;

const MAPEL_IMPOR_BASE = '/operator/master-data/mapel/impor';

/**
 * @param  list<array<string, string>>  $rows
 */
function subjectImportCsv(array $rows): UploadedFile
{
    $lines = [implode(',', SubjectImportRowProcessor::COLUMNS)];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(
            fn (string $column) => '"'.str_replace('"', '""', $row[$column] ?? '').'"',
            SubjectImportRowProcessor::COLUMNS,
        ));
    }

    return UploadedFile::fake()->createWithContent('mapel.csv', implode("\n", $lines)."\n");
}

beforeEach(function () {
    Storage::fake('private');
});

it('operator membuka halaman impor mapel dan mengunduh template', function () {
    $this->actingAsRole(Role::Operator);

    $this->get(MAPEL_IMPOR_BASE)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Academic::Operator/Subjects/Import')
        ->has('imports.data', 0));

    $response = $this->get(MAPEL_IMPOR_BASE.'/template')
        ->assertOk()
        ->assertDownload('template-import-mapel.xlsx');

    $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

    expect($sheet->rangeToArray('A1:D1')[0])->toBe(SubjectImportRowProcessor::COLUMNS)
        ->and($sheet->getCell('A2')->getValue())->toBe('MTK');
});

it('mengimpor mapel dan melaporkan baris gagal', function () {
    Subject::factory()->create(['code' => 'BIN', 'name' => 'Bahasa Indonesia']);
    $this->actingAsRole(Role::Operator);

    $file = subjectImportCsv([
        // Baris 2: valid, kode huruf kecil, kelompok pakai label.
        ['kode' => 'mtk', 'nama' => 'Matematika', 'kelompok' => 'Umum', 'aktif' => 'Ya'],
        // Baris 3: valid, kelompok pakai kode, nonaktif.
        ['kode' => 'MULOK-1', 'nama' => 'Bahasa Daerah', 'kelompok' => 'muatan lokal', 'aktif' => 'Tidak'],
        // Baris 4: valid, kolom aktif kosong = aktif.
        ['kode' => 'TFZ', 'nama' => "Tahfiz Al-Qur'an", 'kelompok' => 'KEPESANTRENAN'],
        // Baris 5: kode sudah terdaftar.
        ['kode' => 'BIN', 'nama' => 'Duplikat', 'kelompok' => 'Umum'],
        // Baris 6: data salah.
        ['kode' => 'A B', 'nama' => '', 'kelompok' => 'Ekstra', 'aktif' => 'mungkin'],
        // Baris 7: kode ganda di file yang sama (baris 2 sudah memakainya).
        ['kode' => 'MTK', 'nama' => 'Matematika Lanjut', 'kelompok' => 'Pilihan'],
        // Baris 8: kosong, dilewati.
        [],
    ]);

    $this->post(MAPEL_IMPOR_BASE, ['file' => $file])->assertSessionHasNoErrors()->assertSessionHas('success');

    $import = SubjectImport::query()->firstOrFail();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->total_rows)->toBe(6)
        ->and($import->success_rows)->toBe(3)
        ->and($import->failed_rows)->toBe(3)
        ->and(collect($import->errors)->pluck('row')->all())->toBe([5, 6, 7])
        ->and($import->errors[0]['messages'][0])->toContain('Kode BIN sudah terdaftar')
        ->and($import->errors[2]['messages'][0])->toContain('Kode MTK sudah terdaftar');

    $invalid = implode(' ', $import->errors[1]['messages']);
    expect($invalid)->toContain('Kode hanya boleh berisi huruf')
        ->and($invalid)->toContain('Nama mata pelajaran wajib diisi')
        ->and($invalid)->toContain('Kelompok "Ekstra" tidak dikenali')
        ->and($invalid)->toContain('Kolom aktif "mungkin" tidak dikenali');

    $mtk = Subject::query()->where('code', 'MTK')->sole();
    $mulok = Subject::query()->where('code', 'MULOK-1')->sole();
    expect($mtk->name)->toBe('Matematika')
        ->and($mtk->group)->toBe(SubjectGroup::General)
        ->and($mtk->is_active)->toBeTrue()
        ->and($mulok->group)->toBe(SubjectGroup::LocalContent)
        ->and($mulok->is_active)->toBeFalse()
        ->and(Subject::query()->where('code', 'TFZ')->sole()->group)->toBe(SubjectGroup::Pesantren)
        ->and(Subject::query()->where('code', 'BIN')->value('name'))->toBe('Bahasa Indonesia');

    // Satu entri audit untuk file, bukan satu per mapel.
    expect(Activity::query()->where('description', 'like', 'Menambah mata pelajaran%')->count())->toBe(0)
        ->and(Activity::query()->where('description', 'like', 'Import mata pelajaran mapel.csv%')->count())->toBe(1);
});

it('mengimpor file .xlsx dari template mapel yang diunduh', function () {
    $this->actingAsRole(Role::Operator);

    $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
    file_put_contents($path, Maatwebsite\Excel\Facades\Excel::raw(new SubjectImportTemplate, Excel::XLSX));
    SafeValueBinder::reset();
    $file = new UploadedFile($path, 'template-import-mapel.xlsx', null, null, true);

    $this->post(MAPEL_IMPOR_BASE, ['file' => $file])->assertSessionHasNoErrors();

    $import = SubjectImport::query()->firstOrFail();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->success_rows)->toBe(2)
        ->and(Subject::query()->where('code', 'TFZ')->value('group'))->toBe(SubjectGroup::Pesantren);
});

it('kode mapel berupa angka dari Excel tetap terbaca utuh', function () {
    expect(app(SubjectImportRowProcessor::class)->process(['kode' => 101.0, 'nama' => 'Mapel Angka', 'kelompok' => 'Umum']))->toBe([])
        ->and(Subject::query()->where('code', '101')->exists())->toBeTrue();
});

it('menolak file mapel dengan isi palsu atau ekstensi lain', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(MAPEL_IMPOR_BASE, ['file' => UploadedFile::fake()->createWithContent('mapel.csv', '<?php system($_GET["c"]); ?>')])
        ->assertSessionHasErrors('file');
    $this->post(MAPEL_IMPOR_BASE, ['file' => UploadedFile::fake()->create('mapel.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('file');

    expect(SubjectImport::query()->count())->toBe(0);
});

it('role lain tidak dapat mengimpor mapel', function (Role $role) {
    $this->actingAsRole($role);

    $this->get(MAPEL_IMPOR_BASE)->assertForbidden();
    $this->get(MAPEL_IMPOR_BASE.'/template')->assertForbidden();
    $this->post(MAPEL_IMPOR_BASE, ['file' => subjectImportCsv([])])->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);

it('impor mapel selesai tanpa worker antrean dan job cadangan tidak mengimpor ulang', function () {
    config(['queue.default' => 'database']);
    $this->actingAsRole(Role::Operator);

    $this->post(MAPEL_IMPOR_BASE, ['file' => subjectImportCsv([
        ['kode' => 'FIK', 'nama' => 'Fikih', 'kelompok' => 'Kepesantrenan'],
    ])])->assertSessionHasNoErrors();

    expect(SubjectImport::query()->sole()->status)->toBe(ImportStatus::Completed);

    $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true])->assertSuccessful();

    expect(Subject::query()->where('code', 'FIK')->count())->toBe(1)
        ->and(SubjectImport::query()->sole()->success_rows)->toBe(1);
});
