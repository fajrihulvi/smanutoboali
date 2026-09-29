<?php

namespace App\Modules\Report\Policies;

use App\Models\User;
use App\Modules\Report\Models\ReportExport;

/**
 * File ekspor hanya untuk pemohonnya dan hanya selama belum kedaluwarsa.
 * `view` juga dipakai FilePolicy untuk /files/{file}.
 */
class ReportExportPolicy
{
    public function view(User $user, ReportExport $export): bool
    {
        return $export->user_id === $user->id && $export->isDownloadable();
    }
}
