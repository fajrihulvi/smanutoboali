<?php

namespace App\Modules\Dashboard\Services;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache agregat dashboard (5 menit, per tanggal). Widget personal/real-time tidak lewat sini.
 */
class DashboardCache
{
    public const TTL_SECONDS = 300;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function remember(string $key, CarbonImmutable $today, Closure $callback): mixed
    {
        return Cache::remember("dashboard.{$key}.{$today->toDateString()}", self::TTL_SECONDS, $callback);
    }
}
