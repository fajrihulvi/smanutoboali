<?php

namespace App\Modules\Portfolio\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortfolioPublishedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $studentName,
        public string $studentPublicId,
        public string $title,
        public string $category,
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
            ->subject("Portofolio baru: {$this->title} — {$this->studentName}")
            ->greeting('Yth. Bapak/Ibu,')
            ->line("Ada portofolio baru ({$this->category}) untuk {$this->studentName}: **{$this->title}**.")
            ->action('Lihat portofolio', url($this->path()));
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Portofolio baru untuk {$this->studentName}",
            'body' => "{$this->category} · {$this->title}",
            'url' => $this->path(),
        ];
    }

    private function path(): string
    {
        return "/orang-tua/portofolio?anak={$this->studentPublicId}";
    }
}
