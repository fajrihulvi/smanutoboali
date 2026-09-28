<?php

namespace App\Modules\Activity\Actions;

use App\Modules\Activity\Models\Activity;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Illuminate\Support\Facades\DB;

class DeleteActivityAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
    ) {}

    public function handle(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            $activity->photos()->get()->each(fn ($file) => $this->files->delete($file));
            $activity->delete();
        });

        $this->audit->record('activity', 'deleted', "Menghapus kegiatan \"{$activity->title}\"", $activity);
    }
}
