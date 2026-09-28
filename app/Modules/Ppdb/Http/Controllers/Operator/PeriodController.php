<?php

namespace App\Modules\Ppdb\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Actions\SavePeriodAction;
use App\Modules\Ppdb\Http\Requests\PeriodRequest;
use App\Modules\Ppdb\Http\Resources\PeriodResource;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Periode PPDB beserta jalur, kuota, dan persyaratan dokumen.
 */
class PeriodController extends Controller
{
    public const BASE = '/operator/ppdb/periode';

    public function index(Request $request): Response
    {
        $periods = PpdbPeriod::query()->with(['academicYear', 'tracks.requirements'])->withCount('applicants')->orderByDesc('opens_at')->get();

        return Inertia::render('Ppdb::Operator/Periods/Index', [
            'periods' => PeriodResource::collection($periods)->resolve($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Ppdb::Operator/Periods/Form', ['period' => null, 'academicYears' => $this->years()]);
    }

    public function store(PeriodRequest $request, SavePeriodAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $save->handle($request->validated(), $user);

        return redirect(self::BASE)->with('success', 'Periode PPDB disimpan.');
    }

    public function edit(Request $request, PpdbPeriod $period): Response
    {
        $period->load(['academicYear', 'tracks.requirements']);

        return Inertia::render('Ppdb::Operator/Periods/Form', [
            'period' => (new PeriodResource($period))->resolve($request),
            'academicYears' => $this->years(),
        ]);
    }

    public function update(PeriodRequest $request, PpdbPeriod $period, SavePeriodAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $save->handle($request->validated(), $user, $period);

        return redirect(self::BASE)->with('success', 'Periode PPDB diperbarui.');
    }

    public function destroy(PpdbPeriod $period, AuditLogger $audit): RedirectResponse
    {
        if (PpdbApplicant::withTrashed()->where('ppdb_period_id', $period->id)->exists()) {
            throw ValidationException::withMessages(['period' => 'Periode yang sudah memiliki pendaftar tidak bisa dihapus; nonaktifkan saja.']);
        }

        $period->delete();
        $audit->record('ppdb', 'period_deleted', "Menghapus periode PPDB {$period->name}", $period);

        return redirect(self::BASE)->with('success', 'Periode dihapus.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function years(): array
    {
        return AcademicYear::query()->orderByDesc('start_date')->get(['public_id', 'name'])
            ->map(fn (AcademicYear $y) => ['value' => $y->public_id, 'label' => $y->name])->values()->all();
    }
}
