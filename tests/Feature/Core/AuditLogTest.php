<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

it('menyimpan ip dan user agent di setiap audit log', function () {
    $user = User::factory()->withRole(Role::Teacher)->create();

    $this->withHeader('User-Agent', 'PestBrowser/1.0')
        ->post('/login/guru', ['email' => $user->email, 'password' => 'password']);

    $log = Activity::query()->where('log_name', 'auth')->where('event', 'login')->firstOrFail();

    expect($log->properties['ip'])->toBe('127.0.0.1')
        ->and($log->properties['user_agent'])->toBe('PestBrowser/1.0')
        ->and($log->causer_id)->toBe($user->id);
});

it('mencatat logout dan pergantian role', function () {
    $user = User::factory()->withRole(Role::Teacher, Role::Guardian)->create();
    $this->actingAsRole(Role::Teacher, $user);

    $this->post('/switch-role/guardian');
    $this->post('/logout');

    expect(Activity::query()->where('log_name', 'auth')->pluck('event')->all())
        ->toContain('role_switched', 'logout');
});

it('operator dan kepala sekolah dapat membuka audit log', function (Role $role, string $path) {
    $this->actingAsRole($role);

    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Core::AuditLog/Index')
            ->where('basePath', $path));
})->with([
    [Role::Operator, '/operator/audit-log'],
    [Role::Principal, '/kepala-sekolah/audit-log'],
]);

it('role lain tidak dapat membuka audit log', function (Role $role) {
    $this->actingAsRole($role);

    $this->get('/operator/audit-log')->assertForbidden();
    $this->get('/kepala-sekolah/audit-log')->assertForbidden();
})->with([Role::Teacher, Role::Guardian]);

it('memfilter berdasarkan modul, pengguna, dan tanggal', function () {
    $budi = User::factory()->create(['name' => 'Budi Operator']);
    $audit = app(AuditLogger::class);
    $audit->record('access', 'created', 'Membuat user A', causer: $budi);
    $audit->record('settings', 'updated', 'Mengubah pengaturan');

    $this->travel(-10)->days();
    $audit->record('access', 'created', 'Aktivitas lama', causer: $budi);
    $this->travelBack();

    $this->actingAsRole(Role::Operator);

    $this->get('/operator/audit-log?module=settings')
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.description', 'Mengubah pengaturan'));

    $this->get('/operator/audit-log?user=budi')
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 2));

    $from = now()->subDay()->format('Y-m-d');
    $this->get("/operator/audit-log?user=budi&from={$from}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.description', 'Membuat user A')
            ->where('logs.data.0.module_label', 'Akses & Pengguna'));
});
