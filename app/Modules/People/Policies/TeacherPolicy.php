<?php

namespace App\Modules\People\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;

class TeacherPolicy
{
    /**
     * Operator (master data) dan kepala sekolah (monitoring, read-only).
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value) || $user->hasRoleEnum(Role::Principal);
    }

    /**
     * Dipakai juga FilePolicy untuk foto guru.
     */
    public function view(User $user, Teacher $teacher): bool
    {
        return $this->viewAny($user) || $teacher->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    /**
     * Guru mengubah profilnya sendiri (kolom terbatas).
     */
    public function updateOwnProfile(User $user, Teacher $teacher): bool
    {
        return $teacher->user_id === $user->getKey();
    }
}
