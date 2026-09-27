<?php

use App\Modules\Core\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

it('menampilkan halaman profil untuk semua role', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/profile'));
})->with([Role::Principal, Role::Teacher, Role::Operator, Role::Guardian]);

it('memperbarui nama dan email', function () {
    $user = $this->actingAsRole(Role::Teacher);

    $this->patch('/settings/profile', ['name' => 'Nama Baru', 'email' => 'baru@skoolia.test'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')
        ->and($user->email)->toBe('baru@skoolia.test')
        ->and($user->email_verified_at)->toBeNull();
});

it('mengganti password dengan password lama yang benar', function () {
    $user = $this->actingAsRole(Role::Guardian);

    $this->from('/settings/security')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/security');

    expect(Hash::check('password-baru', $user->fresh()->password))->toBeTrue();
});

it('menolak ganti password bila password lama salah', function () {
    $this->actingAsRole(Role::Guardian);

    $this->put('/settings/password', [
        'current_password' => 'salah',
        'password' => 'password-baru',
        'password_confirmation' => 'password-baru',
    ])->assertSessionHasErrors('current_password');
});

it('tidak menyediakan hapus akun sendiri', function () {
    $this->actingAsRole(Role::Teacher);

    $this->delete('/settings/profile')->assertStatus(405);
});
