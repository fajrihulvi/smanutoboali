<?php

use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Models\GatewayTransaction;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentCallback;
use App\Modules\Finance\Notifications\OnlinePaymentNotification;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('private');
    $this->travelTo('2026-09-28 10:00:00');
    config([
        'services.midtrans.server_key' => 'SB-Mid-server-test',
        'services.midtrans.client_key' => 'SB-Mid-client-test',
        'services.midtrans.is_production' => false,
    ]);
    Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'snap-token-123', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-123'], 201)]);
});

/**
 * Tagihan SPP Ani (Rp150.000) dan satu percobaan bayar online yang sudah dibuat orang tua.
 *
 * @return array<string, mixed>
 */
function onlineScenario(): array
{
    $s = financeScenario();
    test()->actingAsRole(Role::Operator, $s['operator']);
    test()->post('/operator/keuangan/buat', generatePayload($s['spp']));
    $invoice = invoiceFor($s['ani']);

    test()->actingAsRole(Role::Guardian, $s['parent']);
    $response = test()->postJson("/orang-tua/keuangan/{$invoice->public_id}/bayar")->assertOk();

    return [...$s, 'invoice' => $invoice, 'transaction' => GatewayTransaction::query()->sole(), 'session' => $response->json()];
}

/**
 * @return array<string, mixed>
 */
function midtransPayload(GatewayTransaction $transaction, string $status, array $extra = []): array
{
    $payload = [
        'order_id' => $transaction->order_id,
        'status_code' => $status === 'settlement' ? '200' : ($status === 'pending' ? '201' : '407'),
        'gross_amount' => number_format($transaction->amount, 2, '.', ''),
        'transaction_status' => $status,
        'payment_type' => 'bank_transfer',
        'transaction_id' => 'mid-trx-1',
        'fraud_status' => 'accept',
        ...$extra,
    ];
    $payload['signature_key'] ??= hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'SB-Mid-server-test');

    return $payload;
}

it('orang tua membuat sesi Snap untuk sisa tagihan dengan order_id unik tiap percobaan', function () {
    ['transaction' => $transaction, 'session' => $session, 'invoice' => $invoice] = onlineScenario();

    expect($session)->toMatchArray(['token' => 'snap-token-123', 'client_key' => 'SB-Mid-client-test', 'script_url' => 'https://app.sandbox.midtrans.com/snap/snap.js'])
        ->and($transaction->amount)->toBe(150000)
        ->and($transaction->order_id)->toStartWith('SKL-')
        ->and($session['status_url'])->toBe("/orang-tua/keuangan/{$invoice->public_id}/status/{$transaction->public_id}");

    Http::assertSent(fn (ClientRequest $r) => $r['transaction_details']['gross_amount'] === 150000
        && $r->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-test:')));

    $this->postJson("/orang-tua/keuangan/{$invoice->public_id}/bayar")->assertOk();
    expect(GatewayTransaction::query()->distinct()->count('order_id'))->toBe(2);
});

it('orang tua lain tidak bisa membayar; tanpa kunci Midtrans tombol nonaktif', function () {
    ['invoice' => $invoice, 'otherParent' => $otherParent, 'parent' => $parent] = onlineScenario();

    $this->actingAsRole(Role::Guardian, $otherParent);
    $this->postJson("/orang-tua/keuangan/{$invoice->public_id}/bayar")->assertForbidden();

    config(['services.midtrans.server_key' => null]);
    $this->actingAsRole(Role::Guardian, $parent);
    $this->get("/orang-tua/keuangan/{$invoice->public_id}")->assertInertia(fn (Assert $page) => $page->where('onlinePayment', false));
    $this->postJson("/orang-tua/keuangan/{$invoice->public_id}/bayar")->assertJsonValidationErrors('payment');
});

it('signature salah ditolak dan tetap dicatat', function () {
    ['transaction' => $transaction, 'invoice' => $invoice] = onlineScenario();

    $this->postJson('/webhooks/midtrans', midtransPayload($transaction, 'settlement', ['signature_key' => str_repeat('a', 128)]))->assertForbidden();

    expect($invoice->fresh()->status->value)->toBe('UNPAID')
        ->and(Payment::query()->count())->toBe(0)
        ->and(PaymentCallback::query()->sole())->signature_valid->toBeFalse()->result->toBe('invalid_signature');
});

it('settlement → PAID dengan kwitansi; callback ganda tidak menggandakan pembayaran', function () {
    ['transaction' => $transaction, 'invoice' => $invoice, 'parent' => $parent, 'operator' => $operator] = onlineScenario();
    Notification::fake();

    $this->postJson('/webhooks/midtrans', midtransPayload($transaction, 'settlement'))->assertOk()->assertJson(['result' => 'ok']);
    $this->postJson('/webhooks/midtrans', midtransPayload($transaction, 'settlement'))->assertOk()->assertJson(['result' => 'ignored']);

    $payment = Payment::query()->sole();
    expect($invoice->fresh()->status->value)->toBe('PAID')
        ->and($payment->method->value)->toBe('GATEWAY')
        ->and($payment->receipt_number)->toBe('KW/2026/09/0001')
        ->and($transaction->fresh()->status->value)->toBe('SETTLED')
        ->and(PaymentCallback::query()->count())->toBe(2);

    Notification::assertSentToTimes($parent, OnlinePaymentNotification::class, 1);
    Notification::assertSentTo($operator, OnlinePaymentNotification::class);

    $this->actingAsRole(Role::Guardian, $parent);
    $this->get("/orang-tua/keuangan/{$invoice->public_id}/status/{$transaction->public_id}")->assertInertia(fn (Assert $page) => $page
        ->component('Finance::Guardian/PaymentStatus')
        ->where('transaction.status.value', 'SETTLED')
        ->where('transaction.final', true)
        ->where('transaction.receipt_url', "/orang-tua/keuangan/pembayaran/{$payment->public_id}/kwitansi"));
});

it('pending → PENDING; expire → FAILED dan bisa dicoba lagi', function () {
    ['transaction' => $transaction, 'invoice' => $invoice, 'parent' => $parent] = onlineScenario();
    Notification::fake();

    $this->postJson('/webhooks/midtrans', midtransPayload($transaction, 'pending'))->assertOk();
    expect($invoice->fresh()->status->value)->toBe('PENDING');

    $this->postJson('/webhooks/midtrans', midtransPayload($transaction, 'expire'))->assertOk();
    expect($invoice->fresh()->status->value)->toBe('FAILED')
        ->and($transaction->fresh()->status->value)->toBe('FAILED')
        ->and(Payment::query()->count())->toBe(0);
    Notification::assertSentTo($parent, OnlinePaymentNotification::class, fn ($n) => ! $n->success);

    $this->actingAsRole(Role::Guardian, $parent);
    $this->postJson("/orang-tua/keuangan/{$invoice->public_id}/bayar")->assertOk();
});

it('nominal tidak cocok dan order tidak dikenal ditolak', function () {
    ['transaction' => $transaction, 'invoice' => $invoice] = onlineScenario();

    $wrong = midtransPayload($transaction, 'settlement', ['gross_amount' => '1000.00']);
    $wrong['signature_key'] = hash('sha512', $wrong['order_id'].$wrong['status_code'].'1000.00SB-Mid-server-test');
    $this->postJson('/webhooks/midtrans', $wrong)->assertStatus(422);

    $unknown = midtransPayload($transaction, 'settlement', ['order_id' => 'SKL-TIDAKADA']);
    $unknown['signature_key'] = hash('sha512', 'SKL-TIDAKADA200150000.00SB-Mid-server-test');
    $this->postJson('/webhooks/midtrans', $unknown)->assertNotFound();

    expect($invoice->fresh()->status->value)->toBe('UNPAID')->and(Payment::query()->count())->toBe(0);
});

it('settlement setelah pembayaran manual tetap dicatat sebagai kelebihan bayar', function () {
    ['transaction' => $transaction, 'invoice' => $invoice, 'operator' => $operator] = onlineScenario();
    $this->actingAsRole(Role::Operator, $operator);
    $this->post("/operator/keuangan/tagihan/{$invoice->public_id}/pembayaran", ['amount' => 150000, 'paid_on' => '2026-09-28']);

    $this->postJson('/webhooks/midtrans', midtransPayload($transaction, 'settlement'))->assertOk();

    expect(Payment::query()->count())->toBe(2)
        ->and(Payment::query()->where('method', 'GATEWAY')->sole()->note)->toContain('KELEBIHAN BAYAR')
        ->and(Invoice::query()->find($invoice->id)->paid_amount)->toBe(300000);
});
