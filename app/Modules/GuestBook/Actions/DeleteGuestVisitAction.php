<?php

namespace App\Modules\GuestBook\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\GuestBook\Models\GuestVisit;

class DeleteGuestVisitAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(GuestVisit $visit): void
    {
        $visit->delete();

        $this->audit->record('guest_book', 'deleted', "Menghapus data tamu {$visit->full_name}", $visit);
    }
}
