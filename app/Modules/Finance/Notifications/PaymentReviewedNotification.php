<?php

namespace App\Modules\Finance\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Ke orang tua: pembayaran diterima/dikonfirmasi atau bukti transfer ditolak.
 */
class PaymentReviewedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $invoicePublicId,
        public string $studentName,
        public string $title,
        public int $amount,
        public bool $accepted,
        public ?string $reason = null,
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
        $amount = InvoiceIssuedNotification::rupiah($this->amount);

        return [
            'title' => $this->accepted ? "Pembayaran {$amount} diterima" : 'Bukti transfer ditolak',
            'body' => $this->accepted
                ? "{$this->title} — {$this->studentName}. Kwitansi tersedia."
                : "{$this->title} — {$this->studentName}. Alasan: {$this->reason}",
            'url' => "/orang-tua/keuangan/{$this->invoicePublicId}",
        ];
    }
}
