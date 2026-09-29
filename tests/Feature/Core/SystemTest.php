<?php

use App\Models\User;
use App\Modules\Core\Console\RunBackup;
use App\Modules\Core\Enums\Role;
use App\Modules\Report\Jobs\GenerateReportExport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

function failedJob(string $name = 'App\\Modules\\Report\\Jobs\\GenerateReportExport'): string
{
    $uuid = (string) Str::uuid();
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['uuid' => $uuid, 'displayName' => $name, 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => ['commandName' => $name, 'command' => serialize(new GenerateReportExport(0))]]),
        'exception' => "RuntimeException: Gagal membuat file\n#0 stack",
        'failed_at' => now(),
    ]);

    return $uuid;
}

describe('halaman sistem', function () {
    it('hanya untuk operator dengan izin setting.manage', function () {
        $this->actingAsRole(Role::Operator);
        $this->get('/operator/sistem')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Core::Operator/System')
            ->has('app.version')
            ->has('health.status')
            ->has('backups', 2)
            ->has('storage.folders', 4));

        foreach ([Role::Principal, Role::Teacher, Role::Guardian] as $role) {
            $this->actingAsRole($role);
            $this->get('/operator/sistem')->assertForbidden();
        }

        $operator = User::factory()->withRole(Role::Operator)->create();
        $operator->roles->each->revokePermissionTo('setting.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAsRole(Role::Operator, $operator->fresh());
        $this->get('/operator/sistem')->assertForbidden();
    });

    it('menampilkan job gagal lalu bisa mengulang atau menghapusnya', function () {
        $retry = failedJob();
        $forget = failedJob('App\\Modules\\Finance\\Jobs\\GenerateInvoicesJob');
        $this->actingAsRole(Role::Operator);

        $this->get('/operator/sistem')->assertInertia(fn (Assert $page) => $page
            ->where('queue.failed_total', 2)
            ->where('queue.failed.0.name', 'GenerateInvoicesJob')
            ->where('queue.failed.1.error', 'RuntimeException: Gagal membuat file'));

        $this->post("/operator/sistem/antrean/{$retry}/ulang")->assertRedirect()->assertSessionHas('success');
        expect(DB::table('failed_jobs')->where('uuid', $retry)->exists())->toBeFalse()
            ->and(DB::table('jobs')->count())->toBe(1);

        $this->delete("/operator/sistem/antrean/{$forget}")->assertRedirect();
        expect(DB::table('failed_jobs')->count())->toBe(0)
            ->and(Activity::query()->where('log_name', 'system')->count())->toBe(2);

        $this->post('/operator/sistem/antrean/'.Str::uuid().'/ulang')->assertNotFound();
    });

    it('backup manual dijalankan lewat antrean', function () {
        Queue::fake();
        $this->actingAsRole(Role::Operator);

        $this->post('/operator/sistem/backup')->assertRedirect()->assertSessionHas('success');

        Queue::assertPushed(QueuedCommand::class);
    });
});

describe('backup', function () {
    it('backup file & pembersihan memakai nama terpisah per jenis', function () {
        Storage::fake('backups');

        $this->artisan('skoolia:backup files')->assertSuccessful();
        $this->artisan('skoolia:backup clean')->assertSuccessful();

        $files = Storage::disk('backups')->allFiles();
        expect($files)->toHaveCount(1)
            ->and($files[0])->toStartWith(RunBackup::nameFor('files').'/')
            ->and(RunBackup::nameFor('database'))->toBe('skoolia-database');
    });

    it('menolak jenis backup yang tidak dikenal', function () {
        $this->artisan('skoolia:backup semua')->assertExitCode(2);
    });

    it('terjadwal harian (database) & mingguan (file) dengan retensi 7/4/3', function () {
        $events = collect(app(Schedule::class)->events())->mapWithKeys(fn ($e) => [Str::after((string) $e->command, "artisan' ") => $e->expression]);

        expect($events->get('skoolia:backup database'))->toBe('30 2 * * *')
            ->and($events->get('skoolia:backup files'))->toBe('0 3 * * 0')
            ->and($events->get('skoolia:backup clean'))->toBe('0 4 * * *')
            ->and($events->get('backup:monitor'))->toBe('0 5 * * *');

        expect(config('backup.cleanup.default_strategy'))
            ->keep_all_backups_for_days->toBe(7)
            ->keep_weekly_backups_for_weeks->toBe(4)
            ->keep_monthly_backups_for_months->toBe(3);
        expect(config('backup.monitor_backups'))->toHaveCount(2);
    });
});

describe('log khusus', function () {
    beforeEach(function () {
        $this->logDir = sys_get_temp_dir().'/skoolia-log-'.Str::random(8);
        mkdir($this->logDir);
        config([
            'logging.channels.auth' => ['driver' => 'single', 'path' => $this->logDir.'/auth.log'],
            'logging.channels.payment' => ['driver' => 'single', 'path' => $this->logDir.'/payment.log'],
        ]);
    });

    afterEach(function () {
        array_map('unlink', glob($this->logDir.'/*') ?: []);
        @rmdir($this->logDir);
    });

    it('mencatat login gagal & berhasil tanpa password', function () {
        $user = User::factory()->withRole(Role::Teacher)->create(['email' => 'guru@contoh.test', 'password' => 'rahasia-benar-123']);

        $this->post('/login/guru', ['email' => 'guru@contoh.test', 'password' => 'Tebakan-Salah-999'])->assertSessionHasErrors('email');
        $this->post('/login/guru', ['email' => 'guru@contoh.test', 'password' => 'rahasia-benar-123'])->assertRedirect();

        $log = (string) file_get_contents($this->logDir.'/auth.log');
        expect($log)->toContain('login_failed')->toContain('invalid_credentials')->toContain('guru@contoh.test')
            ->toContain('"user_id":'.$user->id)
            ->not->toContain('Tebakan-Salah-999')
            ->not->toContain('rahasia-benar-123');
    });

    it('mencatat callback pembayaran yang ditolak sebagai peringatan', function () {
        $this->postJson('/webhooks/midtrans', [
            'order_id' => 'SKL-TIDAKADA', 'transaction_status' => 'settlement', 'status_code' => '200',
            'gross_amount' => '150000.00', 'signature_key' => str_repeat('a', 128),
        ])->assertForbidden();

        $log = (string) file_get_contents($this->logDir.'/payment.log');
        expect($log)->toContain('WARNING: gateway_callback_rejected')->toContain('SKL-TIDAKADA')->toContain('invalid_signature')
            ->not->toContain(str_repeat('a', 128));
    });
});

it('halaman maintenance bergaya Skoolia dan halaman error tanpa tautan javascript', function () {
    $html = view('errors.503')->render();
    expect($html)->toContain('Sedang dalam perbaikan')->toContain('Coba lagi');

    $offenders = collect(array_merge(
        glob(resource_path('views/**/*.blade.php')) ?: [],
        glob(resource_path('views/*/*/*.blade.php')) ?: [],
        glob(resource_path('views/*.blade.php')) ?: [],
    ))->filter(fn (string $f) => str_contains((string) file_get_contents($f), 'javascript:'))->values()->all();

    expect($offenders)->toBe([]);
});
