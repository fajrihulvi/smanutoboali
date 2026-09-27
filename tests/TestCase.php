<?php

namespace Tests;

use App\Models\User;
use App\Modules\Access\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Role & permission inti tersedia di setiap test yang memakai RefreshDatabase.
     */
    protected bool $seed = true;

    protected string $seeder = RolesAndPermissionsSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Login sebagai user dengan role tertentu (role aktif ikut diset di session).
     */
    protected function actingAsRole(Role $role, ?User $user = null): User
    {
        $user ??= User::factory()->withRole($role)->create();

        $this->actingAs($user)->withSession([ActiveRoleService::SESSION_KEY => $role->value]);

        return $user;
    }
}
