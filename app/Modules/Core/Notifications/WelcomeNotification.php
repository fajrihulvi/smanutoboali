<?php

namespace App\Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi contoh untuk akun demo (lonceng topbar).
 */
class WelcomeNotification extends Notification
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Selamat datang di Skoolia',
            'body' => 'Lengkapi profil Anda dan pastikan password sudah diganti.',
            'url' => '/settings/profile',
        ];
    }
}
