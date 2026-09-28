<?php

namespace App\Modules\Finance\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\DeleteFeeTypeAction;
use App\Modules\Finance\Actions\SaveFeeTypeAction;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Http\Requests\FeeTypeRequest;
use App\Modules\Finance\Http\Resources\FeeTypeResource;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeeTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage', Invoice::class);

        return Inertia::render('Finance::Operator/FeeTypes', [
            'feeTypes' => FeeTypeResource::collection(FeeType::query()->withCount('invoices')->orderByDesc('is_active')->orderBy('name')->get())->resolve($request),
            'frequencies' => array_map(fn (FeeFrequency $f) => ['value' => $f->value, 'label' => $f->label()], FeeFrequency::cases()),
        ]);
    }

    public function store(FeeTypeRequest $request, SaveFeeTypeAction $save): RedirectResponse
    {
        $save->handle($request->payload());

        return back()->with('success', 'Jenis biaya ditambahkan.');
    }

    public function update(FeeTypeRequest $request, FeeType $feeType, SaveFeeTypeAction $save): RedirectResponse
    {
        $save->handle($request->payload(), $feeType);

        return back()->with('success', 'Jenis biaya diperbarui.');
    }

    public function destroy(FeeType $feeType, DeleteFeeTypeAction $delete): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);
        $delete->handle($feeType);

        return back()->with('success', 'Jenis biaya dihapus.');
    }
}
