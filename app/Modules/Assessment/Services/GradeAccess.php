<?php

namespace App\Modules\Assessment\Services;

use App\Models\User;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;

/**
 * - Kelola nilai: guru pengampu (grade.manage) untuk penugasan tahun ajaran aktif.
 * - Lihat: pengelola, operator dengan grade.view, kepala sekolah.
 */
class GradeAccess
{
    public function __construct(private ActivePeriod $period) {}

    public function canManage(User $user, TeachingAssignment $assignment): bool
    {
        if (! $user->can(Permission::GradeManage->value) || $assignment->academic_year_id !== $this->period->academicYearId()) {
            return false;
        }

        $teacher = Teacher::activeForUser($user);

        return $teacher !== null && $assignment->teacher_id === $teacher->id;
    }

    public function canView(User $user, TeachingAssignment $assignment): bool
    {
        return $this->canManage($user, $assignment)
            || ($user->hasRoleEnum(Role::Operator) && $user->can(Permission::GradeView->value))
            || $user->hasRoleEnum(Role::Principal);
    }
}
