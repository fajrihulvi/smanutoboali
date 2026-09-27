<?php

use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use Inertia\Testing\AssertableInertia as Assert;

it('menolak akses /guru/dashboard dengan role aktif orang tua (403)', function () {
    $this->actingAsRole(Role::Guardian);

    $this->get('/guru/dashboard')->assertForbidden();
});

it('menolak akses portal lain walau user memiliki kedua role tanpa beralih', function () {
    $user = User::factory()->withRole(Role::Teacher, Role::Guardian)->create();
    $this->actingAsRole(Role::Guardian, $user);

    $this->get('/guru/dashboard')->assertForbidden();
    $this->get('/orang-tua/dashboard')->assertOk();
});

it('mengarahkan tamu ke halaman pilihan portal', function () {
    $this->get('/guru/dashboard')->assertRedirect('/login');
});

it('membagikan role, role aktif, dan user tanpa id ke Inertia', function () {
    $user = User::factory()->withRole(Role::Teacher, Role::Guardian)->create();
    $this->actingAsRole(Role::Teacher, $user);

    $this->get('/guru/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard::Teacher/Index')
            ->where('auth.activeRole', 'teacher')
            ->where('auth.user.public_id', $user->public_id)
            ->missing('auth.user.id')
            ->has('auth.roles', 2)
            ->where('auth.roles.0.code', 'teacher')
            ->where('auth.roles.1.label', 'Orang Tua'));
});

it('/dashboard mengarah ke dashboard role aktif', function () {
    $this->actingAsRole(Role::Principal);

    $this->get('/dashboard')->assertRedirect('/kepala-sekolah/dashboard');
});

it('sesi tanpa role aktif (cookie ingat saya) memakai role portal yang dimiliki', function () {
    $user = User::factory()->withRole(Role::Teacher)->create();

    $this->actingAs($user)->get('/guru/dashboard')->assertOk();
    expect(session(ActiveRoleService::SESSION_KEY))->toBe('teacher');
});

it('mengeluarkan user yang dinonaktifkan saat sesi masih berjalan', function () {
    $user = $this->actingAsRole(Role::Teacher);
    $user->forceFill(['is_active' => false])->save();

    $this->get('/guru/dashboard')
        ->assertRedirect('/login')
        ->assertSessionHas('error');

    $this->assertGuest();
});

describe('role switcher', function () {
    it('mengganti role aktif ke role yang dimiliki', function () {
        $user = User::factory()->withRole(Role::Teacher, Role::Guardian)->create();
        $this->actingAsRole(Role::Teacher, $user);

        $this->post('/switch-role/guardian')
            ->assertRedirect('/orang-tua/dashboard')
            ->assertSessionHas(ActiveRoleService::SESSION_KEY, 'guardian');

        $this->get('/orang-tua/dashboard')->assertOk();
        $this->get('/guru/dashboard')->assertForbidden();
    });

    it('menolak beralih ke role yang tidak dimiliki', function () {
        $this->actingAsRole(Role::Teacher);

        $this->post('/switch-role/operator')->assertForbidden();
        $this->post('/switch-role/bukan-role')->assertForbidden();

        expect(session(ActiveRoleService::SESSION_KEY))->toBe('teacher');
    });
});

describe('logout', function () {
    it('kembali ke halaman login portal role terakhir', function (Role $role, string $slug) {
        $this->actingAsRole($role);

        $this->post('/logout')->assertRedirect("/login/{$slug}");
        $this->assertGuest();
    })->with([
        [Role::Teacher, 'guru'],
        [Role::Guardian, 'orang-tua'],
    ]);
});
