<?php

namespace App\Support\Broadcasting;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Throwable;

/**
 * Broadcast event realtime tanpa menggagalkan request bila Pusher tidak terjangkau:
 * data sudah tersimpan di database, klien akan menyusul lewat fetch ulang.
 */
class SafeBroadcast
{
    public static function event(ShouldBroadcast $event): void
    {
        try {
            event($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
