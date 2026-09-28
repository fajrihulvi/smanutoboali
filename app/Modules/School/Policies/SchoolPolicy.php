<?php

namespace App\Modules\School\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\School\Models\School;

/**
 * Dipakai FilePolicy untuk file privat milik sekolah (mis. tanda tangan kepala sekolah).
 */
class SchoolPolicy
{
    public function view(User $user, School $school): bool
    {
        return $user->can(Permission::SettingManage->value);
    }
}
