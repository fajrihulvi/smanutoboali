<?php

namespace App\Modules\Core\Policies\Concerns;

use App\Models\User;
use App\Modules\Core\Contracts\StudentOwnershipGate;

/**
 * Helper untuk Policy modul yang datanya milik siswa. Contoh:
 *
 *  public function view(User $user, AttendanceRecord $record): bool
 *  {
 *      return $user->can('attendance.manage')          // lapis 1: permission
 *          && $this->canViewStudent($user, $record->student_id); // lapis 2: ownership
 *  }
 */
trait ChecksStudentOwnership
{
    protected function canViewStudent(User $user, int $studentId): bool
    {
        return app(StudentOwnershipGate::class)->canViewStudent($user, $studentId);
    }
}
