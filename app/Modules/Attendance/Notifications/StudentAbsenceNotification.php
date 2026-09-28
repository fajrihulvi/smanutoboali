<?php

namespace App\Modules\Attendance\Notifications;

use App\Modules\Attendance\Enums\AttendanceStatus;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Kabar ke orang tua: anak tercatat ALPA atau TERLAMBAT.
 */
class StudentAbsenceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $studentPublicId,
        public AttendanceStatus $status,
        public CarbonImmutable $date,
        public string $context,
        public ?string $note,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->studentName} tercatat {$this->status->label()} — {$this->dateLabel()}")
            ->greeting('Yth. Bapak/Ibu,')
            ->line("{$this->studentName} tercatat **{$this->status->label()}** pada {$this->dateLabel()} ({$this->context}).");

        if ($this->note) {
            $mail->line("Catatan guru: {$this->note}");
        }

        return $mail
            ->action('Lihat presensi anak', url($this->path()))
            ->line('Bila ada kekeliruan, silakan hubungi wali kelas.');
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->studentName} tercatat {$this->status->label()}",
            'body' => "{$this->dateLabel()} · {$this->context}".($this->note ? " — {$this->note}" : ''),
            'url' => $this->path(),
        ];
    }

    private function dateLabel(): string
    {
        return $this->date->locale('id')->translatedFormat('l, j F Y');
    }

    private function path(): string
    {
        return "/orang-tua/presensi?anak={$this->studentPublicId}";
    }
}
