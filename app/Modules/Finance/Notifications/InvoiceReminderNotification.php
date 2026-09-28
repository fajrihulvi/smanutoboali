<?php

namespace App\Modules\Finance\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pengingat jatuh tempo (H-3) atau lewat jatuh tempo (H+1).
 */
class InvoiceReminderNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $invoicePublicId,
        public string $studentName,
        public string $title,
        public int $remaining,
        public string $dueDate,
        public bool $overdue,
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
        $array = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($array['title'])
            ->greeting('Yth. Bapak/Ibu,')
            ->line($array['body'])
            ->action('Lihat tagihan', url($array['url']));
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $amount = InvoiceIssuedNotification::rupiah($this->remaining);

        return [
            'title' => $this->overdue ? "Tagihan {$this->studentName} lewat jatuh tempo" : "Tagihan {$this->studentName} segera jatuh tempo",
            'body' => "{$this->title} · sisa {$amount} · jatuh tempo {$this->dueDate}",
            'url' => "/orang-tua/keuangan/{$this->invoicePublicId}",
        ];
    }
}
