<?php

namespace App\Modules\Ppdb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ppdb\Services\PeriodPicker;
use App\Modules\Ppdb\Services\PpdbDashboard;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard PPDB (operator & kepala sekolah).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, PeriodPicker $picker, PpdbDashboard $dashboard): Response
    {
        $periods = $picker->all();
        $period = $picker->selected($request, $periods);
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/ppdb';

        return Inertia::render('Ppdb::Shared/Dashboard', [
            'base' => $base,
            'periods' => $picker->options($periods),
            'period' => $period ? [
                'public_id' => $period->public_id,
                'name' => $period->name,
                'is_open' => $period->isOpen(),
                'closes_at' => $period->closes_at->toIso8601String(),
                'results_published_at' => $period->results_published_at?->toIso8601String(),
            ] : null,
            'stats' => $period ? $dashboard->for($period, CarbonImmutable::today()) : null,
        ]);
    }
}
