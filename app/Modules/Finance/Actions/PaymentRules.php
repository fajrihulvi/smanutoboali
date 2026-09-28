<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Invoice;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bersama pembayaran: tagihan masih bisa dibayar & jumlah tidak melebihi sisa.
 */
final class PaymentRules
{
    /**
     * @throws ValidationException
     */
    public static function assertPayable(Invoice $invoice, int $amount): void
    {
        if (! $invoice->status->isPayable()) {
            throw ValidationException::withMessages(['amount' => "Tagihan berstatus {$invoice->status->label()} tidak bisa dibayar."]);
        }

        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran minimal Rp1.']);
        }

        if ($amount > $invoice->remaining()) {
            throw ValidationException::withMessages(['amount' => 'Jumlah melebihi sisa tagihan (Rp'.number_format($invoice->remaining(), 0, ',', '.').').']);
        }
    }
}
