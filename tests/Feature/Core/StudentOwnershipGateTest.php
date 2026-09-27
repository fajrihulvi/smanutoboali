<?php

use App\Models\User;
use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Enums\Role;

/**
 * Kontrak sementara sebelum S10: guru & orang tua deny-by-default.
 */
it('mengizinkan operator dan kepala sekolah melihat siswa', function (Role $role) {
    $user = User::factory()->withRole($role)->create();

    expect(app(StudentOwnershipGate::class)->canViewStudent($user, 1))->toBeTrue();
})->with([Role::Operator, Role::Principal]);

it('menolak guru dan orang tua sampai scope visibleTo tersedia', function (Role $role) {
    $user = User::factory()->withRole($role)->create();

    expect(app(StudentOwnershipGate::class)->canViewStudent($user, 1))->toBeFalse();
})->with([Role::Teacher, Role::Guardian]);
