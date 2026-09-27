<?php

namespace Tests;

use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
