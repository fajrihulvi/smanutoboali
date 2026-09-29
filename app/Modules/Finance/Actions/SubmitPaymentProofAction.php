<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Notifications\PaymentProofSubmittedNotification;
use App\Modules\Finance\Services\InvoiceBalance;
use App\Modules\Finance\Services\PaymentLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Orang tua mengunggah bukti transfer → pembayaran PENDING, tagihan PENDING, operator diberi tahu.
 * Satu bukti menunggu per tagihan agar tidak tercatat ganda.
 */
class SubmitPaymentProofAction
{
    public function __construct(private InvoiceBalance $balance, private FileService $files, private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Invoice $invoice, User $guardian, int $amount, CarbonImmutable $paidOn, UploadedFile $proof, ?string $note = null): Payment
    {
        $payment = DB::transaction(function () use ($invoice, $guardian, $amount, $paidOn, $proof, $note) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            PaymentRules::assertPayable($invoice, $amount);

            if ($invoice->payments()->where('status', PaymentStatus::Pending)->exists()) {
                throw ValidationException::withMessages(['proof' => 'Masih ada bukti transfer yang menunggu verifikasi untuk tagihan ini.']);
            }

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'method' => PaymentMethod::Manual,
                'status' => PaymentStatus::Pending,
                'amount' => $amount,
                'paid_on' => $paidOn->toDateString(),
                'note' => $note,
                'submitted_by' => $guardian->id,
            ]);

            $this->files->store($proof, FileDirectory::Payments, FileVisibility::Private, $payment, uploader: $guardian, field: 'proof');
            $this->balance->refresh($invoice);

            return $payment;
        });

        $payment->load('invoice.student', 'invoice.feeType');
        $invoice = $payment->invoice;
        PaymentLog::info('proof_submitted', ['invoice' => $invoice->number, 'amount' => $amount, 'user_id' => $guardian->id]);
        $this->audit->record('finance', 'proof_submitted', "Mengunggah bukti transfer untuk {$invoice->number}", $invoice, ['amount' => $amount]);

        $operators = User::permission(Permission::FinanceManage->value)->where('is_active', true)->get();
        Notification::send($operators, new PaymentProofSubmittedNotification($invoice->student->name, "{$invoice->feeType->name} {$invoice->period_label}", $amount));

        return $payment;
    }
}
