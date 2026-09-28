<?php

namespace App\Modules\Assessment\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Kabar ke orang tua: nilai komponen dipublikasikan.
 */
class GradePublishedNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(
        public string $studentName,
        public string $studentPublicId,
        public string $subject,
        public string $component,
        public float $score,
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
            ->subject("Nilai {$this->component} {$this->subject} — {$this->studentName}")
            ->greeting('Yth. Bapak/Ibu,')
            ->line("Nilai **{$this->component}** mata pelajaran {$this->subject} untuk {$this->studentName}: **{$this->scoreLabel()}**.")
            ->action('Lihat nilai anak', url($this->path()));
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Nilai {$this->component} {$this->subject}",
            'body' => "{$this->studentName}: {$this->scoreLabel()}",
            'url' => $this->path(),
        ];
    }

    private function scoreLabel(): string
    {
        return rtrim(rtrim(number_format($this->score, 2, ',', '.'), '0'), ',');
    }

    private function path(): string
    {
        return "/orang-tua/nilai?anak={$this->studentPublicId}";
    }
}
