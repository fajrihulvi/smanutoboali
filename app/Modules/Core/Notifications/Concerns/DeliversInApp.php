<?php

namespace App\Modules\Core\Notifications\Concerns;

use Illuminate\Notifications\Messages\BroadcastMessage;

/**
 * Notifikasi in-app: disimpan (database) dan dikirim realtime (broadcast) segera saat
 * notifikasi diproses, sementara email (bila ada) tetap lewat antrean.
 */
trait DeliversInApp
{
    /**
     * @return list<string>
     */
    protected function inAppChannels(bool $withMail = false): array
    {
        return $withMail ? ['database', 'broadcast', 'mail'] : ['database', 'broadcast'];
    }

    /**
     * Koneksi antrean per kanal (dipakai Laravel bila notifikasi ShouldQueue).
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync', 'broadcast' => 'sync'];
    }

    /**
     * Payload realtime = payload database. Event broadcast-nya dipaksa lewat koneksi `sync`;
     * tanpa ini BroadcastNotificationCreated masuk antrean default (database) dan baru terkirim
     * saat scheduler memproses queue (hingga 1 menit).
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onConnection('sync');
    }

    /**
     * Jenis notifikasi pada payload broadcast (frontend tidak butuh nama kelas PHP).
     */
    public function broadcastType(): string
    {
        return 'notifikasi';
    }
}
