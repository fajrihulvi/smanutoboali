<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\FinanceSequence;
use App\Modules\Finance\Services\InvoiceBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Operator memverifikasi bukti transfer: terima (CONFIRMED + nomor kwitansi) atau tolak (dengan alasan).
 */
class ReviewPaymentAction
{
    public function __construct(
        private FinanceSequence $sequence,
        private InvoiceBalance $balance,
        private AuditLogger $audit,
        private NotifyPaymentReviewed $notify,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Payment $payment, User $operator, bool $approve, ?string $reason = null): Payment
    {
        DB::transaction(function () use ($payment, $operator, $approve, $reason) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $payment->refresh();
            $next = $approve ? PaymentStatus::Confirmed : PaymentStatus::Rejected;

            if (! $payment->status->canTransitionTo($next)) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah diverifikasi.']);
            }

            if ($approve && $payment->amount > $invoice->remaining()) {
                throw ValidationException::withMessages(['payment' => 'Jumlah melebihi sisa tagihan; tolak bukti ini atau catat ulang dengan jumlah yang benar.']);
            }

            $payment->update([
                'status' => $next,
                'receipt_number' => $approve ? $this->sequence->next('KW') : null,
                'rejection_reason' => $approve ? null : $reason,
                'verified_by' => $operator->id,
                'verified_at' => now(),
            ]);

            $this->balance->refresh($invoice);
        });

        $payment->load('invoice.student', 'invoice.feeType');
        $this->audit->record('finance', $approve ? 'payment_confirmed' : 'payment_rejected',
            ($approve ? 'Menerima' : 'Menolak')." bukti transfer untuk {$payment->invoice->number}", $payment->invoice);
        $this->notify->handle($payment, accepted: $approve);

        return $payment;
    }
}
