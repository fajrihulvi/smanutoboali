<?php

namespace App\Modules\School\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\School\Models\AcademicYear;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function update(User $user, AcademicYear $year): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function activate(User $user, AcademicYear $year): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    /**
     * Tahun ajaran aktif tidak boleh dihapus.
     */
    public function delete(User $user, AcademicYear $year): bool
    {
        return $user->can(Permission::MasterDataManage->value) && ! $year->is_active;
    }
}
