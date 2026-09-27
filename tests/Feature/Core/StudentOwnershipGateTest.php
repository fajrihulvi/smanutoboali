<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;

/**
 * Kontrak Core (S05) kini diimplementasikan modul People dengan Student::visibleTo().
 * Skenario orang tua ada di tests/Feature/People/StudentVisibilityTest.php.
 */
it('mengizinkan operator dan kepala sekolah melihat siswa', function (Role $role) {
    $student = Student::factory()->create();
    $user = User::factory()->withRole($role)->create();

    expect(app(StudentOwnershipGate::class)->canViewStudent($user, $student->id))->toBeTrue();
})->with([Role::Operator, Role::Principal]);

it('menolak guru yang tidak mengajar siswa dan orang tua yang tidak tertaut', function (Role $role) {
    $student = Student::factory()->create();
    $user = User::factory()->withRole($role)->create();

    expect(app(StudentOwnershipGate::class)->canViewStudent($user, $student->id))->toBeFalse();
})->with([Role::Teacher, Role::Guardian]);

it('mengizinkan guru yang mengajar di rombel siswa', function () {
    $classroom = Classroom::factory()->for(activeAcademicYear())->create();
    $user = User::factory()->withRole(Role::Teacher)->create();
    assignTeaching($classroom, Teacher::factory()->withAccount($user)->create());
    $student = Student::factory()->create();
    enrollIn($classroom, $student);
    $this->actingAsRole(Role::Teacher, $user);

    expect(app(StudentOwnershipGate::class)->canViewStudent($user, $student->id))->toBeTrue();
});
