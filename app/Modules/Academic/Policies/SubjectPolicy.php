<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Access\Enums\Permission;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }
}
