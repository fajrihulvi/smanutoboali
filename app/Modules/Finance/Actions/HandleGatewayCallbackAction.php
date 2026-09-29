<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Enums\GatewayStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Gateway\PaymentGateway;
use App\Modules\Finance\Models\GatewayTransaction;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentCallback;
use App\Modules\Finance\Notifications\OnlinePaymentNotification;
use App\Modules\Finance\Services\FinanceSequence;
use App\Modules\Finance\Services\InvoiceBalance;
use App\Modules\Finance\Services\PaymentLog;
use App\Modules\Finance\Services\StudentGuardians;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Proses callback payment gateway secara idempotent:
 *  1. simpan payload mentah (payment_callbacks)
 *  2. verifikasi signature & nominal
 *  3. kunci baris transaksi; transisi status hanya bila diizinkan (callback ganda → diabaikan)
 *  4. settlement → Payment GATEWAY CONFIRMED + kwitansi; gagal → tagihan FAILED; pending → PENDING
 *
 * Mengembalikan kode hasil: ok | ignored | invalid_signature | unknown_order | amount_mismatch | unsupported.
 */
class HandleGatewayCallbackAction
{
    public function __construct(
        private PaymentGateway $gateway,
        private FinanceSequence $sequence,
        private InvoiceBalance $balance,
        private StudentGuardians $guardians,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): string
    {
        $log = PaymentCallback::query()->create([
            'gateway' => $this->gateway->name(),
            'order_id' => is_scalar($payload['order_id'] ?? null) ? mb_substr((string) $payload['order_id'], 0, 50) : null,
            'transaction_status' => is_scalar($payload['transaction_status'] ?? null) ? (string) $payload['transaction_status'] : null,
            'status_code' => is_scalar($payload['status_code'] ?? null) ? (string) $payload['status_code'] : null,
            'gross_amount' => is_scalar($payload['gross_amount'] ?? null) ? (string) $payload['gross_amount'] : null,
            'signature_valid' => $this->gateway->verifySignature($payload),
            'payload' => $payload,
        ]);

        $result = $this->process($log, $payload);
        $log->update(['result' => $result]);

        $context = [
            'order_id' => $log->order_id,
            'transaction_status' => $log->transaction_status,
            'gross_amount' => $log->gross_amount,
            'signature_valid' => $log->signature_valid,
            'result' => $result,
        ];
        in_array($result, ['invalid_signature', 'unknown_order', 'amount_mismatch'], true)
            ? PaymentLog::warning('gateway_callback_rejected', $context)
            : PaymentLog::info('gateway_callback', $context);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function process(PaymentCallback $log, array $payload): string
    {
        if (! $log->signature_valid) {
            return 'invalid_signature';
        }

        $next = GatewayStatus::fromMidtrans((string) ($payload['transaction_status'] ?? ''), isset($payload['fraud_status']) ? (string) $payload['fraud_status'] : null);
        $transaction = GatewayTransaction::query()->where('order_id', $log->order_id)->first();

        if ($transaction === null) {
            return 'unknown_order';
        }

        if ($next === null) {
            return 'unsupported';
        }

        if ((int) round((float) ($payload['gross_amount'] ?? 0)) !== $transaction->amount) {
            return 'amount_mismatch';
        }

        $outcome = DB::transaction(function () use ($transaction, $next, $payload) {
            $transaction = GatewayTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if (! $transaction->status->canTransitionTo($next)) {
                return null;
            }

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($transaction->invoice_id);
            $transaction->fill([
                'status' => $next,
                'payment_type' => isset($payload['payment_type']) ? (string) $payload['payment_type'] : $transaction->payment_type,
                'gateway_transaction_id' => isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : $transaction->gateway_transaction_id,
            ]);

            if ($next === GatewayStatus::Settled) {
                $overpaid = $transaction->amount > $invoice->remaining();
                $payment = Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'method' => PaymentMethod::Gateway,
                    'status' => PaymentStatus::Confirmed,
                    'amount' => $transaction->amount,
                    'paid_on' => now()->toDateString(),
                    'receipt_number' => $this->sequence->next('KW'),
                    'note' => trim('Midtrans '.($transaction->payment_type ?? '').' · '.$transaction->order_id.($overpaid ? ' · KELEBIHAN BAYAR, periksa tagihan' : '')),
                    'submitted_by' => $transaction->created_by,
                    'verified_at' => now(),
                ]);
                $transaction->fill(['payment_id' => $payment->id, 'settled_at' => now()]);
            }

            $transaction->save();
            $this->balance->refresh($invoice, gatewayFailed: $next === GatewayStatus::Failed);

            return [$transaction, $invoice];
        });

        if ($outcome === null) {
            return 'ignored';
        }

        [$transaction, $invoice] = $outcome;

        if ($transaction->status->isFinal()) {
            $this->notify($transaction, $invoice);
        }

        return 'ok';
    }

    private function notify(GatewayTransaction $transaction, Invoice $invoice): void
    {
        $invoice->loadMissing(['student', 'feeType']);
        $success = $transaction->status === GatewayStatus::Settled;
        $title = "{$invoice->feeType->name} {$invoice->period_label}";

        $this->audit->record('finance', $success ? 'online_payment_settled' : 'online_payment_failed',
            ($success ? 'Pembayaran online berhasil' : 'Pembayaran online gagal')." ({$transaction->order_id}) untuk {$invoice->number}", $invoice, ['amount' => $transaction->amount]);

        $guardians = $this->guardians->accounts([$invoice->student_id])->get($invoice->student_id);

        if ($guardians !== null && $guardians->isNotEmpty()) {
            Notification::send($guardians, new OnlinePaymentNotification("/orang-tua/keuangan/{$invoice->public_id}", $invoice->student->name, $title, $transaction->amount, $success));
        }

        if ($success) {
            $operators = User::permission(Permission::FinanceManage->value)->where('is_active', true)->get();
            Notification::send($operators, new OnlinePaymentNotification("/operator/keuangan/tagihan/{$invoice->public_id}", $invoice->student->name, $title, $transaction->amount, true));
        }
    }
}
