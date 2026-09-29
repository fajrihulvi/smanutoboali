<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\HealthCheckService;
use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;

describe('seeder produksi', function () {
    beforeEach(function () {
        app()->detectEnvironment(fn () => 'production');
        config(['skoolia.setup' => [
            'school_name' => 'SMP Negeri 1 Contoh',
            'school_level' => 'smp',
            'operator_name' => 'Rina Operator',
            'operator_email' => 'Operator@SMPN1.sch.id',
            'operator_password' => 'Sekolah#Aman2026!',
        ]]);
        // Tanpa panggilan ke layanan HaveIBeenPwned saat test.
        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());
    });

    it('db:seed di produksi hanya membuat role, profil sekolah, dan satu operator — tanpa data demo', function () {
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $operator = User::query()->sole();
        expect($operator->email)->toBe('operator@smpn1.sch.id')
            ->and($operator->hasRoleEnum(Role::Operator))->toBeTrue()
            ->and($operator->email_verified_at)->not->toBeNull()
            ->and(Permission::query()->count())->toBeGreaterThan(10)
            ->and(School::current()->name)->toBe('SMP Negeri 1 Contoh')
            ->and(User::query()->where('email', 'like', '%@skoolia.test')->exists())->toBeFalse();

        // Aman dijalankan ulang.
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        expect(User::query()->count())->toBe(1);
    });

    it('menolak password operator yang lemah', function () {
        config(['skoolia.setup.operator_password' => 'password']);

        expect(fn () => $this->artisan('db:seed', ['--force' => true])->run())->toThrow(RuntimeException::class, 'Akun operator tidak dibuat');
        expect(User::query()->count())->toBe(0);
    });
});

describe('skoolia:go-live-check', function () {
    it('gagal di lingkungan non-produksi dan menandai akun demo & password lemah', function () {
        User::factory()->withRole(Role::Operator)->create(['email' => 'operator@skoolia.test', 'password' => 'password']);

        $this->artisan('skoolia:go-live-check')
            ->expectsOutputToContain('APP_ENV = production')
            ->expectsOutputToContain('operator@skoolia.test')
            ->assertFailed();
    });

    it('lolos pemeriksaan utama saat konfigurasi produksi lengkap', function () {
        app()->detectEnvironment(fn () => 'production');
        config([
            'app.debug' => false,
            'app.url' => 'https://skoolia.sch.id',
            'session.secure' => true,
            'mail.default' => 'smtp',
        ]);
        Cache::forever(HealthCheckService::SCHEDULER_KEY, now()->toIso8601String());
        User::factory()->withRole(Role::Operator)->create(['email' => 'rina@smpn1.sch.id', 'password' => 'Sekolah#Aman2026!']);

        $this->artisan('skoolia:go-live-check')
            ->doesntExpectOutputToContain('Set APP_ENV=production')
            ->doesntExpectOutputToContain('Aktifkan SSL')
            ->doesntExpectOutputToContain('Nonaktifkan/hapus di User Management')
            ->doesntExpectOutputToContain('Ganti password');
    });
});
