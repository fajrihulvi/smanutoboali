<?php

namespace App\Modules\GuestBook\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\GuestBook\Models\GuestVisit;

/**
 * Operator mengelola buku tamu; kepala sekolah hanya melihat.
 */
class GuestVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::GuestBookManage->value) || $user->can(Permission::GuestBookView->value);
    }

    public function view(User $user, GuestVisit $visit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::GuestBookManage->value);
    }

    public function update(User $user, GuestVisit $visit): bool
    {
        return $user->can(Permission::GuestBookManage->value);
    }

    public function delete(User $user, GuestVisit $visit): bool
    {
        return $user->can(Permission::GuestBookManage->value);
    }
}
