<?php

namespace App\Modules\ChildCondition\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Ke guru tujuan & wali kelas: ada laporan kondisi anak baru (tanpa isi deskripsi).
 */
class ChildConditionSubmittedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $conditionPublicId,
        public string $studentName,
        public string $type,
    ) {}

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
            'title' => "Laporan kondisi anak: {$this->studentName}",
            'body' => "{$this->type} — dari orang tua. Mohon ditinjau.",
            'url' => "/guru/kondisi-anak/{$this->conditionPublicId}",
        ];
    }
}
