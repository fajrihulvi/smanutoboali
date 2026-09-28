<?php

namespace App\Modules\Finance\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Finance\Actions\GenerateInvoicesAction;
use App\Modules\Finance\Http\Requests\GenerateInvoicesRequest;
use App\Modules\Finance\Http\Resources\BatchResource;
use App\Modules\Finance\Http\Resources\FeeTypeResource;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceBatch;
use App\Modules\Finance\Services\InvoicePlanner;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Generate tagihan massal: form → preview (JSON) → eksekusi (Job) + riwayat batch.
 */
class GenerateController extends Controller
{
    public function create(Request $request, ActivePeriod $period): Response
    {
        $this->authorize('manage', Invoice::class);
        $yearId = $period->academicYearId() ?? 0;
        $classrooms = Classroom::query()->where('academic_year_id', $yearId)->orderBy('grade')->orderBy('name')->get(['public_id', 'name', 'grade']);

        return Inertia::render('Finance::Operator/Generate', [
            'feeTypes' => FeeTypeResource::collection(FeeType::query()->where('is_active', true)->orderBy('name')->get())->resolve($request),
            'classrooms' => $classrooms->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name, 'grade' => $c->grade])->values(),
            'grades' => $classrooms->pluck('grade')->unique()->sort()->values(),
            'batches' => BatchResource::collection(InvoiceBatch::query()->with(['feeType', 'creator'])->latest('id')->limit(10)->get())->resolve($request),
            'defaults' => [
                'month' => CarbonImmutable::today()->format('Y-m'),
                'due_date' => CarbonImmutable::today()->day(10)->addMonthNoOverflow()->toDateString(),
                'period_label' => $period->academicYear()->name ?? '',
            ],
        ]);
    }

    public function preview(GenerateInvoicesRequest $request, InvoicePlanner $planner): JsonResponse
    {
        /** @var FeeType $feeType */
        $feeType = $request->feeType();
        [$periodKey, $label] = $request->period();

        return response()->json([
            'label' => "{$feeType->name} {$label}",
            ...$planner->preview($feeType, $periodKey, $request->integer('amount'), $request->scope()),
        ]);
    }

    public function store(GenerateInvoicesRequest $request, GenerateInvoicesAction $generate): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var FeeType $feeType */
        $feeType = $request->feeType();
        [$periodKey, $label] = $request->period();

        $generate->handle($user, $feeType, $periodKey, $label, $request->integer('amount'), $request->dueDate(), $request->scope());

        return back()->with('success', "Tagihan {$feeType->name} {$label} sedang dibuat. Status tampil di riwayat di bawah.");
    }
}
