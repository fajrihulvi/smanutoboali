<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Enums\GatewayStatus;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Gateway\GatewayException;
use App\Modules\Finance\Gateway\PaymentGateway;
use App\Modules\Finance\Models\GatewayTransaction;
use App\Modules\Finance\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Buat percobaan pembayaran online untuk SISA tagihan (order_id baru tiap percobaan).
 */
class StartOnlinePaymentAction
{
    public function __construct(private PaymentGateway $gateway, private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Invoice $invoice, User $guardian, string $finishUrl): GatewayTransaction
    {
        if (! $this->gateway->isEnabled()) {
            throw ValidationException::withMessages(['payment' => 'Pembayaran online belum diaktifkan sekolah.']);
        }

        if (! $invoice->status->isPayable() || $invoice->remaining() < 1) {
            throw ValidationException::withMessages(['payment' => 'Tagihan ini tidak bisa dibayar.']);
        }

        if ($invoice->payments()->where('status', PaymentStatus::Pending)->exists()) {
            throw ValidationException::withMessages(['payment' => 'Masih ada bukti transfer yang menunggu verifikasi.']);
        }

        $transaction = GatewayTransaction::query()->create([
            'invoice_id' => $invoice->id,
            'gateway' => $this->gateway->name(),
            'order_id' => 'SKL-'.Str::ulid(),
            'amount' => $invoice->remaining(),
            'status' => GatewayStatus::Created,
            'created_by' => $guardian->id,
        ]);

        try {
            $this->gateway->createCharge($transaction, $invoice, $finishUrl);
        } catch (GatewayException $e) {
            $transaction->update(['status' => GatewayStatus::Failed]);

            throw ValidationException::withMessages(['payment' => $e->getMessage()]);
        }

        $this->audit->record('finance', 'online_payment_started', "Memulai pembayaran online {$transaction->order_id} untuk {$invoice->number}", $invoice);

        return $transaction;
    }
}
