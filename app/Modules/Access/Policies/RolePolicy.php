<?php

namespace App\Modules\Access\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use Spatie\Permission\Models\Role as RoleModel;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::RoleManage->value);
    }

    public function update(User $actor, RoleModel $role): bool
    {
        return $actor->can(Permission::RoleManage->value);
    }

    /**
     * Role inti tidak pernah boleh dihapus.
     */
    public function delete(User $actor, RoleModel $role): bool
    {
        return $actor->can(Permission::RoleManage->value) && Role::tryFrom($role->name) === null;
    }
}
