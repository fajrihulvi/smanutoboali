<?php

namespace App\Modules\Finance\Gateway;

use App\Modules\Finance\Models\GatewayTransaction;
use App\Modules\Finance\Models\Invoice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Midtrans Snap lewat HTTP client Laravel (tanpa SDK).
 * Dok: https://docs.midtrans.com/reference/backend-integration
 */
class MidtransGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'midtrans';
    }

    public function isEnabled(): bool
    {
        return filled($this->serverKey()) && filled(config('services.midtrans.client_key'));
    }

    public function clientConfig(): array
    {
        return [
            'client_key' => (string) config('services.midtrans.client_key'),
            'script_url' => $this->production() ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js',
        ];
    }

    public function createCharge(GatewayTransaction $transaction, Invoice $invoice, string $finishUrl): GatewayTransaction
    {
        $invoice->loadMissing(['student', 'feeType']);
        $title = Str::limit("{$invoice->feeType->name} {$invoice->period_label}", 50, '');

        try {
            $response = Http::withBasicAuth($this->serverKey(), '')
                ->acceptJson()
                ->timeout(15)
                ->post($this->snapUrl(), [
                    'transaction_details' => ['order_id' => $transaction->order_id, 'gross_amount' => $transaction->amount],
                    'item_details' => [[
                        'id' => Str::limit($invoice->number, 50, ''),
                        'price' => $transaction->amount,
                        'quantity' => 1,
                        'name' => $title,
                    ]],
                    'customer_details' => ['first_name' => Str::limit($invoice->student->name, 50, '')],
                    'callbacks' => ['finish' => $finishUrl],
                ]);
        } catch (ConnectionException $e) {
            throw new GatewayException('Layanan pembayaran tidak dapat dihubungi. Coba beberapa saat lagi.', previous: $e);
        }

        if (! $response->successful() || blank($response->json('token'))) {
            throw new GatewayException('Pembayaran online gagal dibuat: '.($response->json('error_messages.0') ?? 'respons tidak valid').'.');
        }

        $transaction->update([
            'snap_token' => (string) $response->json('token'),
            'redirect_url' => (string) $response->json('redirect_url'),
        ]);

        return $transaction;
    }

    public function verifySignature(array $payload): bool
    {
        $expected = hash('sha512',
            ((string) ($payload['order_id'] ?? '')).
            ((string) ($payload['status_code'] ?? '')).
            ((string) ($payload['gross_amount'] ?? '')).
            $this->serverKey());

        return is_string($payload['signature_key'] ?? null) && hash_equals($expected, $payload['signature_key']);
    }

    private function serverKey(): string
    {
        return (string) config('services.midtrans.server_key');
    }

    private function production(): bool
    {
        return (bool) config('services.midtrans.is_production');
    }

    private function snapUrl(): string
    {
        return $this->production()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }
}
