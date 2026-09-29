<?php

namespace App\Modules\Finance\Services;

use Illuminate\Support\Facades\Log;

/**
 * Log khusus pembayaran (S34) di storage/logs/payment-YYYY-MM-DD.log.
 * Hanya metadata (nomor tagihan/order, nominal, status, id pengguna) — tanpa token Snap,
 * kunci server, atau isi payload mentah.
 */
class PaymentLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        Log::channel('payment')->info($event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        Log::channel('payment')->warning($event, $context);
    }
}
