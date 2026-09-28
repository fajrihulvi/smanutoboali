<?php

namespace App\Modules\Ppdb\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Tautan verifikasi email akun PPDB (signed URL berisi public_id, berlaku 24 jam).
 * Dikirim langsung (tidak lewat antrean) agar calon orang tua segera menerimanya.
 */
class VerifyApplicantEmailNotification extends Notification
{
    use Queueable;

    public const VALID_HOURS = 24;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function url(User $user): string
    {
        return URL::temporarySignedRoute('ppdb.verify', now()->addHours(self::VALID_HOURS), [
            'user' => $user->public_id,
            'hash' => sha1($user->email),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        return (new MailMessage)
            ->subject('Verifikasi email akun PPDB')
            ->greeting("Halo {$notifiable->name},")
            ->line('Terima kasih telah membuat akun PPDB. Klik tombol di bawah untuk memverifikasi email Anda.')
            ->action('Verifikasi email', self::url($notifiable))
            ->line('Tautan berlaku '.self::VALID_HOURS.' jam. Abaikan email ini bila Anda tidak mendaftar.');
    }
}
