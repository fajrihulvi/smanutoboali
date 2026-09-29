<?php

namespace App\Modules\Report\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use App\Modules\Report\Enums\ExportStatus;
use App\Modules\Report\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Ekspor laporan selesai (tautan unduh berlaku 24 jam) atau gagal.
 */
class ReportExportNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public function __construct(public ReportExport $export) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->inAppChannels(withMail: true);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Yth. Bapak/Ibu,');

        if (! $this->succeeded()) {
            return $mail->subject("Ekspor laporan gagal: {$this->export->title}")
                ->line("Ekspor laporan {$this->export->title} gagal diproses. Silakan coba lagi atau persempit filter.");
        }

        return $mail->subject("Laporan siap diunduh: {$this->export->title}")
            ->line("Laporan {$this->export->title} ({$this->export->format->label()}) sudah siap.")
            ->action('Unduh laporan', url($this->path()))
            ->line('Tautan hanya bisa dibuka dengan akun Anda dan berlaku sampai '.$this->export->expires_at->translatedFormat('j F Y, H.i').'.');
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return $this->succeeded()
            ? [
                'title' => 'Laporan siap diunduh',
                'body' => "{$this->export->title} ({$this->export->format->label()}) · berlaku 24 jam",
                'url' => $this->path(),
            ]
            : [
                'title' => 'Ekspor laporan gagal',
                'body' => $this->export->title,
                'url' => '/dashboard',
            ];
    }

    private function succeeded(): bool
    {
        return $this->export->status === ExportStatus::Done;
    }

    private function path(): string
    {
        return "/laporan/ekspor/{$this->export->public_id}";
    }
}
