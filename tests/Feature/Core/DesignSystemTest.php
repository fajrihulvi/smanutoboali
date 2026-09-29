<?php

use App\Modules\Core\Enums\Role;
use Inertia\Testing\AssertableInertia as Assert;

it('tidak mendaftarkan style guide di luar lingkungan lokal', function () {
    $this->get('/style-guide')->assertNotFound();
});

it('membagikan shared props dasar ke Inertia', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', null)
            ->where('auth.roles', [])
            ->where('auth.permissions', [])
            ->where('notifications.unread', 0)
            ->has('flash'));
});

it('tidak membocorkan id numerik user di shared props', function () {
    $user = $this->actingAsRole(Role::Teacher);

    $this->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.email', $user->email)
            ->missing('auth.user.id')
            ->missing('auth.user.password'));
});

it('meneruskan flash message sukses ke props', function () {
    $this->actingAsRole(Role::Teacher);

    $this->withSession(['success' => 'Data berhasil disimpan.'])
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Data berhasil disimpan.'));
});
