<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Console\RunBackup;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Throwable;

/**
 * Ringkasan kondisi sistem untuk halaman /operator/sistem (S34).
 */
class SystemStatus
{
    public const FAILED_JOBS_SHOWN = 20;

    public function __construct(private HealthCheckService $health) {}

    /**
     * @return array<string, mixed>
     */
    public function app(): array
    {
        $db = DB::connection();

        try {
            $dbVersion = (string) (DB::selectOne('select version() as v')->v ?? '');
        } catch (Throwable) {
            $dbVersion = '';
        }

        return [
            'name' => config('app.name'),
            'version' => (string) config('skoolia.version'),
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'url' => config('app.url'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'database' => trim($db->getDriverName().' '.$dbVersion),
            'drivers' => [
                'cache' => config('cache.default'),
                'queue' => config('queue.default'),
                'session' => config('session.driver'),
                'mail' => config('mail.default'),
                'broadcast' => config('broadcasting.default'),
            ],
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        return $this->health->report();
    }

    /**
     * @return array{pending: int, reserved: int, oldest_pending_at: string|null, failed_total: int, failed: list<array<string, mixed>>}
     */
    public function queue(): array
    {
        $jobs = DB::table(config('queue.connections.database.table', 'jobs'));
        $failedTable = config('queue.failed.table', 'failed_jobs');
        $oldest = (clone $jobs)->whereNull('reserved_at')->min('created_at');

        return [
            'pending' => (clone $jobs)->whereNull('reserved_at')->count(),
            'reserved' => (clone $jobs)->whereNotNull('reserved_at')->count(),
            'oldest_pending_at' => $oldest ? CarbonImmutable::createFromTimestamp((int) $oldest)->toIso8601String() : null,
            'failed_total' => DB::table($failedTable)->count(),
            'failed' => DB::table($failedTable)
                ->orderByDesc('id')
                ->limit(self::FAILED_JOBS_SHOWN)
                ->get(['uuid', 'queue', 'payload', 'exception', 'failed_at'])
                ->map(fn (object $job) => [
                    'uuid' => (string) $job->uuid,
                    'queue' => (string) $job->queue,
                    'name' => $this->jobName((string) $job->payload),
                    'error' => Str::limit(Str::before((string) $job->exception, "\n"), 300),
                    'failed_at' => CarbonImmutable::parse((string) $job->failed_at)->toIso8601String(),
                ])->values()->all(),
        ];
    }

    /**
     * Backup terbaru per jenis & tujuan.
     *
     * @return list<array<string, mixed>>
     */
    public function backups(): array
    {
        $rows = [];
        $maxAge = ['database' => 1, 'files' => 8];

        foreach (RunBackup::TYPES as $type) {
            foreach ((array) config('backup.backup.destination.disks', []) as $disk) {
                try {
                    $destination = BackupDestination::create((string) $disk, RunBackup::nameFor($type));
                    $backups = $destination->backups();
                    /** @var Backup|null $newest */
                    $newest = $backups->first();

                    $rows[] = [
                        'type' => $type,
                        'disk' => (string) $disk,
                        'reachable' => true,
                        'count' => $backups->count(),
                        'total_bytes' => (int) $backups->sum(fn (Backup $b) => $b->sizeInBytes()),
                        'newest_at' => $newest?->date()->toIso8601String(),
                        'newest_bytes' => $newest ? (int) $newest->sizeInBytes() : null,
                        'healthy' => $newest !== null && $newest->date()->greaterThan(now()->subDays($maxAge[$type])),
                    ];
                } catch (Throwable) {
                    $rows[] = ['type' => $type, 'disk' => (string) $disk, 'reachable' => false, 'count' => 0, 'total_bytes' => 0, 'newest_at' => null, 'newest_bytes' => null, 'healthy' => false];
                }
            }
        }

        return $rows;
    }

    /**
     * Ukuran folder storage (di-cache 10 menit — penelusuran folder bisa lambat) + sisa disk.
     *
     * @return array{folders: list<array{label: string, bytes: int}>, free_bytes: int|null, measured_at: string}
     */
    public function storage(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('system.storage');
        }

        /** @var array{folders: list<array{label: string, bytes: int}>, free_bytes: int|null, measured_at: string} */
        return Cache::remember('system.storage', 600, fn () => [
            'folders' => [
                ['label' => 'File privat (dokumen, bukti, foto siswa)', 'bytes' => $this->size(storage_path('app/private'))],
                ['label' => 'File publik (website)', 'bytes' => $this->size(storage_path('app/public'))],
                ['label' => 'Backup', 'bytes' => $this->size(storage_path('app/backups'))],
                ['label' => 'Log', 'bytes' => $this->size(storage_path('logs'))],
            ],
            'free_bytes' => ($free = @disk_free_space(storage_path())) === false ? null : (int) $free,
            'measured_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return list<array{name: string, bytes: int, modified_at: string}>
     */
    public function logs(): array
    {
        $files = glob(storage_path('logs/*.log')) ?: [];
        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        return array_map(fn (string $path) => [
            'name' => basename($path),
            'bytes' => (int) filesize($path),
            'modified_at' => CarbonImmutable::createFromTimestamp((int) filemtime($path))->toIso8601String(),
        ], array_slice($files, 0, 12));
    }

    private function size(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $bytes = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $bytes += $file->isFile() ? $file->getSize() : 0;
        }

        return $bytes;
    }

    private function jobName(string $payload): string
    {
        $data = json_decode($payload, true);
        $name = is_array($data) ? (string) ($data['displayName'] ?? 'Job') : 'Job';

        return class_basename($name);
    }
}
