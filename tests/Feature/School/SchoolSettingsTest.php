<?php

use App\Modules\Core\Enums\Role;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\SettingsService;
use App\Modules\School\Enums\SchoolLevel;
use App\Modules\School\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

function schoolPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'SMP Negeri 1 Pangkalpinang',
        'npsn' => '10901234',
        'level' => 'SMP',
        'address' => 'Jl. Merdeka No. 1',
        'phone' => '(0717) 123456',
        'email' => 'info@smpn1.sch.id',
        'principal_name' => 'Dra. Siti Rahmawati, M.Pd.',
        'principal_nip' => '196801011990032001',
        'timezone' => 'Asia/Jakarta',
        'upload_max_size_kb' => 4096,
        'attendance_mode' => 'HARIAN',
        'attendance_edit_days' => 0,
        'teacher_late_after' => '07:15',
        'teacher_attendance_validation' => 'NONAKTIF',
        'teacher_attendance_ips' => '',
        'school_latitude' => null,
        'school_longitude' => null,
        'teacher_attendance_radius_m' => 200,
        'grade_kkm' => 75,
        'grade_predicate_a' => 90,
        'grade_predicate_b' => 80,
        'grade_predicate_c' => 70,
    ], $overrides);
}

it('migrasi membuat satu profil sekolah default', function () {
    expect(School::query()->count())->toBe(1)
        ->and(School::current()->level)->toBe(SchoolLevel::Smp)
        ->and(School::current()->name)->toBe('Sekolah Skoolia');
});

it('migrasi memindahkan identitas lama dari settings ke schools', function () {
    Schema::drop('schools');
    DB::table('settings')->insert([
        ['key' => 'school_name', 'value' => json_encode('SMA Lama'), 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'school_phone', 'value' => json_encode('0717-1'), 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'timezone', 'value' => json_encode('Asia/Jakarta'), 'created_at' => now(), 'updated_at' => now()],
    ]);

    $migration = require base_path('app/Modules/School/Database/Migrations/2026_09_28_100000_create_schools_table.php');
    $migration->up();

    $school = School::current();
    expect($school->name)->toBe('SMA Lama')
        ->and($school->phone)->toBe('0717-1')
        ->and(DB::table('settings')->where('key', 'like', 'school_%')->count())->toBe(0)
        ->and(DB::table('settings')->where('key', 'timezone')->exists())->toBeTrue();
});

it('operator membuka halaman pengaturan sekolah', function () {
    $this->actingAsRole(Role::Operator);

    $this->get('/operator/settings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('School::Operator/Settings')
            ->where('school.name', 'Sekolah Skoolia')
            ->where('school.level', 'SMP')
            ->has('levels', 4)
            ->has('timezones', 3));
});

it('menyimpan profil sekolah dan pengaturan sistem', function () {
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', schoolPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect('/operator/settings');

    $school = School::current();
    expect($school->name)->toBe('SMP Negeri 1 Pangkalpinang')
        ->and($school->npsn)->toBe('10901234')
        ->and($school->principal_nip)->toBe('196801011990032001')
        ->and((new SettingsService)->get(SettingKey::UploadMaxSizeKb))->toBe(4096);

    expect(Activity::query()->where('log_name', 'school')->where('description', 'Mengubah profil sekolah')->exists())->toBeTrue()
        ->and(Activity::query()->where('log_name', 'settings')->exists())->toBeTrue();

    // Nama sekolah ikut dibagikan ke Inertia (cache profil dibersihkan).
    $this->get('/operator/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('school.name', 'SMP Negeri 1 Pangkalpinang'));
});

it('mengunggah logo sebagai file publik milik sekolah dan mengganti logo lama', function () {
    Storage::fake('public');
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', schoolPayload(['logo' => UploadedFile::fake()->image('logo.png', 300, 300)]))
        ->assertSessionHasNoErrors();

    $first = File::query()->firstOrFail();
    expect(School::current()->logo_file_id)->toBe($first->id)
        ->and($first->fileable_type)->toBe(School::class);

    $this->post('/operator/settings', schoolPayload(['logo' => UploadedFile::fake()->image('baru.png', 300, 300)]));

    expect($first->fresh()->trashed())->toBeTrue();

    $this->get('/operator/dashboard')
        ->assertInertia(fn (Assert $page) => $page->whereNot('school.logo_url', null));

    $this->post('/operator/settings', schoolPayload(['remove_logo' => true]));
    expect(School::current()->logo_file_id)->toBeNull();
});

it('menolak logo dengan MIME palsu walau berekstensi .png', function () {
    Storage::fake('public');
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', schoolPayload([
        'logo' => UploadedFile::fake()->createWithContent('logo.png', '<?php echo 1;'),
    ]))->assertSessionHasErrors(['logo' => 'Tipe file tidak diizinkan.']);

    expect(File::query()->count())->toBe(0);
});

it('memvalidasi input pengaturan', function () {
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', schoolPayload([
        'name' => '',
        'npsn' => '123',
        'level' => 'TK',
        'timezone' => 'Europe/London',
        'upload_max_size_kb' => 999999,
        'phone' => 'telp<script>',
        'principal_nip' => 'abc',
    ]))->assertSessionHasErrors(['name', 'npsn', 'level', 'timezone', 'upload_max_size_kb', 'phone', 'principal_nip']);
});

it('role lain tidak dapat membuka atau mengubah pengaturan', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/operator/settings')->assertForbidden();
    $this->post('/operator/settings', schoolPayload())->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);

it('rentang tingkat mengikuti jenjang', function () {
    expect(SchoolLevel::Sd->gradeRange())->toBe([1, 2, 3, 4, 5, 6])
        ->and(SchoolLevel::Smp->gradeRange())->toBe([7, 8, 9])
        ->and(SchoolLevel::Smk->gradeRange())->toBe([10, 11, 12])
        ->and(SchoolLevel::Smp->gradeLabel(7))->toBe('VII')
        ->and(SchoolLevel::Sd->gradeLabel(4))->toBe('4');
});

it('menyimpan pengaturan presensi guru dan memvalidasi IP & koordinat', function () {
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', schoolPayload([
        'teacher_attendance_validation' => 'IP',
        'teacher_attendance_ips' => "103.10.20.0/24\nbukan-ip",
    ]))->assertSessionHasErrors('teacher_attendance_ips');

    $this->post('/operator/settings', schoolPayload(['teacher_attendance_validation' => 'GPS']))
        ->assertSessionHasErrors(['school_latitude', 'school_longitude']);

    $this->post('/operator/settings', schoolPayload([
        'teacher_late_after' => '07:00',
        'teacher_attendance_validation' => 'GPS',
        'school_latitude' => -2.1291,
        'school_longitude' => 106.1134,
        'teacher_attendance_radius_m' => 150,
    ]))->assertSessionHasNoErrors();

    $settings = app(SettingsService::class);
    $settings->flush();
    expect($settings->get(SettingKey::TeacherLateAfter))->toBe('07:00')
        ->and($settings->get(SettingKey::SchoolLatitude))->toBe(-2.1291)
        ->and($settings->get(SettingKey::TeacherAttendanceRadiusM))->toBe(150);
});

it('batas predikat harus menurun A > B > C', function () {
    $this->actingAsRole(Role::Operator);

    $this->post('/operator/settings', schoolPayload(['grade_predicate_a' => 80, 'grade_predicate_b' => 85]))
        ->assertSessionHasErrors('grade_predicate_a');
});
