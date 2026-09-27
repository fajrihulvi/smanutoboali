<?php

namespace App\Modules\People\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Policies\Concerns\ChecksStudentOwnership;
use App\Modules\People\Models\Student;

/**
 * Dua lapis: permission (student.view / master_data.manage) + ownership (visibleTo).
 */
class StudentPolicy
{
    use ChecksStudentOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::StudentView->value);
    }

    /**
     * Dipakai juga FilePolicy untuk foto siswa.
     */
    public function view(User $user, Student $student): bool
    {
        return $user->can(Permission::StudentView->value)
            && $this->canViewStudent($user, $student->getKey());
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }
}
