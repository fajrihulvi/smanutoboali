<?php

namespace App\Modules\People\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\People\Models\Guardian;

class GuardianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    /**
     * TODO(S10/S11): guru wali kelas/pengajar anaknya boleh melihat kontak orang tua.
     */
    public function view(User $user, Guardian $guardian): bool
    {
        return $this->viewAny($user) || $guardian->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function update(User $user, Guardian $guardian): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function delete(User $user, Guardian $guardian): bool
    {
        return $user->can(Permission::MasterDataManage->value);
    }

    public function updateOwnProfile(User $user, Guardian $guardian): bool
    {
        return $guardian->user_id === $user->getKey();
    }
}
