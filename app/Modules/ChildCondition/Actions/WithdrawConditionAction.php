<?php

namespace App\Modules\ChildCondition\Actions;

use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Illuminate\Support\Facades\DB;

/**
 * Orang tua menarik laporan yang belum dibuka guru (foto ikut dihapus).
 */
class WithdrawConditionAction
{
    public function __construct(private FileService $files, private AuditLogger $audit) {}

    public function handle(ChildCondition $condition): void
    {
        $condition->loadMissing(['student', 'photos']);

        DB::transaction(function () use ($condition) {
            $condition->photos->each(fn ($photo) => $this->files->delete($photo));
            $condition->delete();
        });

        $this->audit->record('child_condition', 'deleted', "Menarik laporan kondisi {$condition->student->name}", $condition);
    }
}
