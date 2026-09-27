<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;

/**
 * Jadwal disusun operator; guru hanya melihat jadwalnya (lewat halaman guru),
 * orang tua & kepala sekolah hanya membaca.
 */
class SchedulePolicy
{
    public function manage(User $user): bool
    {
        return $user->can(Permission::AcademicManage->value) && $user->hasRoleEnum(Role::Operator);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $this->manage($user);
    }
}
