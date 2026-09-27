<?php

use App\Models\User;
use App\Modules\Access\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * @return array<string, list<string>>
 */
function expectedMatrix(): array
{
    return json_decode((string) file_get_contents(base_path('tests/fixtures/role-permissions.json')), true);
}

it('enum Permission memuat default yang sama dengan fixture matriks', function (Role $role) {
    $defaults = Permission::defaultsFor($role);
    sort($defaults);

    expect($defaults)->toBe(expectedMatrix()[$role->value]);
})->with(Role::cases());

it('seeder membuat 5 role inti dan semua permission', function () {
    expect(RoleModel::pluck('name')->sort()->values()->all())
        ->toBe(['applicant', 'guardian', 'operator', 'principal', 'teacher'])
        ->and(PermissionModel::count())->toBe(count(Permission::cases()));
});

it('permission tiap role sesuai matriks setelah seeding', function (Role $role) {
    $names = RoleModel::findByName($role->value)->permissions->pluck('name')->sort()->values()->all();

    expect($names)->toBe(expectedMatrix()[$role->value]);
})->with(Role::cases());

it('seeder idempoten dan tidak mencabut perubahan operator', function () {
    $teacher = RoleModel::findByName('teacher');
    $teacher->revokePermissionTo('chat.use');
    $teacher->givePermissionTo('finance.view');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    $names = $teacher->fresh()->permissions->pluck('name');

    expect(RoleModel::count())->toBe(5)
        ->and(PermissionModel::count())->toBe(count(Permission::cases()))
        ->and($names)->not->toContain('chat.use')
        ->and($names)->toContain('finance.view');
});

it('membagikan permission role aktif ke Inertia', function (Role $role, string $dashboard) {
    $this->actingAsRole($role);

    $this->get($dashboard)->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', expectedMatrix()[$role->value]));
})->with([
    [Role::Principal, '/kepala-sekolah/dashboard'],
    [Role::Teacher, '/guru/dashboard'],
    [Role::Operator, '/operator/dashboard'],
    [Role::Guardian, '/orang-tua/dashboard'],
]);

it('user multi-role hanya menerima permission role aktif untuk menu', function () {
    $this->actingAsRole(Role::Guardian, User::factory()->withRole(Role::Teacher, Role::Guardian)->create());

    $this->get('/orang-tua/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', expectedMatrix()['guardian']));
});

describe('middleware permission', function () {
    beforeEach(function () {
        Route::middleware(['web', 'auth', 'active.role:operator', 'permission:user.manage'])
            ->get('/_test/operator/users', fn () => 'ok');

        Route::middleware(['web', 'auth', 'active.role:teacher', 'permission:finance.manage'])
            ->get('/_test/guru/keuangan', fn () => 'ok');
    });

    it('mengizinkan role yang memiliki permission', function () {
        $this->actingAsRole(Role::Operator);

        $this->get('/_test/operator/users')->assertOk();
    });

    it('menolak route dengan permission yang tidak dimiliki (403)', function () {
        $this->actingAsRole(Role::Teacher);

        $this->get('/_test/guru/keuangan')->assertForbidden();
    });

    it('menolak bila permission dicabut dari role', function () {
        RoleModel::findByName('operator')->revokePermissionTo('user.manage');
        $this->actingAsRole(Role::Operator);

        $this->get('/_test/operator/users')->assertForbidden();
    });
});
