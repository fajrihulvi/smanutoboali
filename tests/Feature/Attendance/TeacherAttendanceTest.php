<?php

use App\Models\User;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Models\TeacherAttendance;
use App\Modules\Attendance\Services\TeacherLocationGuard;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Teacher;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

/**
 * @return array{user: User, teacher: Teacher}
 */
function teacherAccount(): array
{
    $user = User::factory()->withRole(Role::Teacher)->create();

    return ['user' => $user, 'teacher' => Teacher::factory()->withAccount($user)->create()];
}

describe('presensi mandiri guru', function () {
    it('kartu dashboard menampilkan tombol presensi masuk', function () {
        $this->travelTo('2026-09-28 06:50:00');
        ['user' => $user] = teacherAccount();
        $this->actingAsRole(Role::Teacher, $user);

        $this->get('/guru/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('selfAttendance.hasTeacherProfile', true)
            ->where('selfAttendance.canCheckIn', true)
            ->where('selfAttendance.canCheckOut', false)
            ->where('selfAttendance.today', null));
    });

    it('masuk sebelum jam batas = HADIR, lalu pulang; masing-masing sekali', function () {
        $this->travelTo('2026-09-28 07:05:00');
        ['user' => $user, 'teacher' => $teacher] = teacherAccount();
        $this->actingAsRole(Role::Teacher, $user);

        $this->post('/guru/kehadiran/pulang')->assertSessionHasErrors('attendance');
        $this->post('/guru/kehadiran/masuk')->assertSessionHasNoErrors();
        $this->post('/guru/kehadiran/masuk')->assertSessionHasErrors('attendance');

        $this->travelTo('2026-09-28 14:00:00');
        $this->post('/guru/kehadiran/pulang')->assertSessionHasNoErrors();
        $this->post('/guru/kehadiran/pulang')->assertSessionHasErrors('attendance');

        $record = TeacherAttendance::query()->sole();
        expect($record->teacher_id)->toBe($teacher->id)
            ->and($record->status)->toBe(TeacherAttendanceStatus::Present)
            ->and($record->check_in_at?->format('H:i'))->toBe('07:05')
            ->and($record->check_out_at?->format('H:i'))->toBe('14:00')
            ->and($record->source)->toBe('MANDIRI');
    });

    it('masuk setelah jam batas (pengaturan) = TERLAMBAT', function () {
        app(SettingsService::class)->set([SettingKey::TeacherLateAfter->value => '07:00']);
        $this->travelTo('2026-09-28 07:01:00');
        ['user' => $user] = teacherAccount();
        $this->actingAsRole(Role::Teacher, $user);

        $this->post('/guru/kehadiran/masuk');

        expect(TeacherAttendance::query()->sole()->status)->toBe(TeacherAttendanceStatus::Late);
    });

    it('menolak hari Minggu dan guru yang sudah dicatat izin oleh operator', function () {
        ['user' => $user, 'teacher' => $teacher] = teacherAccount();
        $this->actingAsRole(Role::Teacher, $user);

        $this->travelTo('2026-09-27 07:00:00');
        $this->post('/guru/kehadiran/masuk')->assertSessionHasErrors('attendance');

        $this->travelTo('2026-09-28 07:00:00');
        TeacherAttendance::query()->create([
            'teacher_id' => $teacher->id, 'date' => '2026-09-28', 'status' => TeacherAttendanceStatus::Permit, 'source' => 'OPERATOR',
        ]);
        $this->post('/guru/kehadiran/masuk')->assertSessionHasErrors('attendance');
    });

    it('akun guru tanpa profil guru ditolak; role lain tidak bisa memakai tombol ini', function () {
        $this->travelTo('2026-09-28 07:00:00');
        $this->actingAsRole(Role::Teacher);
        $this->post('/guru/kehadiran/masuk')->assertForbidden();

        $this->actingAsRole(Role::Guardian);
        $this->post('/guru/kehadiran/masuk')->assertForbidden();
    });
});

describe('validasi lokasi', function () {
    beforeEach(fn () => $this->travelTo('2026-09-28 07:00:00'));

    it('mode IP: hanya dari jaringan sekolah (mendukung CIDR)', function () {
        app(SettingsService::class)->set([
            SettingKey::TeacherAttendanceValidation->value => 'IP',
            SettingKey::TeacherAttendanceIps->value => "103.10.20.0/24\n10.0.0.1",
        ]);
        ['user' => $user] = teacherAccount();
        $this->actingAsRole(Role::Teacher, $user);

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->post('/guru/kehadiran/masuk')->assertSessionHasErrors('location');
        $this->withServerVariables(['REMOTE_ADDR' => '103.10.20.77'])->post('/guru/kehadiran/masuk')->assertSessionHasNoErrors();

        expect(TeacherAttendance::query()->sole()->check_in_meta['ip'])->toBe('103.10.20.77');
    });

    it('mode GPS: di dalam radius diterima, di luar atau tanpa koordinat ditolak', function () {
        app(SettingsService::class)->set([
            SettingKey::TeacherAttendanceValidation->value => 'GPS',
            SettingKey::SchoolLatitude->value => -2.1291,
            SettingKey::SchoolLongitude->value => 106.1134,
            SettingKey::TeacherAttendanceRadiusM->value => 200,
        ]);
        ['user' => $user] = teacherAccount();
        $this->actingAsRole(Role::Teacher, $user);

        $this->post('/guru/kehadiran/masuk')->assertSessionHasErrors('location');
        $this->post('/guru/kehadiran/masuk', ['lat' => -2.1391, 'lng' => 106.1134])->assertSessionHasErrors('location'); // ±1,1 km
        $this->post('/guru/kehadiran/masuk', ['lat' => -2.1292, 'lng' => 106.1135])->assertSessionHasNoErrors();

        expect(TeacherAttendance::query()->sole()->check_in_meta['distance_m'])->toBeLessThan(200);
    });

    it('jarak Haversine masuk akal', function () {
        $distance = app(TeacherLocationGuard::class)->distance(-2.1291, 106.1134, -2.1391, 106.1134);

        expect($distance)->toBeGreaterThan(1100)->toBeLessThan(1120);
    });
});

describe('operator & kepala sekolah', function () {
    beforeEach(fn () => $this->travelTo('2026-09-28 10:00:00'));

    it('operator melihat semua guru aktif per tanggal', function () {
        ['teacher' => $present] = teacherAccount();
        TeacherAttendance::query()->create([
            'teacher_id' => $present->id, 'date' => '2026-09-28', 'check_in_at' => '2026-09-28 07:00:00',
            'status' => TeacherAttendanceStatus::Present, 'source' => 'MANDIRI',
        ]);
        Teacher::factory()->create();
        Teacher::factory()->inactive()->create();
        $this->actingAsRole(Role::Operator);

        $this->get('/operator/presensi-guru?tanggal=2026-09-28')->assertInertia(fn (Assert $page) => $page
            ->component('Attendance::Operator/Teachers/Index')
            ->has('rows', 2)
            ->missing('rows.0.teacher.id'));
    });

    it('operator mengoreksi status & jam; tercatat audit dengan nilai lama', function () {
        ['teacher' => $teacher] = teacherAccount();
        TeacherAttendance::query()->create([
            'teacher_id' => $teacher->id, 'date' => '2026-09-28', 'check_in_at' => '2026-09-28 07:40:00',
            'status' => TeacherAttendanceStatus::Late, 'source' => 'MANDIRI',
        ]);
        $this->actingAsRole(Role::Operator);

        $this->put("/operator/presensi-guru/{$teacher->public_id}", [
            'date' => '2026-09-28', 'status' => 'HADIR', 'check_in' => '07:05', 'check_out' => '14:00', 'note' => 'Mesin absen error',
        ])->assertSessionHasNoErrors();

        $record = TeacherAttendance::query()->sole();
        expect($record->status)->toBe(TeacherAttendanceStatus::Present)
            ->and($record->check_in_at?->format('H:i'))->toBe('07:05')
            ->and($record->source)->toBe('OPERATOR');

        $log = Activity::query()->where('event', 'teacher_corrected')->sole();
        expect($log->properties['old']['status'])->toBe('TERLAMBAT')
            ->and($log->properties['attributes']['check_in'])->toBe('07:05');
    });

    it('input izin menghapus jam; validasi jam & tanggal', function () {
        ['teacher' => $teacher] = teacherAccount();
        $this->actingAsRole(Role::Operator);
        $url = "/operator/presensi-guru/{$teacher->public_id}";

        $this->put($url, ['date' => '2026-09-28', 'status' => 'HADIR', 'check_in' => '10:00', 'check_out' => '09:00'])->assertSessionHasErrors('check_out');
        $this->put($url, ['date' => '2026-09-28', 'status' => 'HADIR'])->assertSessionHasErrors('check_in');
        $this->put($url, ['date' => '2026-09-30', 'status' => 'IZIN'])->assertSessionHasErrors('date');
        $this->put($url, ['date' => '2026-09-28', 'status' => 'IZIN', 'check_in' => '07:00', 'note' => 'Surat izin'])->assertSessionHasNoErrors();

        expect(TeacherAttendance::query()->sole())
            ->status->toBe(TeacherAttendanceStatus::Permit)
            ->check_in_at->toBeNull();
    });

    it('rekap periode per guru untuk operator dan kepala sekolah', function () {
        ['teacher' => $teacher] = teacherAccount();
        foreach (['2026-09-24' => 'HADIR', '2026-09-25' => 'SAKIT', '2026-09-26' => 'DINAS', '2026-09-28' => 'TERLAMBAT'] as $date => $status) {
            TeacherAttendance::query()->create(['teacher_id' => $teacher->id, 'date' => $date, 'status' => $status, 'source' => 'OPERATOR']);
        }

        foreach ([[Role::Operator, '/operator/presensi-guru/rekap'], [Role::Principal, '/kepala-sekolah/presensi-guru']] as [$role, $url]) {
            $this->actingAsRole($role);

            $this->get("{$url}?dari=2026-09-01&sampai=2026-09-28")->assertInertia(fn (Assert $page) => $page
                ->component('Attendance::Shared/TeacherRecap')
                ->where('rows.0.summary.days', 4)
                ->where('rows.0.summary.percentage', 75)
                ->where('rows.0.summary.counts.SAKIT', 1));
        }
    });

    it('guru tidak bisa membuka halaman presensi guru operator/kepala sekolah', function () {
        ['user' => $user, 'teacher' => $teacher] = teacherAccount();
        $this->actingAsRole(Role::Teacher, $user);

        $this->get('/operator/presensi-guru')->assertForbidden();
        $this->put("/operator/presensi-guru/{$teacher->public_id}", ['date' => '2026-09-28', 'status' => 'HADIR'])->assertForbidden();
        $this->get('/kepala-sekolah/presensi-guru')->assertForbidden();
    });
});
