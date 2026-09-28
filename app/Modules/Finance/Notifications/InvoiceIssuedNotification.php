<?php

namespace App\Modules\Finance\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ke orang tua: tagihan baru untuk anak (in-app + email).
 */
class InvoiceIssuedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $invoicePublicId,
        public string $studentName,
        public string $title,
        public int $total,
        public string $dueDate,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->inAppChannels(withMail: true);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tagihan baru: {$this->title} — {$this->studentName}")
            ->greeting('Yth. Bapak/Ibu,')
            ->line("Ada tagihan baru untuk {$this->studentName}: **{$this->title}** sebesar **".self::rupiah($this->total).'**.')
            ->line("Jatuh tempo: {$this->dueDate}.")
            ->action('Lihat tagihan', url($this->path()));
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Tagihan baru untuk {$this->studentName}",
            'body' => "{$this->title} · ".self::rupiah($this->total)." · jatuh tempo {$this->dueDate}",
            'url' => $this->path(),
        ];
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    private function path(): string
    {
        return "/orang-tua/keuangan/{$this->invoicePublicId}";
    }
}
