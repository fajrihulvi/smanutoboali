<?php

namespace App\Modules\Core\Console;

use App\Models\User;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\HealthCheckService;
use App\Modules\School\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Backup\BackupDestination\BackupDestination;
use Throwable;

/**
 * Checklist go-live otomatis (S35). Jalankan di server setelah pemasangan:
 *   php artisan skoolia:go-live-check
 * GAGAL = wajib diperbaiki sebelum dibuka ke pengguna; PERINGATAN = periksa manual.
 */
class GoLiveCheck extends Command
{
    protected $signature = 'skoolia:go-live-check';

    protected $description = 'Periksa kesiapan produksi (keamanan, konfigurasi, cron, backup, akun)';

    private const PHP_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'gd', 'zip', 'curl', 'intl'];

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $rows = [];

    public function handle(): int
    {
        $this->app();
        $this->services();
        $this->operations();
        $this->accounts();
        $this->files();

        $this->table(['Pemeriksaan', 'Status', 'Keterangan'], $this->rows);

        $failed = count(array_filter($this->rows, fn (array $r) => $r[1] === 'GAGAL'));
        $warned = count(array_filter($this->rows, fn (array $r) => $r[1] === 'PERINGATAN'));

        if ($failed > 0) {
            $this->components->error("{$failed} pemeriksaan GAGAL dan {$warned} peringatan. Perbaiki sebelum dibuka ke pengguna.");

            return self::FAILURE;
        }

        $warned > 0
            ? $this->components->warn("Siap, dengan {$warned} peringatan yang perlu diperiksa manual.")
            : $this->components->info('Semua pemeriksaan lolos. Siap go-live.');

        return self::SUCCESS;
    }

    private function app(): void
    {
        $url = (string) config('app.url');

        $this->check('APP_ENV = production', app()->isProduction(), 'Set APP_ENV=production', info: app()->environment());
        $this->check('APP_DEBUG = false', ! config('app.debug'), 'Detail error tidak boleh tampil ke pengguna');
        $this->check('APP_KEY terisi', filled(config('app.key')), 'php artisan key:generate --force');
        $this->check('APP_URL memakai https://', str_starts_with($url, 'https://'), 'Aktifkan SSL lalu APP_URL=https://…', info: $url);
        $this->check('Cookie sesi hanya lewat HTTPS', (bool) config('session.secure'), 'Jangan isi SESSION_SECURE_COOKIE=false', warnOnly: true);
        $this->check('CSP aktif (bukan report-only)', config('skoolia.security.csp') && ! config('skoolia.security.csp_report_only'), 'CSP_ENABLED=true, CSP_REPORT_ONLY=false', warnOnly: true);
        $this->check('Versi PHP ≥ 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), 'Pilih PHP 8.2/8.3 di MultiPHP Manager', info: PHP_VERSION);

        $missing = array_values(array_filter(self::PHP_EXTENSIONS, fn (string $ext) => ! extension_loaded($ext)));
        $this->check('Ekstensi PHP lengkap', $missing === [], 'Aktifkan di Select PHP Version → Extensions: '.implode(', ', $missing));
        $this->check('Config sudah di-cache (php artisan optimize)', app()->configurationIsCached(), 'Jalankan php artisan optimize', warnOnly: true);
    }

    private function services(): void
    {
        $mailer = (string) config('mail.default');
        $this->check('Email produksi (bukan log/array)', ! in_array($mailer, ['log', 'array'], true), 'Isi MAIL_MAILER=smtp & kredensial MAIL_*', info: "MAIL_MAILER={$mailer}");
        $this->check('Antrean memakai database', config('queue.default') === 'database', 'QUEUE_CONNECTION=database', warnOnly: true, info: 'QUEUE_CONNECTION='.config('queue.default'));

        $pusher = config('broadcasting.default') === 'pusher'
            && filled(config('broadcasting.connections.pusher.key'))
            && filled(config('broadcasting.connections.pusher.secret'))
            && filled(config('broadcasting.connections.pusher.app_id'));
        $this->check('Pusher (chat & notifikasi realtime)', $pusher, 'BROADCAST_CONNECTION=pusher + PUSHER_APP_ID/KEY/SECRET', warnOnly: true);

        $server = (string) config('services.midtrans.server_key');
        $client = (string) config('services.midtrans.client_key');

        if ($server === '' && $client === '') {
            $this->caution('Midtrans', 'Kunci kosong: pembayaran online tidak aktif (pembayaran manual tetap jalan)');
        } else {
            $sandbox = str_starts_with($server, 'SB-') || str_starts_with($client, 'SB-');
            $this->check('Midtrans memakai kunci produksi', (bool) config('services.midtrans.is_production') && ! $sandbox, 'Pakai kunci produksi + MIDTRANS_IS_PRODUCTION=true', info: $sandbox ? 'kunci sandbox (SB-…)' : 'MIDTRANS_IS_PRODUCTION='.(config('services.midtrans.is_production') ? 'true' : 'false'));
        }

        $this->check('HEALTH_TOKEN terisi', filled(config('skoolia.health_token')), 'Untuk pemantauan /health', warnOnly: true);
    }

    private function operations(): void
    {
        $last = Cache::get(HealthCheckService::SCHEDULER_KEY);
        $fresh = is_string($last) && CarbonImmutable::parse($last)->greaterThan(now()->subMinutes(HealthCheckService::SCHEDULER_STALE_MINUTES));
        $this->check('Cron (schedule:run) berdetak', $fresh, 'Pasang cron per menit (lihat docs/DEPLOY_CPANEL.md §6)', info: $last ? 'terakhir '.$last : 'belum pernah berjalan');

        try {
            $disk = (string) (config('backup.backup.destination.disks')[0] ?? 'backups');
            $newest = BackupDestination::create($disk, RunBackup::nameFor('database'))->newestBackup();
            $ok = $newest !== null && $newest->date()->greaterThan(now()->subHours(26));
            $this->check('Backup database < 26 jam', $ok, 'php artisan skoolia:backup database', warnOnly: true, info: $newest ? 'terakhir '.$newest->date()->toDateTimeString() : 'belum ada');
        } catch (Throwable $e) {
            $this->caution('Backup database', 'Tidak bisa dibaca: '.$e->getMessage());
        }

        $this->check('Email tujuan notifikasi backup', filled(config('backup.notifications.mail.to')) && ! str_ends_with((string) config('backup.notifications.mail.to'), '.test'), 'BACKUP_MAIL_TO', warnOnly: true);
    }

    private function accounts(): void
    {
        $demo = User::query()->where('is_active', true)->where('email', 'like', '%@skoolia.test')->pluck('email');
        $this->check('Tidak ada akun demo aktif', $demo->isEmpty(), 'Nonaktifkan/hapus di User Management: '.$demo->implode(', '));

        $admins = User::query()->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [Role::Operator->value, Role::Principal->value]))
            ->get(['id', 'email', 'password']);
        $this->check('Ada operator/kepsek aktif', $admins->isNotEmpty(), 'php artisan skoolia:make-operator <email>', info: $admins->count().' akun');

        $weak = $admins->filter(fn (User $u) => Hash::check('password', $u->password))->pluck('email');
        $this->check('Tidak ada operator/kepsek berpassword "password"', $weak->isEmpty(), 'Ganti password: '.$weak->implode(', '));

        $this->check('OPERATOR_PASSWORD sudah dihapus dari .env', blank(config('skoolia.setup.operator_password')), 'Hapus setelah seeding, lalu php artisan optimize', warnOnly: true);
        $this->check('Profil sekolah sudah diisi', School::current()->name !== 'Sekolah Skoolia', 'Menu Pengaturan Sekolah', warnOnly: true);

        // Halaman sistem (Profil, Visi Misi, Kontak, …) dibuat sebagai draf oleh migrasi (temuan geladi S35).
        $live = WebsitePage::query()->live()->count();
        $this->check('Halaman website sudah diterbitkan', $live > 0, 'Isi & terbitkan di CMS Website → Halaman (Profil, Kontak, …)', warnOnly: true, info: "{$live} halaman terbit");
    }

    private function files(): void
    {
        $this->check('Aset build (public/build/manifest.json)', is_file(public_path('build/manifest.json')), 'Unggah folder public/build dari arsip rilis');
        $this->check('Tidak ada file public/hot', ! is_file(public_path('hot')), 'Hapus public/hot (sisa mode pengembangan)');
        $this->check('Folder storage bisa ditulis', is_writable(storage_path('framework')) && is_writable(storage_path('logs')) && is_writable(storage_path('app')), 'chmod -R 775 storage bootstrap/cache');
        $this->check('Pelindung .htaccess folder upload', is_file(storage_path('app/public/.htaccess')) && is_file(storage_path('app/private/.htaccess')), 'Pastikan ikut terunggah dari arsip rilis', warnOnly: true);
    }

    /**
     * `$fix` = cara memperbaiki (tampil bila tidak lolos); `$info` = nilai saat ini (selalu tampil).
     */
    private function check(string $label, bool $ok, string $fix, bool $warnOnly = false, ?string $info = null): void
    {
        $detail = $ok ? ($info ?? 'OK') : trim(($info !== null ? $info.' — ' : '').$fix);

        $this->rows[] = [$label, $ok ? 'OK' : ($warnOnly ? 'PERINGATAN' : 'GAGAL'), $detail];
    }

    private function caution(string $label, string $detail): void
    {
        $this->rows[] = [$label, 'PERINGATAN', $detail];
    }
}
