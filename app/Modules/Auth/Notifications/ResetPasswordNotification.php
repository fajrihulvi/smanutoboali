<?php

namespace App\Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Atur Ulang Password Akun '.config('app.name'))
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun '.config('app.name').' Anda.')
            ->action('Atur Ulang Password', $url)
            ->line("Tautan ini berlaku selama {$minutes} menit.")
            ->line('Jika Anda tidak meminta pengaturan ulang password, abaikan email ini. Password Anda tidak akan berubah.')
            ->salutation('Salam, Tim '.config('app.name'));
    }
}
