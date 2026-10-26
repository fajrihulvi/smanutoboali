<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\HealthCheckService;
use App\Modules\People\Models\Student;
use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

describe('seeder produksi', function () {
    beforeEach(function () {
        app()->detectEnvironment(fn () => 'production');
        config(['skoolia.setup' => [
            'school_name' => 'SMA NU Toboali',
            'school_level' => 'sma',
            'account_domain' => 'smanutoboali.sch.id',
            'default_password' => 'Sekolah#Aman2026!',
        ]]);
        // Tanpa panggilan ke layanan HaveIBeenPwned saat test.
        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());
    });

    it('db:seed di produksi membuat role, profil sekolah, dan 4 akun default — tanpa data demo', function () {
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $users = User::query()->orderBy('id')->get();
        expect($users->pluck('email')->all())->toBe([
            'operator@smanutoboali.sch.id',
            'kepsek@smanutoboali.sch.id',
            'guru@smanutoboali.sch.id',
            'ortu@smanutoboali.sch.id',
        ])
            ->and($users->map(fn (User $u) => $u->coreRoles()[0])->all())->toBe([Role::Operator, Role::Principal, Role::Teacher, Role::Guardian])
            ->and($users->every(fn (User $u) => $u->must_change_password && $u->is_active && $u->email_verified_at !== null))->toBeTrue()
            ->and(Hash::check('Sekolah#Aman2026!', $users[0]->password))->toBeTrue()
            ->and(Permission::query()->count())->toBeGreaterThan(10)
            ->and(School::current()->level->value)->toBe('SMA')
            ->and(Student::query()->exists())->toBeFalse()
            ->and(User::query()->where('email', 'like', '%@skoolia.test')->exists())->toBeFalse();

        // Aman dijalankan ulang: akun yang sudah ada (dan password barunya) tidak diubah.
        $users[0]->update(['password' => 'Password#Baru2026!']);
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        expect(User::query()->count())->toBe(4)
            ->and(Hash::check('Password#Baru2026!', $users[0]->fresh()->password))->toBeTrue();
    });

    it('menolak password awal yang lemah', function () {
        config(['skoolia.setup.default_password' => 'password']);

        expect(fn () => $this->artisan('db:seed', ['--force' => true])->run())->toThrow(RuntimeException::class, 'Akun default tidak dibuat');
        expect(User::query()->count())->toBe(0);
    });

    it('tanpa DEFAULT_ACCOUNT_PASSWORD akun default tidak dibuat', function () {
        config(['skoolia.setup.default_password' => null]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        expect(User::query()->count())->toBe(0);
    });
});

describe('ganti password awal', function () {
    it('akun berpassword awal hanya bisa membuka halaman ganti password sampai password diganti', function () {
        $user = User::factory()->withRole(Role::Operator)->create(['password' => 'Sekolah#Aman2026!']);
        $user->forceFill(['must_change_password' => true])->save();
        $this->actingAs($user);

        $this->get('/operator/dashboard')->assertRedirect('/settings/security');
        $this->get('/operator/master-data/siswa')->assertRedirect('/settings/security');
        $this->getJson('/operator/master-data/siswa')->assertForbidden();
        $this->get('/settings/security')->assertOk()->assertInertia(fn (Assert $page) => $page->where('mustChangePassword', true));

        $this->put('/settings/password', [
            'current_password' => 'Sekolah#Aman2026!',
            'password' => 'Password#Baru2026!',
            'password_confirmation' => 'Password#Baru2026!',
        ])->assertSessionHasNoErrors()->assertRedirect('/dashboard');

        expect($user->fresh()->must_change_password)->toBeFalse();
        $this->get('/operator/dashboard')->assertOk();
    });

    it('akun guru & orang tua default tanpa data tertaut tetap bisa membuka portal', function (Role $role, string $url) {
        $this->actingAsRole($role);

        $this->get($url)->assertOk();
    })->with([
        [Role::Teacher, '/guru/dashboard'],
        [Role::Guardian, '/orang-tua/dashboard'],
        [Role::Principal, '/kepala-sekolah/dashboard'],
    ]);

    it('reset password oleh operator juga menyelesaikan kewajiban ganti password', function () {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        $user->update(['password' => 'Password#Baru2026!']);

        expect($user->fresh()->must_change_password)->toBeFalse();
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

    it('menandai migrasi dan permission yang belum dijalankan setelah git pull', function () {
        DB::table('migrations')->where('migration', '2026_10_26_120000_create_guest_visits_table')->delete();
        Permission::query()->where('name', 'guest_book.manage')->delete();

        $this->artisan('skoolia:go-live-check')
            ->expectsOutputToContain('2026_10_26_120000_create_guest_visits_table — Jalankan php artisan migrate --force')
            ->expectsOutputToContain('guest_book.manage — Jalankan php artisan db:seed --force')
            ->assertFailed();
    });
});
