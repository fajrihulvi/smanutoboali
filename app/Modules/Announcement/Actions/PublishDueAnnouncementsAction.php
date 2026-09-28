<?php

namespace App\Modules\Announcement\Actions;

use App\Modules\Announcement\Jobs\NotifyAnnouncementAudience;
use App\Modules\Announcement\Models\Announcement;

/**
 * Pengumuman yang sudah waktunya terbit tetapi belum diumumkan → antre notifikasi (sekali saja).
 */
class PublishDueAnnouncementsAction
{
    public function handle(?Announcement $only = null): int
    {
        $due = Announcement::query()
            ->published()
            ->whereNull('notified_at')
            ->when($only, fn ($q) => $q->whereKey($only?->id))
            ->pluck('id');

        $count = 0;

        foreach ($due as $id) {
            // Klaim atomik agar dua proses tidak mengirim notifikasi ganda.
            $claimed = Announcement::query()->whereKey($id)->whereNull('notified_at')->update(['notified_at' => now()]);

            if ($claimed === 1) {
                NotifyAnnouncementAudience::dispatch($id);
                $count++;
            }
        }

        return $count;
    }
}
