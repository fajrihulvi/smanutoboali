<?php

namespace App\Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Nomor dokumen berurutan per bulan: INV/2026/09/0001, KW/2026/09/0001.
 * Baris urutan dikunci (lockForUpdate) agar aman saat generate paralel.
 */
class FinanceSequence
{
    public function next(string $prefix, ?CarbonImmutable $date = null): string
    {
        $date ??= CarbonImmutable::now();
        $key = $prefix.'-'.$date->format('Y-m');

        return DB::transaction(function () use ($prefix, $date, $key) {
            DB::table('finance_sequences')->insertOrIgnore(['key' => $key, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);

            $current = (int) DB::table('finance_sequences')->where('key', $key)->lockForUpdate()->value('last_number');
            $next = $current + 1;
            DB::table('finance_sequences')->where('key', $key)->update(['last_number' => $next, 'updated_at' => now()]);

            return sprintf('%s/%s/%s/%04d', $prefix, $date->format('Y'), $date->format('m'), $next);
        });
    }
}
