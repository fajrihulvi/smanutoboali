<?php

namespace App\Modules\Finance\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Ke operator keuangan: ada bukti transfer baru yang perlu diverifikasi.
 */
class PaymentProofSubmittedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $studentName,
        public string $title,
        public int $amount,
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
            'title' => 'Bukti transfer baru',
            'body' => "{$this->studentName} · {$this->title} · ".InvoiceIssuedNotification::rupiah($this->amount),
            'url' => '/operator/keuangan/verifikasi',
        ];
    }
}
