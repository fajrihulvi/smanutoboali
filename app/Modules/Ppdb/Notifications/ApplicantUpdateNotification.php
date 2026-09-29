<?php

namespace App\Modules\Ppdb\Notifications;

use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Kabar untuk calon orang tua: perlu perbaikan, terverifikasi, hasil seleksi, daftar ulang selesai.
 */
class ApplicantUpdateNotification extends Notification implements ShouldQueue
{
    use DeliversInApp, Queueable;

    public const REVISION = 'revision';

    public const VERIFIED = 'verified';

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    public const REREGISTERED = 'reregistered';

    public function __construct(
        public string $kind,
        public string $number,
        public string $name,
        public ?string $note = null,
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
        $data = $this->toArray($notifiable);
        $mail = (new MailMessage)->subject($data['title'].' — '.$this->number)->greeting('Yth. Bapak/Ibu,')->line($data['body']);

        if ($this->note) {
            $mail->line('Catatan: '.$this->note);
        }

        return $mail->action($this->kind === self::REREGISTERED ? 'Masuk portal orang tua' : 'Buka portal PPDB', url($data['url']));
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        [$title, $body] = match ($this->kind) {
            self::REVISION => ['Pendaftaran perlu diperbaiki', "Pendaftaran {$this->name} ({$this->number}) perlu diperbaiki. Buka formulir, perbaiki sesuai catatan, lalu kirim ulang."],
            self::VERIFIED => ['Pendaftaran terverifikasi', "Data & dokumen {$this->name} ({$this->number}) sudah diverifikasi dan masuk tahap seleksi."],
            self::ACCEPTED => ['Selamat, calon siswa diterima', "{$this->name} ({$this->number}) DITERIMA. Silakan lakukan daftar ulang melalui portal PPDB."],
            self::REJECTED => ['Hasil seleksi PPDB', "Mohon maaf, {$this->name} ({$this->number}) belum dapat diterima pada periode ini."],
            default => ['Daftar ulang selesai', "Daftar ulang {$this->name} ({$this->number}) selesai. Akun Anda kini menjadi akun orang tua; masuk melalui portal orang tua."],
        };

        return [
            'title' => $title,
            'body' => $body,
            'url' => $this->kind === self::REREGISTERED ? '/login/orang-tua' : '/ppdb/akun/dashboard',
        ];
    }
}
