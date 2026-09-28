<?php

namespace App\Modules\ReportCard\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportCardPublishedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $studentName,
        public string $reportCardPublicId,
        public string $period,
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
            ->subject("Raport {$this->studentName} — {$this->period} sudah terbit")
            ->greeting('Yth. Bapak/Ibu,')
            ->line("Raport {$this->studentName} untuk {$this->period} sudah terbit dan dapat dilihat serta diunduh.")
            ->action('Lihat raport', url($this->path()));
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Raport {$this->studentName} sudah terbit",
            'body' => $this->period,
            'url' => $this->path(),
        ];
    }

    private function path(): string
    {
        return "/orang-tua/raport/{$this->reportCardPublicId}";
    }
}
