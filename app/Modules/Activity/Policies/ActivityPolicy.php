<?php

namespace App\Modules\Activity\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\Core\Enums\Role;

/**
 * Lihat: bila terlihat di salah satu role milik user (lihat ActivityVisibility). Juga dipakai FilePolicy untuk foto.
 * Kelola: pembuat (activity.manage) atau operator.
 */
class ActivityPolicy
{
    public function __construct(private ActivityVisibility $visibility) {}

    public function manageAny(User $user): bool
    {
        return $user->can(Permission::ActivityManage->value)
            && ($user->hasRoleEnum(Role::Operator) || $user->hasRoleEnum(Role::Teacher));
    }

    public function view(User $user, Activity $activity): bool
    {
        foreach ([Role::Operator, Role::Principal, Role::Teacher, Role::Guardian] as $role) {
            if ($user->hasRoleEnum($role) && $this->visibility->forUser($user, $role)->whereKey($activity->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->can(Permission::ActivityManage->value)
            && ($activity->created_by === $user->id || $user->hasRoleEnum(Role::Operator));
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }
}
