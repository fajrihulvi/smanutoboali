<?php

namespace App\Modules\Portfolio\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\Portfolio\Models\Portfolio;
use Illuminate\Support\Facades\DB;

class DeletePortfolioAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
    ) {}

    public function handle(Portfolio $portfolio): void
    {
        DB::transaction(function () use ($portfolio) {
            $portfolio->files()->get()->each(fn ($file) => $this->files->delete($file));
            $portfolio->delete();
        });

        $this->audit->record('portfolio', 'deleted', "Menghapus portofolio \"{$portfolio->title}\"", $portfolio);
    }
}
