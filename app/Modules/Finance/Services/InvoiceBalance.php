<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Invoice;

/**
 * Hitung ulang terbayar & status tagihan dari pembayarannya:
 * total terkonfirmasi ≥ total → PAID; ada bukti menunggu → PENDING; selain itu UNPAID
 * (FAILED dari gateway dipertahankan sampai ada pembayaran/bukti baru).
 */
class InvoiceBalance
{
    public function refresh(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return $invoice;
        }

        $paid = (int) $invoice->payments()->where('status', PaymentStatus::Confirmed)->sum('amount');
        $hasPending = $invoice->payments()->where('status', PaymentStatus::Pending)->exists();

        $status = match (true) {
            $paid >= $invoice->total => InvoiceStatus::Paid,
            $hasPending => InvoiceStatus::Pending,
            $invoice->status === InvoiceStatus::Failed => InvoiceStatus::Failed,
            default => InvoiceStatus::Unpaid,
        };

        $invoice->forceFill([
            'paid_amount' => $paid,
            'status' => $status,
            'paid_at' => $status === InvoiceStatus::Paid ? ($invoice->paid_at ?? now()) : null,
        ])->save();

        return $invoice;
    }
}
