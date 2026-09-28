<?php

namespace App\Modules\Finance\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use App\Modules\Finance\Actions\StartOnlinePaymentAction;
use App\Modules\Finance\Actions\SubmitPaymentProofAction;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Gateway\PaymentGateway;
use App\Modules\Finance\Http\Requests\PaymentRequest;
use App\Modules\Finance\Http\Resources\InvoiceResource;
use App\Modules\Finance\Models\GatewayTransaction;
use App\Modules\Finance\Models\Invoice;
use App\Modules\People\Services\GuardianChildren;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tagihan anak aktif: daftar (belum lunas / lunas), detail, unggah bukti transfer.
 */
class InvoiceController extends Controller
{
    public function index(Request $request, GuardianChildren $children): Response
    {
        /** @var User $user */
        $user = $request->user();
        $list = $children->of($user);
        $child = $children->selected($list, $request->string(GuardianChildren::QUERY_KEY)->value());
        $tab = $request->string('tab')->value() === 'lunas' ? 'lunas' : 'belum';

        $invoices = $child
            ? Invoice::query()
                ->where('student_id', $child->id)
                ->with(['student', 'feeType'])
                ->when($tab === 'lunas', fn (Builder $q) => $q->where('status', InvoiceStatus::Paid), fn (Builder $q) => $q->outstanding())
                ->orderBy($tab === 'lunas' ? 'paid_at' : 'due_date', $tab === 'lunas' ? 'desc' : 'asc')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString()
            : null;

        $outstanding = $child ? Invoice::query()->where('student_id', $child->id)->outstanding() : null;

        return Inertia::render('Finance::Guardian/Index', [
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'tab' => $tab,
            'invoices' => $invoices ? InvoiceResource::collection($invoices) : null,
            'outstanding' => [
                'amount' => $outstanding ? (int) (clone $outstanding)->sum(DB::raw('total - paid_amount')) : 0,
                'count' => $outstanding ? (clone $outstanding)->count() : 0,
            ],
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        $invoice->load(['student', 'feeType', 'items', 'payments.proof', 'payments.submitter', 'payments.verifier']);

        return Inertia::render('Finance::Guardian/Show', [
            'invoice' => (new InvoiceResource($invoice))->detail()->resolve($request),
            'instructions' => (string) app(SettingsService::class)->get(SettingKey::PaymentInstructions) ?: null,
            'onlinePayment' => app(PaymentGateway::class)->isEnabled(),
            'today' => CarbonImmutable::today()->toDateString(),
        ]);
    }

    /**
     * POST /orang-tua/keuangan/{invoice}/bayar — buat sesi Snap untuk sisa tagihan.
     */
    public function pay(Request $request, Invoice $invoice, StartOnlinePaymentAction $start, PaymentGateway $gateway): JsonResponse
    {
        $this->authorize('pay', $invoice);

        /** @var User $user */
        $user = $request->user();
        $transaction = $start->handle($invoice, $user, url('/orang-tua/keuangan/'.$invoice->public_id));
        $statusUrl = "/orang-tua/keuangan/{$invoice->public_id}/status/{$transaction->public_id}";

        return response()->json([
            'token' => $transaction->snap_token,
            'redirect_url' => $transaction->redirect_url,
            'status_url' => $statusUrl,
            ...$gateway->clientConfig(),
        ]);
    }

    /**
     * Status percobaan bayar — selalu dari DB (hasil webhook), bukan dari redirect Snap.
     */
    public function status(Request $request, Invoice $invoice, GatewayTransaction $transaction): Response
    {
        $this->authorize('view', $invoice);
        abort_unless($transaction->invoice_id === $invoice->id, 404);
        $invoice->load(['student', 'feeType']);

        return Inertia::render('Finance::Guardian/PaymentStatus', [
            'invoice' => (new InvoiceResource($invoice))->resolve($request),
            'transaction' => [
                'public_id' => $transaction->public_id,
                'order_id' => $transaction->order_id,
                'amount' => $transaction->amount,
                'status' => ['value' => $transaction->status->value, 'label' => $transaction->status->label()],
                'final' => $transaction->status->isFinal(),
                'payment_type' => $transaction->payment_type,
                'receipt_url' => $transaction->payment_id
                    ? '/orang-tua/keuangan/pembayaran/'.$transaction->payment()->value('public_id').'/kwitansi'
                    : null,
            ],
        ]);
    }

    public function storeProof(PaymentRequest $request, Invoice $invoice, SubmitPaymentProofAction $submit): RedirectResponse
    {
        $this->authorize('pay', $invoice);

        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $proof */
        $proof = $request->proof();
        $submit->handle($invoice, $user, $request->integer('amount'), $request->paidOn(), $proof, $request->string('note')->value() ?: null);

        return back()->with('success', 'Bukti transfer terkirim. Sekolah akan memverifikasi.');
    }
}
