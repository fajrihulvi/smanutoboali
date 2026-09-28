<?php

namespace App\Modules\Finance\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Finance\Actions\CancelInvoiceAction;
use App\Modules\Finance\Actions\RecordPaymentAction;
use App\Modules\Finance\Actions\UpdateInvoiceAction;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Http\Requests\PaymentRequest;
use App\Modules\Finance\Http\Resources\InvoiceResource;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\ActiveClassrooms;
use App\Modules\Finance\Services\FinanceReport;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar & detail tagihan (operator): catat pembayaran, ubah jatuh tempo/catatan, batalkan.
 */
class InvoiceController extends Controller
{
    public const PER_PAGE = 20;

    public function __construct(private ActiveClassrooms $classrooms) {}

    public function index(Request $request, ActivePeriod $period, FinanceReport $report): Response
    {
        $this->authorize('manage', Invoice::class);

        $filters = [
            'q' => $request->string('q')->trim()->value(),
            'status' => $request->string('status')->value(),
            'jenis' => $request->string('jenis')->value(),
            'rombel' => $request->string('rombel')->value(),
            'periode' => $request->string('periode')->value(),
        ];
        $yearId = $period->academicYearId() ?? 0;

        $invoices = Invoice::query()
            ->with(['student', 'feeType'])
            ->when($filters['q'] !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('number', 'like', "%{$filters['q']}%")
                ->orWhereHas('student', fn (Builder $s) => $s->search($filters['q']))))
            ->when($filters['status'] === 'TERLAMBAT', fn (Builder $q) => $q->outstanding()->whereDate('due_date', '<', CarbonImmutable::today()->toDateString()))
            ->when(InvoiceStatus::tryFrom($filters['status']) !== null, fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['jenis'] !== '', fn (Builder $q) => $q->whereHas('feeType', fn (Builder $f) => $f->where('public_id', $filters['jenis'])))
            ->when($filters['periode'] !== '', fn (Builder $q) => $q->where('period', $filters['periode']))
            ->when($filters['rombel'] !== '', fn (Builder $q) => $q->whereHas('student.enrollments', fn (Builder $e) => $e
                ->where('academic_year_id', $yearId)->where('status', EnrollmentStatus::Active)
                ->whereHas('classroom', fn (Builder $c) => $c->where('public_id', $filters['rombel']))))
            ->orderByRaw("CASE WHEN status IN ('UNPAID','PENDING','FAILED') THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $names = $this->classrooms->names($invoices->getCollection()->pluck('student_id'));

        return Inertia::render('Finance::Operator/Invoices/Index', [
            'invoices' => InvoiceResource::collection($invoices->through(fn (Invoice $i) => (new InvoiceResource($i))->withClassroom($names[$i->student_id] ?? null))),
            'filters' => array_filter($filters),
            'summary' => $report->summary(CarbonImmutable::today()),
            'feeTypes' => FeeType::query()->orderBy('name')->get(['public_id', 'name'])->map(fn (FeeType $f) => ['value' => $f->public_id, 'label' => $f->name])->values(),
            'classrooms' => Classroom::query()->where('academic_year_id', $yearId)->orderBy('grade')->orderBy('name')->get(['public_id', 'name'])
                ->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
            'statuses' => [
                ...array_map(fn (InvoiceStatus $s) => $s->toOption(), InvoiceStatus::cases()),
                ['value' => 'TERLAMBAT', 'label' => 'Lewat jatuh tempo'],
            ],
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('manage', Invoice::class);
        $invoice->load(['student', 'feeType', 'items', 'payments.proof', 'payments.submitter', 'payments.verifier']);

        return Inertia::render('Finance::Operator/Invoices/Show', [
            'invoice' => (new InvoiceResource($invoice))->detail()->withClassroom($this->classrooms->names([$invoice->student_id])[$invoice->student_id] ?? null)->resolve($request),
            'today' => CarbonImmutable::today()->toDateString(),
        ]);
    }

    public function update(Request $request, Invoice $invoice, UpdateInvoiceAction $update): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);
        $data = $request->validate([
            'due_date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['due_date' => 'jatuh tempo', 'notes' => 'catatan']);

        $update->handle($invoice, CarbonImmutable::parse($data['due_date']), $data['notes'] ?? null);

        return back()->with('success', 'Tagihan diperbarui.');
    }

    public function cancel(Request $request, Invoice $invoice, CancelInvoiceAction $cancel): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan']);

        /** @var User $user */
        $user = $request->user();
        $cancel->handle($invoice, $user, $data['reason']);

        return back()->with('success', 'Tagihan dibatalkan.');
    }

    public function storePayment(PaymentRequest $request, Invoice $invoice, RecordPaymentAction $record): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);

        /** @var User $user */
        $user = $request->user();
        $payment = $record->handle($invoice, $user, $request->integer('amount'), $request->paidOn(), $request->string('note')->value() ?: null, $request->proof());

        return back()->with('success', "Pembayaran dicatat ({$payment->receipt_number}).");
    }
}
