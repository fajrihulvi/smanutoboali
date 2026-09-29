<?php

namespace App\Modules\Ppdb\Services;

use App\Modules\Ppdb\Models\PpdbPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Periode terpilih di halaman internal (?periode=): default periode terbaru.
 */
class PeriodPicker
{
    /**
     * @return Collection<int, PpdbPeriod>
     */
    public function all(): Collection
    {
        return PpdbPeriod::query()->with(['academicYear', 'tracks'])->orderByDesc('opens_at')->get();
    }

    /**
     * @param  Collection<int, PpdbPeriod>  $periods
     */
    public function selected(Request $request, Collection $periods): ?PpdbPeriod
    {
        $key = $request->string('periode')->value();

        return ($key !== '' ? $periods->firstWhere('public_id', $key) : null) ?? $periods->first();
    }

    /**
     * @param  Collection<int, PpdbPeriod>  $periods
     * @return list<array{value: string, label: string}>
     */
    public function options(Collection $periods): array
    {
        return $periods->map(fn (PpdbPeriod $p) => ['value' => $p->public_id, 'label' => $p->name])->values()->all();
    }
}
