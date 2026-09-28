<?php

namespace App\Modules\Finance\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Hasil pembayaran online (dari webhook): berhasil atau gagal/kedaluwarsa.
 * Dikirim ke orang tua dan (bila berhasil) operator keuangan dengan URL portal masing-masing.
 */
class OnlinePaymentNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $url,
        public string $studentName,
        public string $title,
        public int $amount,
        public bool $success,
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
            'title' => $this->success ? "Pembayaran online {$amount} berhasil" : 'Pembayaran online gagal atau kedaluwarsa',
            'body' => $this->success
                ? "{$this->title} — {$this->studentName}. Kwitansi tersedia."
                : "{$this->title} — {$this->studentName}. Silakan coba bayar lagi.",
            'url' => $this->url,
        ];
    }
}
