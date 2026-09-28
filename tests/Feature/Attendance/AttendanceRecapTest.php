<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\Semester;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-09-28 10:00:00');
});

/**
 * Sesi + catatan langsung (tanpa action) untuk skenario rekap.
 *
 * @param  array<int, AttendanceStatus>  $statuses  student_id → status
 */
function recordSession(Classroom $classroom, string $date, array $statuses, ?int $scheduleId = null): AttendanceSession
{
    $session = AttendanceSession::query()->create([
        'academic_year_id' => $classroom->academic_year_id,
        'semester_id' => Semester::query()->where('academic_year_id', $classroom->academic_year_id)->value('id'),
        'classroom_id' => $classroom->id,
        'date' => $date,
        'schedule_id' => $scheduleId,
    ]);

    foreach ($statuses as $studentId => $status) {
        AttendanceRecord::query()->create(['attendance_session_id' => $session->id, 'student_id' => $studentId, 'status' => $status]);
    }

    return $session;
}

describe('mode per mapel', function () {
    beforeEach(fn () => app(SettingsService::class)->set([SettingKey::AttendanceMode->value => 'PER_MAPEL']));

    it('guru pengampu mengisi presensi jadwalnya; guru lain & jadwal hari lain ditolak', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $student = Student::factory()->create();
        enrollIn($classroom, $student);

        $owner = User::factory()->withRole(Role::Teacher)->create();
        $monday = scheduleAt(assignTeaching($classroom, Teacher::factory()->withAccount($owner)->create()), 1);
        $tuesday = scheduleAt($monday->teachingAssignment, 2);

        $homeroom = User::factory()->withRole(Role::Teacher)->create();
        $classroom->update(['homeroom_teacher_id' => Teacher::factory()->withAccount($homeroom)->create()->id]);

        $payload = fn (?string $schedule) => [
            'date' => '2026-09-28',
            'schedule' => $schedule,
            'records' => [['student' => $student->public_id, 'status' => 'HADIR']],
        ];
        $url = "/guru/presensi/rombel/{$classroom->public_id}";

        $this->actingAsRole(Role::Teacher, $owner);
        $this->get('/guru/presensi?tanggal=2026-09-28')->assertInertia(fn (Assert $page) => $page
            ->where('mode.value', 'PER_MAPEL')
            ->has('items', 1)
            ->where('items.0.schedule.public_id', $monday->public_id));

        $this->put($url, $payload(null))->assertSessionHasErrors('schedule');
        $this->put($url, $payload($tuesday->public_id))->assertSessionHasErrors('schedule');
        $this->put($url, $payload($monday->public_id))->assertSessionHasNoErrors();

        // Wali kelas bukan pengampu jadwal ini.
        $this->actingAsRole(Role::Teacher, $homeroom);
        $this->put($url, $payload($monday->public_id))->assertForbidden();

        expect(AttendanceSession::query()->sole()->schedule_id)->toBe($monday->id);
    });

    it('status harian memakai status terberat dari beberapa sesi', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $student = Student::factory()->create();
        enrollIn($classroom, $student);
        $first = scheduleAt(assignTeaching($classroom, Teacher::factory()->create()), 1, '07:30', '08:10');
        $second = scheduleAt(assignTeaching($classroom, Teacher::factory()->create()), 1, '08:10', '08:50');

        recordSession($classroom, '2026-09-28', [$student->id => AttendanceStatus::Present], $first->id);
        recordSession($classroom, '2026-09-28', [$student->id => AttendanceStatus::Absent], $second->id);
        $this->actingAsRole(Role::Operator);

        $this->get("/operator/presensi/siswa/{$student->public_id}?bulan=2026-09")->assertInertia(fn (Assert $page) => $page
            ->component('Attendance::Shared/StudentCalendar')
            ->where('calendar.days.2026-09-28.status', 'ALPA')
            ->has('calendar.days.2026-09-28.entries', 2)
            ->where('calendar.summary.days', 1)
            ->where('calendar.summary.percentage', 0));
    });
});

describe('rekap', function () {
    it('rekap bulanan rombel menghitung per siswa dan persentase', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        [$a, $b] = Student::factory()->count(2)->create()->all();
        enrollIn($classroom, $a);
        enrollIn($classroom, $b);
        recordSession($classroom, '2026-09-24', [$a->id => AttendanceStatus::Present, $b->id => AttendanceStatus::Late]);
        recordSession($classroom, '2026-09-25', [$a->id => AttendanceStatus::Sick, $b->id => AttendanceStatus::Present]);
        recordSession($classroom, '2026-08-31', [$a->id => AttendanceStatus::Absent, $b->id => AttendanceStatus::Absent]);
        $this->actingAsRole(Role::Operator);

        $this->get("/operator/presensi/rombel/{$classroom->public_id}/rekap?bulan=2026-09")->assertInertia(fn (Assert $page) => $page
            ->component('Attendance::Shared/ClassroomRecap')
            ->where('recap.dates', ['2026-09-24', '2026-09-25'])
            ->where('recap.summary.days', 4)
            ->where('recap.summary.percentage', 75)
            ->where('recap.summary.counts.SAKIT', 1));
    });

    it('guru hanya melihat kalender siswa yang ia ajar', function () {
        $teacher = User::factory()->withRole(Role::Teacher)->create();
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        assignTeaching($classroom, Teacher::factory()->withAccount($teacher)->create());
        $mine = Student::factory()->create();
        enrollIn($classroom, $mine);
        $other = Student::factory()->create();
        $this->actingAsRole(Role::Teacher, $teacher);

        $this->get("/guru/presensi/siswa/{$mine->public_id}")->assertOk();
        $this->get("/guru/presensi/siswa/{$other->public_id}")->assertForbidden();
    });
});

describe('orang tua', function () {
    it('melihat kehadiran hari ini & kalender anaknya; anak lain 403', function () {
        $parent = User::factory()->withRole(Role::Guardian)->create();
        $child = Student::factory()->withGuardian(Guardian::factory()->withAccount($parent)->create())->create();
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        enrollIn($classroom, $child);
        recordSession($classroom, '2026-09-28', [$child->id => AttendanceStatus::Late]);
        recordSession($classroom, '2026-09-25', [$child->id => AttendanceStatus::Present]);
        $stranger = Student::factory()->create();
        $this->actingAsRole(Role::Guardian, $parent);

        $this->get('/orang-tua/presensi')->assertInertia(fn (Assert $page) => $page
            ->component('Attendance::Guardian/Index')
            ->where('today.status', 'TERLAMBAT')
            ->where('calendar.summary.days', 2)
            ->where('calendar.summary.percentage', 100));

        $this->get("/orang-tua/presensi?anak={$stranger->public_id}")->assertForbidden();
    });

    it('role lain tidak bisa membuka halaman presensi orang tua', function () {
        $this->actingAsRole(Role::Teacher);

        $this->get('/orang-tua/presensi')->assertForbidden();
    });
});

describe('kepala sekolah', function () {
    it('melihat statistik per rombel lalu per siswa (read-only)', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create(['name' => 'VII-A']);
        [$a, $b] = Student::factory()->count(2)->create()->all();
        enrollIn($classroom, $a);
        enrollIn($classroom, $b);
        recordSession($classroom, '2026-09-25', [$a->id => AttendanceStatus::Present, $b->id => AttendanceStatus::Absent]);
        $this->actingAsRole(Role::Principal);

        $this->get('/kepala-sekolah/presensi?dari=2026-09-01&sampai=2026-09-28')->assertInertia(fn (Assert $page) => $page
            ->component('Attendance::Principal/Index')
            ->where('classrooms.0.name', 'VII-A')
            ->where('classrooms.0.summary.percentage', 50)
            ->where('classrooms.0.summary.counts.ALPA', 1));

        $this->get("/kepala-sekolah/presensi/rombel/{$classroom->public_id}?dari=2026-09-01&sampai=2026-09-28")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance::Principal/Classroom')
                ->has('students', 2));

        $this->get("/kepala-sekolah/presensi/siswa/{$b->public_id}?bulan=2026-09")->assertOk();
        $this->get("/kepala-sekolah/presensi/rombel/{$classroom->public_id}/rekap?bulan=2026-09")->assertOk();
    });

    it('tidak bisa mengisi presensi', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $this->actingAsRole(Role::Principal);

        $this->put("/operator/presensi/rombel/{$classroom->public_id}", [])->assertForbidden();
        $this->put("/guru/presensi/rombel/{$classroom->public_id}", [])->assertForbidden();
    });
});
