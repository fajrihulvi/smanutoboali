<?php

use App\Models\User;
use App\Modules\Auth\Database\Seeders\DemoUserSeeder;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\HealthCheckService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

describe('GET /health', function () {
    beforeEach(function () {
        config(['skoolia.health_token' => 'rahasia-health', 'skoolia.version' => '9.9.9']);
    });

    it('tidak tersedia bila HEALTH_TOKEN kosong', function () {
        config(['skoolia.health_token' => null]);

        $this->get('/health')->assertNotFound();
    });

    it('menolak token yang salah atau kosong', function () {
        $this->getJson('/health')->assertForbidden();
        $this->getJson('/health?token=salah')->assertForbidden();
    });

    it('melaporkan status database, queue, scheduler, dan versi', function () {
        HealthCheckService::markSchedulerRan();
        HealthCheckService::markQueueProcessed();

        $this->getJson('/health', ['X-Health-Token' => 'rahasia-health'])
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('version', '9.9.9')
            ->assertJsonPath('checks.database.ok', true)
            ->assertJsonPath('checks.queue.pending', 0)
            ->assertJsonPath('checks.queue.failed', 0)
            ->assertJsonPath('checks.scheduler.ok', true)
            ->assertJsonStructure(['checks' => ['queue' => ['last_processed_at'], 'scheduler' => ['last_run_at']]]);
    });

    it('menandai degraded bila scheduler tidak berdetak', function () {
        Cache::forget(HealthCheckService::SCHEDULER_KEY);

        $this->getJson('/health?token=rahasia-health')
            ->assertOk()
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.scheduler.ok', false);
    });

    it('tidak membuat sesi untuk ping monitor', function () {
        $this->getJson('/health?token=rahasia-health')
            ->assertCookieMissing(config('session.cookie'))
            ->assertCookieMissing('XSRF-TOKEN');
    });

    it('scheduler memiliki tugas detak untuk health check', function () {
        $this->artisan('schedule:list')->expectsOutputToContain('scheduler-heartbeat')->assertSuccessful();
    });
});

describe('halaman error', function () {
    beforeEach(function () {
        Route::middleware('web')->get('/_test/boom', fn () => throw new RuntimeException('DB_PASSWORD=rahasia bocor'));
    });

    it('menampilkan halaman 404 dengan identitas sekolah', function () {
        $this->get('/tidak-ada-halaman')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee(config('app.name'))
            ->assertSee('images/logo.png');
    });

    it('menampilkan halaman 403 dengan identitas sekolah', function () {
        $this->actingAsRole(Role::Guardian);

        $this->get('/operator/users')
            ->assertForbidden()
            ->assertSee('Akses ditolak');
    });

    it('tidak membocorkan detail exception saat APP_DEBUG=false', function () {
        config(['app.debug' => false]);

        $this->get('/_test/boom')
            ->assertStatus(500)
            ->assertSee('Terjadi kesalahan')
            ->assertDontSee('rahasia bocor')
            ->assertDontSee('RuntimeException');
    });

    it('mengarahkan kunjungan Inertia yang kedaluwarsa (419) kembali dengan pesan', function () {
        Route::middleware('web')->post('/_test/expired', fn () => abort(419));
        $this->actingAsRole(Role::Teacher);

        $this->from('/guru/dashboard')
            ->withHeaders(['X-Inertia' => 'true'])
            ->post('/_test/expired')
            ->assertRedirect('/guru/dashboard')
            ->assertSessionHas('error', 'Sesi Anda telah berakhir. Silakan coba lagi.');
    });

    it('menampilkan halaman 419 untuk request biasa', function () {
        Route::middleware('web')->post('/_test/expired-html', fn () => abort(419));

        $this->post('/_test/expired-html')->assertStatus(419)->assertSee('Sesi berakhir');
    });
});

describe('skoolia:make-operator', function () {
    it('membuat akun operator aktif', function () {
        $this->artisan('skoolia:make-operator', ['email' => 'Admin@Sekolah.sch.id', '--name' => 'Admin Sekolah'])
            ->expectsQuestion('Password (tidak ditampilkan)', 'Rahasia-Kuat-2026!')
            ->expectsQuestion('Ulangi password', 'Rahasia-Kuat-2026!')
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@sekolah.sch.id')->firstOrFail();

        expect($user->hasRole('operator'))->toBeTrue()
            ->and($user->is_active)->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    });

    it('menolak konfirmasi password yang tidak cocok dan email ganda', function () {
        User::factory()->create(['email' => 'ada@sekolah.sch.id']);

        $this->artisan('skoolia:make-operator', ['email' => 'ada@sekolah.sch.id', '--name' => 'X'])
            ->expectsQuestion('Password (tidak ditampilkan)', 'satu-password')
            ->expectsQuestion('Ulangi password', 'dua-password')
            ->assertFailed();

        expect(User::query()->where('email', 'ada@sekolah.sch.id')->count())->toBe(1);
    });
});

it('seeder akun demo tidak berjalan di produksi', function () {
    app()->detectEnvironment(fn () => 'production');

    app(DemoUserSeeder::class)->run();

    expect(User::query()->where('email', 'operator@skoolia.test')->exists())->toBeFalse();

    app()->detectEnvironment(fn () => 'testing');
});
