<?php

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Guru (akun + profil) yang menjadi wali di satu rombel dan mengajar di rombel lain; satu rombel lain bukan miliknya.
 *
 * @return array{user: User, homeroom: Classroom, taught: Classroom, other: Classroom}
 */
function teacherWithClasses(): array
{
    $year = activeAcademicYear();
    $user = User::factory()->withRole(Role::Teacher)->create();
    $teacher = Teacher::factory()->withAccount($user)->create();

    $homeroom = Classroom::factory()->for($year)->create(['name' => 'VII-A', 'homeroom_teacher_id' => $teacher->id]);
    $taught = Classroom::factory()->for($year)->create(['name' => 'VII-B']);
    $other = Classroom::factory()->for($year)->create(['name' => 'VII-C']);
    assignTeaching($taught, $teacher);
    assignTeaching($other, Teacher::factory()->create());

    foreach ([$homeroom, $taught, $other] as $classroom) {
        enrollIn($classroom, Student::factory()->create());
    }

    return compact('user', 'homeroom', 'taught', 'other');
}

it('kelas saya berisi rombel wali dan rombel yang diajar', function () {
    ['user' => $user] = teacherWithClasses();
    $this->actingAsRole(Role::Teacher, $user);

    $this->get('/guru/kelas')->assertInertia(fn (Assert $page) => $page
        ->component('Academic::Teacher/Classes/Index')
        ->where('hasTeacherProfile', true)
        ->has('classrooms', 2)
        ->where('classrooms.0.name', 'VII-A')
        ->where('classrooms.0.is_homeroom', true)
        ->where('classrooms.1.is_homeroom', false)
        ->has('classrooms.1.subjects', 1));
});

it('guru membuka rombelnya tetapi tidak rombel lain (403)', function () {
    ['user' => $user, 'homeroom' => $homeroom, 'taught' => $taught, 'other' => $other] = teacherWithClasses();
    $this->actingAsRole(Role::Teacher, $user);

    $this->get("/guru/kelas/{$homeroom->public_id}")->assertInertia(fn (Assert $page) => $page
        ->component('Academic::Teacher/Classes/Show')
        ->has('students', 1));
    $this->get("/guru/kelas/{$taught->public_id}")->assertOk();
    $this->get("/guru/kelas/{$other->public_id}")->assertForbidden();
});

it('daftar siswa guru hanya siswa di rombel yang diampu', function () {
    ['user' => $user, 'other' => $other] = teacherWithClasses();
    $this->actingAsRole(Role::Teacher, $user);

    $otherStudent = $other->enrollments()->sole()->student;

    $this->get('/guru/siswa')->assertInertia(fn (Assert $page) => $page
        ->component('Academic::Teacher/Students/Index')
        ->has('students.data', 2)
        ->where('students.data', fn ($rows) => collect($rows)->pluck('public_id')->doesntContain($otherStudent->public_id))
        ->missing('students.data.0.id'));
});

it('akun guru tanpa profil guru melihat keadaan kosong', function () {
    activeAcademicYear();
    $this->actingAsRole(Role::Teacher);

    $this->get('/guru/kelas')->assertInertia(fn (Assert $page) => $page
        ->where('hasTeacherProfile', false)
        ->has('classrooms', 0));
});

it('tanpa tahun ajaran aktif, kelas saya kosong tanpa error', function () {
    $user = User::factory()->withRole(Role::Teacher)->create();
    Teacher::factory()->withAccount($user)->create();
    $this->actingAsRole(Role::Teacher, $user);

    $this->get('/guru/kelas')->assertInertia(fn (Assert $page) => $page
        ->where('hasTeacherProfile', true)
        ->has('classrooms', 0));
});

it('rombel tahun ajaran lain tidak muncul', function () {
    ['user' => $user] = teacherWithClasses();
    $teacher = Teacher::query()->where('user_id', $user->id)->sole();
    $old = Classroom::factory()->for(AcademicYear::factory()->startingIn(2020)->create())->create();
    assignTeaching($old, $teacher);
    enrollIn($old, Student::factory()->create(), EnrollmentStatus::Completed);
    $this->actingAsRole(Role::Teacher, $user);

    $this->get('/guru/kelas')->assertInertia(fn (Assert $page) => $page->has('classrooms', 2));
    $this->get("/guru/kelas/{$old->public_id}")->assertOk();
});

it('role lain tidak dapat membuka portal guru', function (Role $role) {
    ['homeroom' => $homeroom] = teacherWithClasses();
    $this->actingAsRole($role);

    $this->get('/guru/kelas')->assertForbidden();
    $this->get("/guru/kelas/{$homeroom->public_id}")->assertForbidden();
})->with([Role::Operator, Role::Guardian]);
