<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\FinanceSequence;
use App\Modules\Finance\Services\InvoiceBalance;
use App\Modules\Finance\Services\PaymentLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Operator mencatat pembayaran (tunai/transfer) — langsung terkonfirmasi, boleh sebagian (cicilan).
 */
class RecordPaymentAction
{
    public function __construct(
        private FinanceSequence $sequence,
        private InvoiceBalance $balance,
        private FileService $files,
        private AuditLogger $audit,
        private NotifyPaymentReviewed $notify,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Invoice $invoice, User $operator, int $amount, CarbonImmutable $paidOn, ?string $note = null, ?UploadedFile $proof = null): Payment
    {
        $payment = DB::transaction(function () use ($invoice, $operator, $amount, $paidOn, $note, $proof) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            PaymentRules::assertPayable($invoice, $amount);

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'method' => PaymentMethod::Manual,
                'status' => PaymentStatus::Confirmed,
                'amount' => $amount,
                'paid_on' => $paidOn->toDateString(),
                'receipt_number' => $this->sequence->next('KW'),
                'note' => $note,
                'submitted_by' => $operator->id,
                'verified_by' => $operator->id,
                'verified_at' => now(),
            ]);

            if ($proof !== null) {
                $this->files->store($proof, FileDirectory::Payments, FileVisibility::Private, $payment, uploader: $operator, field: 'proof');
            }

            $this->balance->refresh($invoice);

            return $payment;
        });

        $payment->load('invoice.student', 'invoice.feeType');
        PaymentLog::info('payment_recorded', ['receipt' => $payment->receipt_number, 'invoice' => $payment->invoice->number, 'amount' => $amount, 'operator_id' => $operator->id]);
        $this->audit->record('finance', 'payment_recorded', "Mencatat pembayaran {$payment->receipt_number} untuk {$payment->invoice->number}", $payment->invoice, ['amount' => $amount]);
        $this->notify->handle($payment, accepted: true);

        return $payment;
    }
}
