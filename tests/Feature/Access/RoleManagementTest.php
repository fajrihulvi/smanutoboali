<?php

use App\Modules\Core\Enums\Role;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role as RoleModel;

it('operator melihat daftar 5 role inti beserta jumlah permission', function () {
    $this->actingAsRole(Role::Operator);

    $this->get('/operator/roles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Access::Operator/Roles/Index')
            ->has('roles.data', 5)
            ->where('roles.data.0.code', 'principal')
            ->where('roles.data.0.is_core', true)
            ->where('roles.data.2.permissions_count', 18));
});

it('menampilkan permission role dikelompokkan per modul', function () {
    $this->actingAsRole(Role::Operator);

    $this->get('/operator/roles/teacher/edit')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Access::Operator/Roles/Edit')
            ->where('role.code', 'teacher')
            ->has('role.permissions', 10)
            ->where('groups.0.group', 'Master Data')
            ->has('groups.0.permissions.0.label'));
});

it('menyimpan permission role dan mencatat audit log', function () {
    $this->actingAsRole(Role::Operator);

    $this->put('/operator/roles/guardian', ['permissions' => ['chat.use', 'student.view']])
        ->assertRedirect('/operator/roles/guardian/edit')
        ->assertSessionHas('success');

    expect(RoleModel::findByName('guardian')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['chat.use', 'student.view']);

    $log = Activity::query()->where('event', 'permissions_updated')->firstOrFail();
    expect($log->properties['revoked'])->toContain('finance.view');
});

it('menolak permission yang tidak dikenal', function () {
    $this->actingAsRole(Role::Operator);

    $this->put('/operator/roles/teacher', ['permissions' => ['hack.everything']])
        ->assertSessionHasErrors('permissions.0');
});

it('tidak mengizinkan role.manage dicabut dari role operator', function () {
    $this->actingAsRole(Role::Operator);

    $this->put('/operator/roles/operator', ['permissions' => ['user.manage']])
        ->assertSessionHasErrors('permissions');

    expect(RoleModel::findByName('operator')->hasPermissionTo('role.manage'))->toBeTrue();
});

it('role lain tidak dapat mengelola role', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/operator/roles')->assertForbidden();
    $this->put('/operator/roles/teacher', ['permissions' => []])->assertForbidden();
})->with([Role::Principal, Role::Teacher, Role::Guardian]);

it('role inti tidak dapat dihapus', function () {
    $operator = $this->actingAsRole(Role::Operator);

    foreach (Role::cases() as $role) {
        expect($operator->can('delete', RoleModel::findByName($role->value)))->toBeFalse();
    }

    $this->delete('/operator/roles/teacher')->assertStatus(405);
});

it('mengembalikan 404 untuk role yang tidak ada', function () {
    $this->actingAsRole(Role::Operator);

    $this->get('/operator/roles/superadmin/edit')->assertNotFound();
});
