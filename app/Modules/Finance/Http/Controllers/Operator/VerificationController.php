<?php

namespace App\Modules\Finance\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Actions\ReviewPaymentAction;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Http\Resources\PaymentResource;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Antrean verifikasi bukti transfer dari orang tua (terlama dulu).
 */
class VerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage', Invoice::class);

        $payments = Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->with(['invoice.student', 'invoice.feeType', 'proof', 'submitter', 'verifier'])
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Finance::Operator/Verification', [
            'payments' => PaymentResource::collection($payments->through(fn (Payment $p) => (new PaymentResource($p))->withInvoice())),
        ]);
    }

    public function review(Request $request, Payment $payment, ReviewPaymentAction $review): RedirectResponse
    {
        $this->authorize('review', $payment);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'required_if:decision,reject', 'string', 'max:255'],
        ], ['reason.required_if' => 'Tulis alasan penolakan.'], ['reason' => 'alasan']);

        /** @var User $user */
        $user = $request->user();
        $approve = $data['decision'] === 'approve';
        $review->handle($payment, $user, $approve, $data['reason'] ?? null);

        return back()->with('success', $approve ? 'Pembayaran dikonfirmasi.' : 'Bukti transfer ditolak.');
    }
}
