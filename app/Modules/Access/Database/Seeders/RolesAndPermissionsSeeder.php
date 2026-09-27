<?php

namespace App\Modules\Access\Database\Seeders;

use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role inti + permission. Idempoten dan aman untuk produksi:
 * tidak mencabut permission yang sudah diubah operator lewat UI.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var array<string, RoleModel> $roles */
        $roles = [];
        $newRoles = [];

        foreach (Role::cases() as $role) {
            $model = RoleModel::findOrCreate($role->value, 'web');
            $roles[$role->value] = $model;

            if ($model->wasRecentlyCreated) {
                $newRoles[] = $role;
            }
        }

        foreach (Permission::cases() as $permission) {
            $model = PermissionModel::findOrCreate($permission->value, 'web');

            // Permission baru langsung diberikan ke role default-nya.
            if ($model->wasRecentlyCreated) {
                foreach ($permission->defaultRoles() as $role) {
                    $roles[$role->value]->givePermissionTo($model);
                }
            }
        }

        // Role baru mendapat set default lengkap.
        foreach ($newRoles as $role) {
            $roles[$role->value]->syncPermissions(Permission::defaultsFor($role));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
