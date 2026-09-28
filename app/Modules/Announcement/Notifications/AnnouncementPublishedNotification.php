<?php

namespace App\Modules\Announcement\Notifications;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Notifications\Concerns\DeliversInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Pengumuman terbit — dikirim dari job NotifyAnnouncementAudience (sudah di antrean).
 */
class AnnouncementPublishedNotification extends Notification
{
    use DeliversInApp, Queueable;

    /**
     * @param  list<string>  $targetRoles  role audiens (kosong = semua role)
     */
    public function __construct(
        public string $publicId,
        public string $title,
        public string $body,
        public array $targetRoles = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->inAppChannels();
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Pengumuman: {$this->title}",
            'body' => Str::limit((string) preg_replace('/\s+/', ' ', $this->body), 120),
            'url' => '/'.$this->portalFor($notifiable)->urlPrefix()."/pengumuman/{$this->publicId}",
        ];
    }

    /**
     * Portal penerima: role yang menjadi target (untuk user multi-role), orang tua diutamakan.
     */
    private function portalFor(object $notifiable): Role
    {
        $roles = $notifiable instanceof User ? $notifiable->coreRoles() : [];
        $preferred = [Role::Guardian, Role::Teacher, Role::Principal, Role::Operator];

        foreach ($preferred as $role) {
            if (in_array($role, $roles, true) && ($this->targetRoles === [] || in_array($role->value, $this->targetRoles, true))) {
                return $role;
            }
        }

        return $roles[0] ?? Role::Guardian;
    }
}
