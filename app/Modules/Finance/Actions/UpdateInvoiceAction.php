<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Models\Invoice;
use Carbon\CarbonImmutable;

/**
 * Hanya jatuh tempo & catatan yang boleh diubah; nominal salah → batalkan lalu buat ulang.
 */
class UpdateInvoiceAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Invoice $invoice, CarbonImmutable $dueDate, ?string $notes): void
    {
        $invoice->update(['due_date' => $dueDate->toDateString(), 'notes' => $notes]);
        $this->audit->record('finance', 'updated', "Mengubah tagihan {$invoice->number}", $invoice);
    }
}
