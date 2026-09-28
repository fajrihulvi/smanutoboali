<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Notifications\PaymentReviewedNotification;
use App\Modules\Finance\Services\StudentGuardians;
use Illuminate\Support\Facades\Notification;

/**
 * Beri tahu orang tua siswa: pembayaran diterima / bukti ditolak.
 */
class NotifyPaymentReviewed
{
    public function __construct(private StudentGuardians $guardians) {}

    public function handle(Payment $payment, bool $accepted): void
    {
        $invoice = $payment->invoice;
        $invoice->loadMissing(['student', 'feeType']);
        $accounts = $this->guardians->accounts([$invoice->student_id])->get($invoice->student_id);

        if ($accounts === null || $accounts->isEmpty()) {
            return;
        }

        Notification::send($accounts, new PaymentReviewedNotification(
            $invoice->public_id,
            $invoice->student->name,
            "{$invoice->feeType->name} {$invoice->period_label}",
            $payment->amount,
            $accepted,
            $payment->rejection_reason,
        ));
    }
}
