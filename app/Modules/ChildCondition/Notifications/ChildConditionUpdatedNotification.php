<?php

namespace App\Modules\ChildCondition\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Ke orang tua pelapor: laporan ditanggapi atau diselesaikan guru.
 */
class ChildConditionUpdatedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $conditionPublicId,
        public string $studentName,
        public string $teacherName,
        public bool $closed,
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
            'title' => $this->closed
                ? "Laporan kondisi {$this->studentName} selesai"
                : "{$this->teacherName} menanggapi laporan {$this->studentName}",
            'body' => $this->closed ? "Ditandai selesai oleh {$this->teacherName}." : 'Buka untuk membaca tanggapan guru.',
            'url' => "/orang-tua/kondisi-anak/{$this->conditionPublicId}",
        ];
    }
}
