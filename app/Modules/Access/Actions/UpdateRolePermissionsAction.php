<?php

namespace App\Modules\Access\Actions;

use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class UpdateRolePermissionsAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<string>  $permissions
     *
     * @throws ValidationException
     */
    public function handle(RoleModel $role, array $permissions): RoleModel
    {
        // Cegah operator terkunci dari halaman role & permission.
        if ($role->name === Role::Operator->value && ! in_array(Permission::RoleManage->value, $permissions, true)) {
            throw ValidationException::withMessages([
                'permissions' => 'Permission "Kelola role & permission" tidak boleh dicabut dari role Operator.',
            ]);
        }

        $old = $role->permissions->pluck('name')->sort()->values()->all();

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $new = collect($permissions)->sort()->values()->all();

        if ($old !== $new) {
            $this->audit->record('access', 'permissions_updated', "Mengubah permission role {$role->name}", $role, [
                'granted' => array_values(array_diff($new, $old)),
                'revoked' => array_values(array_diff($old, $new)),
            ]);
        }

        return $role;
    }
}
