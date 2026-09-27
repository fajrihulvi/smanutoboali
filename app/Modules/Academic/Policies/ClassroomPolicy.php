<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    /**
     * Operator, kepala sekolah, atau guru yang mengajar/menjadi wali kelas di rombel ini.
     */
    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->can(Permission::MasterDataManage->value) || $user->hasRoleEnum(Role::Principal)) {
            return true;
        }

        $teacher = Teacher::query()->where('user_id', $user->getKey())->where('is_active', true)->first();

        return $teacher !== null && $classroom->isTaughtBy($teacher);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }
}
