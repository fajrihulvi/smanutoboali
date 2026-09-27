<?php

namespace App\Modules\Core\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Status operasional untuk GET /health (deploy cPanel, monitoring uptime).
 */
class HealthCheckService
{
    public const QUEUE_KEY = 'health.queue.last_processed_at';

    public const SCHEDULER_KEY = 'health.scheduler.last_run_at';

    /** Scheduler dianggap mati bila tidak berdetak selama ini (menit). */
    public const SCHEDULER_STALE_MINUTES = 5;

    public static function markQueueProcessed(): void
    {
        Cache::forever(self::QUEUE_KEY, now()->toIso8601String());
    }

    public static function markSchedulerRan(): void
    {
        Cache::forever(self::SCHEDULER_KEY, now()->toIso8601String());
    }

    /**
     * @return array{status: string, version: string, time: string, checks: array<string, array<string, mixed>>}
     */
    public function report(): array
    {
        $database = $this->database();
        $scheduler = $this->scheduler();

        $status = match (true) {
            ! $database['ok'] => 'down',
            ! $scheduler['ok'] => 'degraded',
            default => 'ok',
        };

        return [
            'status' => $status,
            'version' => (string) config('skoolia.version'),
            'time' => now()->toIso8601String(),
            'checks' => [
                'app' => ['ok' => true, 'env' => app()->environment(), 'debug' => (bool) config('app.debug')],
                'database' => $database,
                'queue' => $database['ok'] ? $this->queue() : ['ok' => false],
                'scheduler' => $scheduler,
            ],
        ];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    private function database(): array
    {
        try {
            DB::select('select 1');

            return ['ok' => true];
        } catch (Throwable) {
            // Pesan asli tidak diekspos (bisa berisi host/username DB).
            return ['ok' => false, 'error' => 'Koneksi database gagal.'];
        }
    }

    /**
     * @return array{ok: bool, pending: int, failed: int, last_processed_at: string|null}
     */
    private function queue(): array
    {
        $table = config('queue.connections.database.table', 'jobs');

        return [
            'ok' => true,
            'pending' => DB::table($table)->count(),
            'failed' => DB::table(config('queue.failed.table', 'failed_jobs'))->count(),
            'last_processed_at' => $this->cached(self::QUEUE_KEY),
        ];
    }

    /**
     * @return array{ok: bool, last_run_at: string|null}
     */
    private function scheduler(): array
    {
        $last = $this->cached(self::SCHEDULER_KEY);
        $fresh = $last !== null
            && CarbonImmutable::parse($last)->greaterThan(now()->subMinutes(self::SCHEDULER_STALE_MINUTES));

        return ['ok' => $fresh, 'last_run_at' => $last];
    }

    private function cached(string $key): ?string
    {
        try {
            $value = Cache::get($key);

            return is_string($value) ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }
}
