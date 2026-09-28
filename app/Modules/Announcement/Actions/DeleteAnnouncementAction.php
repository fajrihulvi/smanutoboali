<?php

namespace App\Modules\Announcement\Actions;

use App\Modules\Announcement\Models\Announcement;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Illuminate\Support\Facades\DB;

class DeleteAnnouncementAction
{
    public function __construct(private FileService $files, private AuditLogger $audit) {}

    public function handle(Announcement $announcement): void
    {
        DB::transaction(function () use ($announcement) {
            $announcement->attachments()->get()->each(fn ($file) => $this->files->delete($file));
            $announcement->delete();
        });

        $this->audit->record('announcement', 'deleted', "Menghapus pengumuman \"{$announcement->title}\"", $announcement);
    }
}
