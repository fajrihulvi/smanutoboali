<?php

use App\Modules\Core\Enums\Role;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\ImportStatus;
use App\Modules\People\Exports\TeacherImportTemplate;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Models\TeacherImport;
use App\Modules\People\Services\TeacherImportRowProcessor;
use App\Support\Excel\SafeValueBinder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Activitylog\Models\Activity;

const GURU_IMPOR_BASE = '/operator/master-data/guru/impor';

/**
 * @param  list<array<string, string>>  $rows
 */
function teacherImportCsv(array $rows): UploadedFile
{
    $lines = [implode(',', TeacherImportRowProcessor::COLUMNS)];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(
            fn (string $column) => '"'.str_replace('"', '""', $row[$column] ?? '').'"',
            TeacherImportRowProcessor::COLUMNS,
        ));
    }

    return UploadedFile::fake()->createWithContent('guru.csv', implode("\n", $lines)."\n");
}

beforeEach(function () {
    Storage::fake('private');
});

it('operator membuka halaman impor guru dan mengunduh template', function () {
    $this->actingAsRole(Role::Operator);

    $this->get(GURU_IMPOR_BASE)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('People::Operator/Teachers/Import')
        ->has('imports.data', 0));

    $response = $this->get(GURU_IMPOR_BASE.'/template')
        ->assertOk()
        ->assertDownload('template-import-guru.xlsx');

    $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

    expect($sheet->rangeToArray('A1:F1')[0])->toBe(TeacherImportRowProcessor::COLUMNS)
        // NIP contoh tersimpan sebagai teks utuh, bukan angka 1.98E+17.
        ->and($sheet->getCell('A2')->getValue())->toBe('198001012005011001');
});

it('mengimpor guru dan melaporkan baris gagal', function () {
    Teacher::factory()->create(['nip' => '197001012000031001']);
    $this->actingAsRole(Role::Operator);

    $file = teacherImportCsv([
        // Baris 2: valid, NIP berspasi, telepon tanpa 0, status pakai label.
        ['nip' => '19800101 200501 1 001', 'nuptk' => '1234567890123456', 'nama' => 'Dewi Lestari, S.Pd.',
            'jenis_kelamin' => 'Perempuan', 'status_kepegawaian' => 'Guru Tetap Yayasan', 'telepon' => '81234567890'],
        // Baris 3: valid tanpa NIP/NUPTK.
        ['nama' => 'Agus Salim', 'jenis_kelamin' => 'L', 'status_kepegawaian' => 'honorer'],
        // Baris 4: NIP sudah terdaftar.
        ['nip' => '197001012000031001', 'nama' => 'Duplikat', 'jenis_kelamin' => 'L', 'status_kepegawaian' => 'PNS'],
        // Baris 5: data salah.
        ['nip' => '123', 'nama' => 'Salah Data', 'jenis_kelamin' => 'X', 'status_kepegawaian' => 'Kontrak'],
        // Baris 6: NUPTK ganda di file yang sama (baris 2 sudah memakainya).
        ['nuptk' => '1234567890123456', 'nama' => 'Ganda', 'jenis_kelamin' => 'P', 'status_kepegawaian' => 'GTT'],
        // Baris 7: kosong, dilewati.
        [],
    ]);

    $this->post(GURU_IMPOR_BASE, ['file' => $file])->assertSessionHasNoErrors()->assertSessionHas('success');

    $import = TeacherImport::query()->firstOrFail();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->total_rows)->toBe(5)
        ->and($import->success_rows)->toBe(2)
        ->and($import->failed_rows)->toBe(3)
        ->and(collect($import->errors)->pluck('row')->all())->toBe([4, 5, 6])
        ->and($import->errors[0]['messages'][0])->toContain('NIP 197001012000031001 sudah terdaftar');

    $invalid = implode(' ', $import->errors[1]['messages']);
    expect($invalid)->toContain('NIP harus terdiri dari 18 angka')
        ->and($invalid)->toContain('Jenis kelamin harus L atau P')
        ->and($invalid)->toContain('Status kepegawaian "Kontrak" tidak dikenali')
        ->and($import->errors[2]['messages'][0])->toContain('NUPTK 1234567890123456 sudah terdaftar');

    $dewi = Teacher::query()->where('nip', '198001012005011001')->firstOrFail();
    expect($dewi->name)->toBe('Dewi Lestari, S.Pd.')
        ->and($dewi->gender->value)->toBe('P')
        ->and($dewi->employment_status)->toBe(EmploymentStatus::Gty)
        ->and($dewi->phone)->toBe('081234567890')
        ->and($dewi->is_active)->toBeTrue()
        ->and($dewi->user_id)->toBeNull()
        ->and(Teacher::query()->where('name', 'Agus Salim')->value('employment_status'))->toBe(EmploymentStatus::Honorer);

    // Satu entri audit untuk file, bukan satu per guru.
    expect(Activity::query()->where('description', 'like', 'Menambah guru%')->count())->toBe(0)
        ->and(Activity::query()->where('description', 'like', 'Import guru guru.csv%')->count())->toBe(1);
});

it('memberi pesan jelas bila NIP terbaca sebagai angka oleh Excel', function () {
    $messages = app(TeacherImportRowProcessor::class)->process([
        'nip' => 1.98001012005011E+17,
        'nama' => 'Angka',
        'jenis_kelamin' => 'L',
        'status_kepegawaian' => 'PNS',
    ]);

    expect($messages)->toHaveCount(1)
        ->and($messages[0])->toContain('Format kolom NIP sebagai Teks')
        ->and(Teacher::query()->count())->toBe(0);
});

it('mengimpor file .xlsx dari template guru yang diunduh', function () {
    $this->actingAsRole(Role::Operator);

    $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
    file_put_contents($path, Maatwebsite\Excel\Facades\Excel::raw(new TeacherImportTemplate, Excel::XLSX));
    SafeValueBinder::reset();
    $file = new UploadedFile($path, 'template-import-guru.xlsx', null, null, true);

    $this->post(GURU_IMPOR_BASE, ['file' => $file])->assertSessionHasNoErrors();

    $import = TeacherImport::query()->firstOrFail();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->success_rows)->toBe(1)
        ->and(Teacher::query()->where('nip', '198001012005011001')->value('nuptk'))->toBe('1234567890123456');
});

it('menolak file guru dengan isi palsu atau ekstensi lain', function () {
    $this->actingAsRole(Role::Operator);

    $this->post(GURU_IMPOR_BASE, ['file' => UploadedFile::fake()->createWithContent('guru.csv', '<?php system($_GET["c"]); ?>')])
        ->assertSessionHasErrors('file');
    $this->post(GURU_IMPOR_BASE, ['file' => UploadedFile::fake()->create('guru.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('file');

    expect(TeacherImport::query()->count())->toBe(0);
});

it('role lain tidak dapat mengimpor guru', function (Role $role) {
    $this->actingAsRole($role);

    $this->get(GURU_IMPOR_BASE)->assertForbidden();
    $this->get(GURU_IMPOR_BASE.'/template')->assertForbidden();
    $this->post(GURU_IMPOR_BASE, ['file' => teacherImportCsv([])])->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);
