<?php

namespace App\Modules\Access\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Access\Actions\UpdateRolePermissionsAction;
use App\Modules\Access\Enums\Permission;
use App\Modules\Access\Http\Requests\UpdateRolePermissionsRequest;
use App\Modules\Access\Http\Resources\RoleResource;
use App\Modules\Core\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role as RoleModel;

class RoleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', RoleModel::class);

        $order = array_map(fn (Role $role) => $role->value, Role::cases());

        $roles = RoleModel::query()
            ->withCount(['users', 'permissions'])
            ->get()
            ->sortBy(fn (RoleModel $role) => array_search($role->name, $order, true))
            ->values();

        return Inertia::render('Access::Operator/Roles/Index', [
            'roles' => RoleResource::collection($roles),
            'totalPermissions' => count(Permission::cases()),
        ]);
    }

    public function edit(RoleModel $role): Response
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        return Inertia::render('Access::Operator/Roles/Edit', [
            'role' => (new RoleResource($role))->resolve(request()),
            'groups' => $this->permissionGroups(),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, RoleModel $role, UpdateRolePermissionsAction $updatePermissions): RedirectResponse
    {
        $updatePermissions->handle($role, $request->permissions());

        return to_route('operator.roles.edit', $role->name)->with('success', 'Permission role berhasil disimpan.');
    }

    /**
     * @return list<array{group: string, permissions: list<array{name: string, label: string}>}>
     */
    private function permissionGroups(): array
    {
        $groups = [];

        foreach (Permission::cases() as $permission) {
            $groups[$permission->group()][] = ['name' => $permission->value, 'label' => $permission->label()];
        }

        return array_map(
            fn (string $group, array $permissions) => ['group' => $group, 'permissions' => $permissions],
            array_keys($groups),
            array_values($groups),
        );
    }
}
