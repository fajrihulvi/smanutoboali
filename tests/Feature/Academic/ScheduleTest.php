<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\Subject;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

/**
 * @return array<string, string|null>
 */
function slot(string $assignment, int $day = 1, string $start = '07:30', string $end = '08:10', ?string $room = null): array
{
    return ['teaching_assignment' => $assignment, 'day_of_week' => (string) $day, 'start_time' => $start, 'end_time' => $end, 'room' => $room];
}

describe('editor jadwal operator', function () {
    it('menampilkan grid rombel terpilih beserta pilihan mapel', function () {
        $year = activeAcademicYear();
        $classroom = Classroom::factory()->for($year)->create(['name' => 'VII-A']);
        $assignment = assignTeaching($classroom, Teacher::factory()->create());
        scheduleAt($assignment, 2, '08:10', '08:50');
        $this->actingAsRole(Role::Operator);

        $this->get("/operator/akademik/jadwal?rombel={$classroom->public_id}")->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Operator/Schedules/Index')
            ->where('classroom.name', 'VII-A')
            ->has('schedules', 1)
            ->where('schedules.0.day_label', 'Selasa')
            ->where('schedules.0.start_time', '08:10')
            ->has('assignments', 1)
            ->missing('schedules.0.id'));
    });

    it('menambah, mengubah, dan menghapus jadwal (tercatat di audit log)', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $assignment = assignTeaching($classroom, Teacher::factory()->create());
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/akademik/jadwal', slot($assignment->public_id, 1, '07:30', '08:10', 'Lab'))->assertSessionHasNoErrors();
        $schedule = Schedule::query()->sole();

        $this->put("/operator/akademik/jadwal/{$schedule->public_id}", slot($assignment->public_id, 3, '09:00', '09:40'))->assertSessionHasNoErrors();
        expect($schedule->fresh())->day_of_week->value->toBe(3)->start_time->toBe('09:00:00')->room->toBeNull();

        $this->delete("/operator/akademik/jadwal/{$schedule->public_id}")->assertSessionHasNoErrors();
        expect(Schedule::query()->count())->toBe(0)
            ->and(Schedule::withTrashed()->count())->toBe(1)
            ->and(Activity::query()->where('log_name', 'academic')->pluck('event')->all())
            ->toBe(['schedule_created', 'schedule_updated', 'schedule_deleted']);
    });

    it('menolak jam selesai sebelum jam mulai', function () {
        $assignment = assignTeaching(Classroom::factory()->for(activeAcademicYear())->create(), Teacher::factory()->create());
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/akademik/jadwal', slot($assignment->public_id, 1, '09:00', '08:00'))->assertSessionHasErrors('end_time');
    });

    it('menolak bentrok rombel', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        scheduleAt(assignTeaching($classroom, Teacher::factory()->create()), 1, '07:30', '08:10');
        $other = assignTeaching($classroom, Teacher::factory()->create());
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/akademik/jadwal', slot($other->public_id, 1, '08:00', '08:40'))->assertSessionHasErrors('start_time');
        // Bersebelahan (selesai = mulai) tidak dianggap bentrok.
        $this->post('/operator/akademik/jadwal', slot($other->public_id, 1, '08:10', '08:50'))->assertSessionHasNoErrors();
    });

    it('menolak bentrok guru di rombel lain', function () {
        $year = activeAcademicYear();
        $teacher = Teacher::factory()->create();
        scheduleAt(assignTeaching(Classroom::factory()->for($year)->create(), $teacher), 1, '07:30', '08:10');
        $second = assignTeaching(Classroom::factory()->for($year)->create(), $teacher);
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/akademik/jadwal', slot($second->public_id, 1, '07:30', '08:10'))->assertSessionHasErrors('teaching_assignment');
        $this->post('/operator/akademik/jadwal', slot($second->public_id, 2, '07:30', '08:10'))->assertSessionHasNoErrors();
    });

    it('mengubah jadwal tidak bentrok dengan dirinya sendiri', function () {
        $assignment = assignTeaching(Classroom::factory()->for(activeAcademicYear())->create(), Teacher::factory()->create());
        $schedule = scheduleAt($assignment, 1, '07:30', '08:10');
        $this->actingAsRole(Role::Operator);

        $this->put("/operator/akademik/jadwal/{$schedule->public_id}", slot($assignment->public_id, 1, '07:30', '08:30'))->assertSessionHasNoErrors();
    });

    it('role selain operator ditolak', function (Role $role) {
        $schedule = scheduleAt(assignTeaching(Classroom::factory()->for(activeAcademicYear())->create(), Teacher::factory()->create()));
        $this->actingAsRole($role);

        $this->get('/operator/akademik/jadwal')->assertForbidden();
        $this->delete("/operator/akademik/jadwal/{$schedule->public_id}")->assertForbidden();
    })->with([Role::Principal, Role::Teacher, Role::Guardian]);
});

describe('penjaga penugasan', function () {
    it('penugasan yang punya jadwal tidak bisa dikosongkan', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create();
        $subject = Subject::factory()->create();
        scheduleAt(assignTeaching($classroom, Teacher::factory()->create(), $subject));
        $this->actingAsRole(Role::Operator);

        $this->put('/operator/akademik/penugasan', [
            'classroom' => $classroom->public_id, 'subject' => $subject->public_id, 'teacher' => null,
        ])->assertSessionHasErrors('teacher');
    });

    it('guru pengganti yang bentrok jadwal ditolak, yang tidak bentrok diterima', function () {
        $year = activeAcademicYear();
        $classroom = Classroom::factory()->for($year)->create();
        $subject = Subject::factory()->create();
        scheduleAt(assignTeaching($classroom, Teacher::factory()->create(), $subject), 1, '07:30', '08:10');

        $busy = Teacher::factory()->create();
        scheduleAt(assignTeaching(Classroom::factory()->for($year)->create(), $busy), 1, '07:30', '08:10');
        $free = Teacher::factory()->create();
        $this->actingAsRole(Role::Operator);

        $payload = ['classroom' => $classroom->public_id, 'subject' => $subject->public_id];
        $this->put('/operator/akademik/penugasan', [...$payload, 'teacher' => $busy->public_id])->assertSessionHasErrors('teacher');
        $this->put('/operator/akademik/penugasan', [...$payload, 'teacher' => $free->public_id])->assertSessionHasNoErrors();
    });
});

describe('jadwal guru, orang tua, kepala sekolah', function () {
    it('guru hanya melihat jadwal miliknya', function () {
        $year = activeAcademicYear();
        $user = User::factory()->withRole(Role::Teacher)->create();
        $teacher = Teacher::factory()->withAccount($user)->create();
        scheduleAt(assignTeaching(Classroom::factory()->for($year)->create(), $teacher), 1);
        scheduleAt(assignTeaching(Classroom::factory()->for($year)->create(), Teacher::factory()->create()), 1);
        $this->actingAsRole(Role::Teacher, $user);

        $this->get('/guru/akademik/jadwal')->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Teacher/Schedules/Index')
            ->where('hasTeacherProfile', true)
            ->has('schedules', 1));
    });

    it('orang tua melihat jadwal rombel anak terpilih; anak orang lain 403', function () {
        $year = activeAcademicYear();
        $user = User::factory()->withRole(Role::Guardian)->create();
        $guardian = Guardian::factory()->withAccount($user)->create();
        [$first, $second] = collect(['Adik Satu', 'Budi Dua'])
            ->map(fn (string $name) => Student::factory()->withGuardian($guardian)->create(['name' => $name]))->all();

        $classA = Classroom::factory()->for($year)->create(['name' => 'VII-A']);
        $classB = Classroom::factory()->for($year)->create(['name' => 'VIII-A']);
        enrollIn($classA, $first);
        enrollIn($classB, $second);
        scheduleAt(assignTeaching($classA, Teacher::factory()->create()), 1);
        scheduleAt(assignTeaching($classB, Teacher::factory()->create()), 2);
        scheduleAt(assignTeaching($classB, Teacher::factory()->create()), 3);

        $stranger = Student::factory()->create();
        $this->actingAsRole(Role::Guardian, $user);

        $this->get('/orang-tua/akademik')->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Guardian/Academic/Index')
            ->has('childOptions', 2)
            ->where('activeChild', $first->public_id)
            ->where('classroom.name', 'VII-A')
            ->has('schedules', 1)
            ->has('subjects', 1));

        $this->get("/orang-tua/akademik?anak={$second->public_id}")->assertInertia(fn (Assert $page) => $page
            ->where('classroom.name', 'VIII-A')
            ->has('schedules', 2));

        $this->get("/orang-tua/akademik?anak={$stranger->public_id}")->assertForbidden();
    });

    it('kepala sekolah melihat ringkasan jam per minggu dan mapel belum terjadwal', function () {
        $classroom = Classroom::factory()->for(activeAcademicYear())->create(['name' => 'VII-A']);
        $scheduled = Subject::factory()->create(['name' => 'Matematika']);
        $unscheduled = Subject::factory()->create(['name' => 'Seni Budaya']);
        $assignment = assignTeaching($classroom, Teacher::factory()->create(), $scheduled);
        assignTeaching($classroom, Teacher::factory()->create(), $unscheduled);
        scheduleAt($assignment, 1, '07:30', '08:10');
        scheduleAt($assignment, 2, '07:30', '08:50');
        $this->actingAsRole(Role::Principal);

        $this->get('/kepala-sekolah/akademik')->assertInertia(fn (Assert $page) => $page
            ->component('Academic::Principal/Academic/Index')
            ->where('classrooms.0.minutes_per_week', 120)
            ->where('classrooms.0.unscheduled_subjects', ['Seni Budaya'])
            ->has('schedules', 2));
    });

    it('portal akademik hanya untuk role masing-masing', function () {
        $this->actingAsRole(Role::Teacher);
        $this->get('/orang-tua/akademik')->assertForbidden();
        $this->get('/kepala-sekolah/akademik')->assertForbidden();

        $this->actingAsRole(Role::Guardian);
        $this->get('/guru/akademik/jadwal')->assertForbidden();
    });
});
