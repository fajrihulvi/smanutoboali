<?php

namespace App\Support\Broadcasting;

use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Channel notifikasi `broadcast` yang tidak melempar error bila Pusher gagal:
 * notifikasi database tetap tersimpan dan aksi utama (mis. simpan presensi) tidak batal.
 */
class ResilientBroadcastChannel extends BroadcastChannel
{
    /**
     * @param  mixed  $notifiable
     * @return array<int, mixed>|null
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            return parent::send($notifiable, $notification);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
