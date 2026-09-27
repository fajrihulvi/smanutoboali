<?php

use App\Models\User;
use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;

/**
 * Kontrak Core (S05) kini diimplementasikan modul People dengan Student::visibleTo().
 * Skenario orang tua ada di tests/Feature/People/StudentVisibilityTest.php.
 */
it('mengizinkan operator dan kepala sekolah melihat siswa', function (Role $role) {
    $student = Student::factory()->create();
    $user = User::factory()->withRole($role)->create();

    expect(app(StudentOwnershipGate::class)->canViewStudent($user, $student->id))->toBeTrue();
})->with([Role::Operator, Role::Principal]);

it('menolak guru (sampai S11) dan orang tua yang tidak tertaut', function (Role $role) {
    $student = Student::factory()->create();
    $user = User::factory()->withRole($role)->create();

    expect(app(StudentOwnershipGate::class)->canViewStudent($user, $student->id))->toBeFalse();
})->with([Role::Teacher, Role::Guardian]);
