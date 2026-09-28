<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Invoice;
use Illuminate\Validation\ValidationException;

/**
 * Batalkan tagihan yang belum ada pembayaran; dedupe_key dikosongkan agar bisa dibuat ulang.
 */
class CancelInvoiceAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Invoice $invoice, User $actor, string $reason): void
    {
        if (! $invoice->status->canTransitionTo(InvoiceStatus::Cancelled)) {
            throw ValidationException::withMessages(['invoice' => "Tagihan berstatus {$invoice->status->label()} tidak bisa dibatalkan."]);
        }

        if ($invoice->payments()->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Pending])->exists()) {
            throw ValidationException::withMessages(['invoice' => 'Tagihan yang sudah dibayar sebagian atau menunggu verifikasi tidak bisa dibatalkan.']);
        }

        $invoice->update([
            'status' => InvoiceStatus::Cancelled,
            'dedupe_key' => null,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'notes' => trim(($invoice->notes ? $invoice->notes."\n" : '').'Dibatalkan: '.$reason),
        ]);

        $this->audit->record('finance', 'cancelled', "Membatalkan tagihan {$invoice->number}", $invoice, ['reason' => $reason]);
    }
}
