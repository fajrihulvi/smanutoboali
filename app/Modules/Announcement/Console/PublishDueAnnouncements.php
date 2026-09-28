<?php

namespace App\Modules\Announcement\Console;

use App\Modules\Announcement\Actions\PublishDueAnnouncementsAction;
use Illuminate\Console\Command;

/**
 * Dijalankan scheduler tiap menit: pengumuman terjadwal yang sudah tiba waktunya → notifikasi.
 */
class PublishDueAnnouncements extends Command
{
    protected $signature = 'announcements:publish-due';

    protected $description = 'Antrekan notifikasi untuk pengumuman terjadwal yang sudah terbit';

    public function handle(PublishDueAnnouncementsAction $action): int
    {
        $count = $action->handle();
        $this->info("{$count} pengumuman diumumkan.");

        return self::SUCCESS;
    }
}
