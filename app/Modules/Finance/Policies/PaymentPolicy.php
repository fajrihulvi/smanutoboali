<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use Illuminate\Support\Facades\Gate;

/**
 * Bukti transfer & kwitansi mengikuti hak melihat tagihannya.
 */
class PaymentPolicy
{
    /**
     * Juga dipakai FilePolicy untuk bukti transfer.
     */
    public function view(User $user, Payment $payment): bool
    {
        return Gate::forUser($user)->allows('view', $payment->invoice);
    }

    public function receipt(User $user, Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Confirmed && $this->view($user, $payment);
    }

    public function review(User $user, Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Pending && Gate::forUser($user)->allows('manage', Invoice::class);
    }
}
