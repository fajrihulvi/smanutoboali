<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\LearningMaterial;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Illuminate\Support\Facades\DB;

class DeleteLearningMaterialAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
    ) {}

    public function handle(LearningMaterial $material): void
    {
        DB::transaction(function () use ($material) {
            if ($file = $material->file()->first()) {
                $this->files->delete($file);
            }

            $material->delete();

            $this->audit->record('academic', 'material_deleted', "Menghapus materi \"{$material->title}\"", $material);
        });
    }
}
