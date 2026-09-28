<?php

namespace App\Modules\Finance\Gateway;

use App\Modules\Finance\Models\GatewayTransaction;
use App\Modules\Finance\Models\Invoice;

/**
 * Kontrak payment gateway (implementasi: Midtrans Snap).
 */
interface PaymentGateway
{
    public function name(): string;

    public function isEnabled(): bool;

    /**
     * Konfigurasi publik untuk frontend (client key & URL skrip), tanpa rahasia.
     *
     * @return array{client_key: string, script_url: string}
     */
    public function clientConfig(): array;

    /**
     * Buat sesi pembayaran untuk transaksi (mengisi snap_token/redirect_url).
     *
     * @throws GatewayException
     */
    public function createCharge(GatewayTransaction $transaction, Invoice $invoice, string $finishUrl): GatewayTransaction;

    /**
     * Verifikasi keaslian payload callback.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload): bool;
}
