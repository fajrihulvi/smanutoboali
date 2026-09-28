<?php

namespace App\Modules\Announcement\Jobs;

use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Notifications\AnnouncementPublishedNotification;
use App\Modules\Announcement\Services\AnnouncementAudienceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Kirim notifikasi pengumuman ke seluruh audiens per 200 user (shared hosting: lewat
 * queue database yang diproses scheduler).
 */
class NotifyAnnouncementAudience implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CHUNK = 200;

    public function __construct(public int $announcementId) {}

    public function handle(AnnouncementAudienceResolver $audience): void
    {
        $announcement = Announcement::query()->find($this->announcementId);

        if ($announcement === null || ! $announcement->isPublished()) {
            return;
        }

        $notification = new AnnouncementPublishedNotification(
            $announcement->public_id,
            $announcement->title,
            $announcement->body,
            $announcement->audience === AnnouncementAudience::Roles ? ($announcement->roles ?? []) : [],
        );

        $audience->recipients($announcement)
            ->with('roles')
            ->chunkById(self::CHUNK, fn (Collection $users) => Notification::send($users, $notification));
    }
}
