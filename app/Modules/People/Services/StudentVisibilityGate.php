<?php

namespace App\Modules\People\Services;

use App\Models\User;
use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\People\Models\Student;

/**
 * Implementasi kontrak Core (S05) dengan scope Student::visibleTo().
 */
class StudentVisibilityGate implements StudentOwnershipGate
{
    public function canViewStudent(User $user, int $studentId): bool
    {
        return Student::query()->visibleTo($user)->whereKey($studentId)->exists();
    }
}
