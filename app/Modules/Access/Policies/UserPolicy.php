<?php

namespace App\Modules\Access\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::UserManage->value);
    }

    /**
     * Data akun: diri sendiri atau pengelola user (dipakai juga FilePolicy
     * untuk file milik user, mis. foto profil).
     */
    public function view(User $actor, User $user): bool
    {
        return $actor->is($user) || $actor->can(Permission::UserManage->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::UserManage->value);
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can(Permission::UserManage->value);
    }

    public function resetPassword(User $actor, User $user): bool
    {
        return $actor->can(Permission::UserManage->value);
    }

    /**
     * Operator tidak boleh mengubah status aktif akunnya sendiri.
     */
    public function toggleStatus(User $actor, User $user): bool
    {
        return $actor->can(Permission::UserManage->value) && ! $actor->is($user);
    }
}
