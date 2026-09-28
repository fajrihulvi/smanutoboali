<?php

namespace App\Modules\Portfolio\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Models\Portfolio;

/**
 * - Operator (portfolio.manage): semua.
 * - Guru (portfolio.manage): kelola buatannya sendiri; lihat bila ia pembuat atau siswa terkait terlihat olehnya.
 * - Kepala sekolah: lihat yang terbit.
 * - Orang tua: lihat yang terbit bila minimal satu siswa terkait adalah anaknya.
 * Dipakai juga FilePolicy untuk file portofolio.
 */
class PortfolioPolicy
{
    public function manageAny(User $user): bool
    {
        return $user->can(Permission::PortfolioManage->value)
            && ($user->hasRoleEnum(Role::Operator) || $user->hasRoleEnum(Role::Teacher));
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        if ($this->isOperator($user) || $portfolio->created_by === $user->id) {
            return true;
        }

        $published = $portfolio->visibility === PortfolioVisibility::Published;

        if ($user->hasRoleEnum(Role::Principal)) {
            return $published;
        }

        if ($user->hasRoleEnum(Role::Guardian) && $published && $this->linkedTo($user, $portfolio, Role::Guardian)) {
            return true;
        }

        return $user->hasRoleEnum(Role::Teacher) && $this->linkedTo($user, $portfolio, Role::Teacher);
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $this->isOperator($user)
            || ($user->can(Permission::PortfolioManage->value) && $portfolio->created_by === $user->id);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->update($user, $portfolio);
    }

    private function isOperator(User $user): bool
    {
        return $user->hasRoleEnum(Role::Operator) && $user->can(Permission::PortfolioManage->value);
    }

    private function linkedTo(User $user, Portfolio $portfolio, Role $as): bool
    {
        return Student::query()
            ->visibleTo($user, $as)
            ->whereIn('students.id', $portfolio->students()->pluck('students.id'))
            ->exists();
    }
}
