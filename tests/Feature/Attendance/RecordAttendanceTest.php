<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\Attendance\Notifications\StudentAbsenceNotification;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    // Senin, di dalam semester ganjil 2026/2027.
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * Wali kelas (akun guru) + rombel tahun aktif berisi dua siswa; siswa pertama punya akun orang tua.
 *
 * @return array{teacher: User, parent: User, classroom: Classroom, students: list<Student>}
 */
function homeroomScenario(): array
{
    $teacher = User::factory()->withRole(Role::Teacher)->create();
    $classroom = Classroom::factory()->for(activeAcademicYear())->create([
        'name' => 'VII-A',
        'homeroom_teacher_id' => Teacher::factory()->withAccount($teacher)->create()->id,
    ]);

    $parent = User::factory()->withRole(Role::Guardian)->create();
    $students = [
        Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create(['name' => 'Ani']),
        Student::factory()->create(['name' => 'Budi']),
    ];

    foreach ($students as $student) {
        enrollIn($classroom, $student);
    }

    return compact('teacher', 'parent', 'classroom', 'students');
}

/**
 * @param  list<Student>  $students
 * @param  array<string, string>  $statuses  public_id → status (default HADIR)
 * @return array<string, mixed>
 */
function attendancePayload(array $students, array $statuses = [], string $date = '2026-09-28', ?string $schedule = null): array
{
    return [
        'date' => $date,
        'schedule' => $schedule,
        'records' => array_map(fn (Student $s) => [
            'student' => $s->public_id,
            'status' => $statuses[$s->public_id] ?? 'HADIR',
            'note' => null,
        ], $students),
    ];
}

function setAttendanceSettings(array $values): void
{
    app(SettingsService::class)->set($values);
}

describe('mengisi presensi (mode harian)', function () {
    it('wali kelas melihat rombelnya di daftar dan membuka halaman isi presensi', function () {
        ['teacher' => $teacher, 'classroom' => $classroom] = homeroomScenario();
        Classroom::factory()->for($classroom->academicYear)->create(['name' => 'VII-Z']);
        $this->actingAsRole(Role::Teacher, $teacher);

        $this->get('/guru/presensi')->assertInertia(fn (Assert $page) => $page
            ->component('Attendance::Shared/Index')
            ->where('mode.value', 'HARIAN')
            ->has('items', 1)
            ->where('items.0.classroom.name', 'VII-A')
            ->where('items.0.recorded', false));

        $this->get("/guru/presensi/rombel/{$classroom->public_id}?tanggal=2026-09-28")->assertInertia(fn (Assert $page) => $page
            ->component('Attendance::Shared/Take')
            ->has('students', 2)
            ->where('students.0.status', null)
            ->where('editable', true)
            ->missing('students.0.id'));
    });

    it('menyimpan presensi, tercatat audit, dan menandai rombel sudah diisi', function () {
        Notification::fake();
        ['teacher' => $teacher, 'classroom' => $classroom, 'students' => $students] = homeroomScenario();
        $this->actingAsRole(Role::Teacher, $teacher);

        $this->put("/guru/presensi/rombel/{$classroom->public_id}", attendancePayload($students, [$students[1]->public_id => 'SAKIT']))
            ->assertSessionHasNoErrors();

        $session = AttendanceSession::query()->sole();
        expect($session->records()->pluck('status', 'student_id')->map->value->all())
            ->toBe([$students[0]->id => 'HADIR', $students[1]->id => 'SAKIT'])
            ->and($session->semester_id)->not->toBeNull()
            ->and(Activity::query()->where('log_name', 'attendance')->where('event', 'recorded')->count())->toBe(1);

        $this->get('/guru/presensi?tanggal=2026-09-28')->assertInertia(fn (Assert $page) => $page
            ->where('items.0.recorded', true)
            ->where('items.0.counts.SAKIT', 1));
    });

    it('menyimpan ulang memperbarui sesi yang sama', function () {
        ['teacher' => $teacher, 'classroom' => $classroom, 'students' => $students] = homeroomScenario();
        $this->actingAsRole(Role::Teacher, $teacher);

        $this->put("/guru/presensi/rombel/{$classroom->public_id}", attendancePayload($students));
        $this->put("/guru/presensi/rombel/{$classroom->public_id}", attendancePayload($students, [$students[0]->public_id => 'IZIN']));

        expect(AttendanceSession::query()->count())->toBe(1)
            ->and(AttendanceRecord::query()->count())->toBe(2)
            ->and(AttendanceSession::query()->sole()->updated_by)->toBe($teacher->id);
    });

    it('guru yang tidak mengajar di rombel ditolak (403)', function () {
        ['classroom' => $classroom, 'students' => $students] = homeroomScenario();
        $stranger = User::factory()->withRole(Role::Teacher)->create();
        Teacher::factory()->withAccount($stranger)->create();
        $this->actingAsRole(Role::Teacher, $stranger);

        $this->get("/guru/presensi/rombel/{$classroom->public_id}")->assertForbidden();
        $this->put("/guru/presensi/rombel/{$classroom->public_id}", attendancePayload($students))->assertForbidden();
        $this->get("/guru/presensi/rombel/{$classroom->public_id}/rekap")->assertForbidden();

        expect(AttendanceSession::query()->count())->toBe(0);
    });

    it('guru pengampu mapel di rombel boleh mengisi presensi harian', function () {
        ['classroom' => $classroom, 'students' => $students] = homeroomScenario();
        $subjectTeacher = User::factory()->withRole(Role::Teacher)->create();
        assignTeaching($classroom, Teacher::factory()->withAccount($subjectTeacher)->create());
        $this->actingAsRole(Role::Teacher, $subjectTeacher);

        $this->put("/guru/presensi/rombel/{$classroom->public_id}", attendancePayload($students))->assertSessionHasNoErrors();
    });

    it('menolak tanggal masa depan, hari Minggu, di luar semester, dan daftar siswa yang tidak cocok', function () {
        ['teacher' => $teacher, 'classroom' => $classroom, 'students' => $students] = homeroomScenario();
        setAttendanceSettings([SettingKey::AttendanceEditDays->value => 30]);
        $this->actingAsRole(Role::Teacher, $teacher);
        $url = "/guru/presensi/rombel/{$classroom->public_id}";

        $this->put($url, attendancePayload($students, date: '2026-09-29'))->assertSessionHasErrors('date');
        $this->put($url, attendancePayload($students, date: '2026-09-27'))->assertSessionHasErrors('date');
        $this->put($url, attendancePayload($students, date: '2026-06-30'))->assertSessionHasErrors('date');
        $this->put($url, attendancePayload([$students[0]]))->assertSessionHasErrors('records');

        expect(AttendanceSession::query()->count())->toBe(0);
    });
});

describe('batas edit', function () {
    it('guru tidak bisa mengisi/mengubah presensi kemarin bila batas 0 hari; operator bisa', function () {
        ['teacher' => $teacher, 'classroom' => $classroom, 'students' => $students] = homeroomScenario();
        $url = "/guru/presensi/rombel/{$classroom->public_id}";

        $this->actingAsRole(Role::Teacher, $teacher);
        $this->put($url, attendancePayload($students, date: '2026-09-26'))->assertSessionHasErrors('date');
        $this->get("{$url}?tanggal=2026-09-26")->assertInertia(fn (Assert $page) => $page->where('editable', false));

        $this->actingAsRole(Role::Operator);
        $this->put("/operator/presensi/rombel/{$classroom->public_id}", attendancePayload($students, date: '2026-09-26'))
            ->assertSessionHasNoErrors();
    });

    it('batas edit mengikuti pengaturan', function () {
        ['teacher' => $teacher, 'classroom' => $classroom, 'students' => $students] = homeroomScenario();
        setAttendanceSettings([SettingKey::AttendanceEditDays->value => 2]);
        $this->actingAsRole(Role::Teacher, $teacher);

        $this->put("/guru/presensi/rombel/{$classroom->public_id}", attendancePayload($students, date: '2026-09-26'))
            ->assertSessionHasNoErrors();
    });
});

describe('notifikasi orang tua', function () {
    it('dikirim hanya saat status baru menjadi ALPA/TERLAMBAT', function () {
        Notification::fake();
        ['teacher' => $teacher, 'parent' => $parent, 'classroom' => $classroom, 'students' => $students] = homeroomScenario();
        $this->actingAsRole(Role::Teacher, $teacher);
        $url = "/guru/presensi/rombel/{$classroom->public_id}";
        $ani = $students[0]->public_id;

        $this->put($url, attendancePayload($students, [$ani => 'ALPA']));
        Notification::assertSentToTimes($parent, StudentAbsenceNotification::class, 1);

        $this->put($url, attendancePayload($students, [$ani => 'ALPA']));
        Notification::assertSentToTimes($parent, StudentAbsenceNotification::class, 1);

        $this->put($url, attendancePayload($students, [$ani => 'TERLAMBAT']));
        Notification::assertSentToTimes($parent, StudentAbsenceNotification::class, 2);

        $this->put($url, attendancePayload($students, [$ani => 'SAKIT']));
        Notification::assertSentToTimes($parent, StudentAbsenceNotification::class, 2);
    });

    it('isi notifikasi mengarah ke halaman presensi anak lewat database & email', function () {
        Notification::fake();
        ['teacher' => $teacher, 'parent' => $parent, 'classroom' => $classroom, 'students' => $students] = homeroomScenario();
        $this->actingAsRole(Role::Teacher, $teacher);

        $this->put("/guru/presensi/rombel/{$classroom->public_id}", attendancePayload($students, [$students[0]->public_id => 'ALPA']));

        Notification::assertSentTo($parent, StudentAbsenceNotification::class, function (StudentAbsenceNotification $n, array $channels) use ($parent, $students) {
            $data = $n->toArray($parent);

            return $channels === ['database', 'broadcast', 'mail']
                && $data['title'] === 'Ani tercatat Alpa'
                && $data['url'] === "/orang-tua/presensi?anak={$students[0]->public_id}";
        });
    });
});

it('role tanpa akses ke portal presensi guru/operator ditolak', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/guru/presensi')->assertForbidden();
    $this->get('/operator/presensi')->assertForbidden();
})->with([Role::Principal, Role::Guardian]);
