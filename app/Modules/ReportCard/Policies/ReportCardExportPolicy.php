<?php

namespace App\Modules\ReportCard\Policies;

use App\Models\User;
use App\Modules\ReportCard\Models\ReportCardExport;
use App\Modules\ReportCard\Services\ReportCardAccess;

/**
 * Unduhan ZIP cetak massal (via FilePolicy → fileable): peminta atau operator.
 */
class ReportCardExportPolicy
{
    public function __construct(private ReportCardAccess $access) {}

    public function view(User $user, ReportCardExport $export): bool
    {
        return $export->requested_by === $user->id || $this->access->isOperator($user);
    }
}
