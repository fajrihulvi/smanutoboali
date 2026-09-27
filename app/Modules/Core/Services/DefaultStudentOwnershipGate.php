<?php

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Enums\Role;

/**
 * Implementasi sementara sebelum tabel siswa ada (S10).
 *
 * Operator dan kepala sekolah melihat semua siswa; guru dan orang tua DITOLAK
 * (deny-by-default) karena relasi guardian_student & teaching_assignments belum ada.
 */
class DefaultStudentOwnershipGate implements StudentOwnershipGate
{
    public function canViewStudent(User $user, int $studentId): bool
    {
        return $user->hasRoleEnum(Role::Operator) || $user->hasRoleEnum(Role::Principal);
    }
}
