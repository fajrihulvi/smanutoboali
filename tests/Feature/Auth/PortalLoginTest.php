<?php

use App\Models\User;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use Inertia\Testing\AssertableInertia as Assert;

dataset('portals', [
    'kepala sekolah' => [Role::Principal, 'kepala-sekolah', '/kepala-sekolah/dashboard'],
    'guru' => [Role::Teacher, 'guru', '/guru/dashboard'],
    'operator' => [Role::Operator, 'operator', '/operator/dashboard'],
    'orang tua' => [Role::Guardian, 'orang-tua', '/orang-tua/dashboard'],
]);

it('menampilkan halaman pilihan portal', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth::PortalSelect'));
});

it('menampilkan halaman login portal', function (Role $role, string $slug) {
    $this->get("/login/{$slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth::Login')
            ->where('portal', $role->value));
})->with('portals');

it('mengembalikan 404 untuk portal yang tidak dikenal', function () {
    $this->get('/login/siswa')->assertNotFound();
    $this->get('/login/ppdb-akun')->assertNotFound();
});

it('login sukses ke portal sesuai role dan menyimpan role aktif', function (Role $role, string $slug, string $dashboard) {
    $user = User::factory()->withRole($role)->create();

    $this->post("/login/{$slug}", ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($dashboard)
        ->assertSessionHas(ActiveRoleService::SESSION_KEY, $role->value);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();

    $this->get($dashboard)->assertOk();
})->with('portals');

it('menolak login ke portal yang bukan role-nya dengan pesan generik', function () {
    $guardian = User::factory()->withRole(Role::Guardian)->create();

    $this->from('/login/guru')
        ->post('/login/guru', ['email' => $guardian->email, 'password' => 'password'])
        ->assertRedirect('/login/guru')
        ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

    $this->assertGuest();
});

it('menolak password salah dengan pesan generik yang sama', function () {
    $teacher = User::factory()->withRole(Role::Teacher)->create();

    $this->post('/login/guru', ['email' => $teacher->email, 'password' => 'salah'])
        ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

    $this->post('/login/guru', ['email' => 'tidak-ada@skoolia.test', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

    $this->assertGuest();
});

it('menolak akun nonaktif', function () {
    $teacher = User::factory()->withRole(Role::Teacher)->inactive()->create();

    $this->post('/login/guru', ['email' => $teacher->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('membatasi percobaan login 5x per menit per email+IP', function () {
    $teacher = User::factory()->withRole(Role::Teacher)->create();

    foreach (range(1, 5) as $ignored) {
        $this->post('/login/guru', ['email' => $teacher->email, 'password' => 'salah']);
    }

    // Percobaan ke-6 ditolak meski password benar.
    $this->post('/login/guru', ['email' => $teacher->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Terlalu banyak percobaan');
    $this->assertGuest();
});

it('memvalidasi input login dalam Bahasa Indonesia', function () {
    $this->post('/login/guru', ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email', 'password']);

    expect(session('errors')->first('email'))->toContain('wajib');
});

it('tidak memakai URL tujuan milik portal lain setelah login', function () {
    $user = User::factory()->withRole(Role::Teacher)->create();

    $this->withSession(['url.intended' => url('/operator/dashboard')])
        ->post('/login/guru', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/guru/dashboard');
});

it('memakai URL tujuan bila masih di portal yang sama', function () {
    $user = User::factory()->withRole(Role::Teacher)->create();

    $this->withSession(['url.intended' => url('/guru/presensi')])
        ->post('/login/guru', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/guru/presensi');
});

it('mengarahkan user yang sudah login dari halaman login ke dashboard-nya', function () {
    $this->actingAsRole(Role::Operator);

    $this->get('/login')->assertRedirect('/operator/dashboard');
});

it('tidak menyediakan halaman register publik', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});
