<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Contracts\Session\Session;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Role aktif user disimpan di session ('active_role'), lihat VIBE_PLANNING §4.1.
 */
class ActiveRoleService
{
    public const SESSION_KEY = 'active_role';

    public function current(Session $session): ?Role
    {
        $value = $session->get(self::SESSION_KEY);

        return is_string($value) ? Role::tryFrom($value) : null;
    }

    public function set(Session $session, Role $role): void
    {
        $session->put(self::SESSION_KEY, $role->value);
    }

    /**
     * Role aktif yang sah untuk user ini: role di session bila masih dimiliki,
     * selain itu role inti pertama user (mis. sesi baru dari cookie "ingat saya").
     */
    public function resolve(Session $session, User $user): ?Role
    {
        $current = $this->current($session);

        if ($current && $user->hasRoleEnum($current)) {
            return $current;
        }

        $fallback = $user->coreRoles()[0] ?? null;

        if ($fallback) {
            $this->set($session, $fallback);
        } else {
            $session->forget(self::SESSION_KEY);
        }

        return $fallback;
    }

    /**
     * Permission milik role aktif + permission langsung user (untuk menu & <Can>).
     *
     * @return list<string>
     */
    public function permissionsFor(User $user, ?Role $role): array
    {
        $direct = $user->getDirectPermissions()->pluck('name');

        if (! $role) {
            return $direct->values()->all();
        }

        $rolePermissions = RoleModel::findByName($role->value, 'web')->permissions->pluck('name');

        return $rolePermissions->merge($direct)->unique()->sort()->values()->all();
    }

    public function homeUrl(Role $role): string
    {
        return route($role->dashboardRoute());
    }
}
