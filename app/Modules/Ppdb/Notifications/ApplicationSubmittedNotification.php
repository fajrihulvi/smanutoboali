<?php

namespace App\Modules\Ppdb\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Ke operator PPDB: pendaftaran baru / perbaikan masuk antrean verifikasi.
 */
class ApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(public string $number, public string $name, public bool $revision) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->inAppChannels();
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->revision ? 'Perbaikan pendaftaran PPDB' : 'Pendaftar PPDB baru',
            'body' => "{$this->number} · {$this->name}",
            'url' => '/operator/ppdb',
        ];
    }
}
