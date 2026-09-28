<?php

namespace App\Modules\Dashboard\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Dashboard\Services\ChildTimeline;
use App\Modules\People\Services\GuardianChildren;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * GET /orang-tua/timeline?bulan=YYYY-MM — linimasa anak aktif per bulan.
 */
class TimelineController extends Controller
{
    public function __invoke(Request $request, GuardianChildren $children, ChildTimeline $timeline): Response
    {
        /** @var User $user */
        $user = $request->user();
        $list = $children->of($user);
        $child = $children->selected($list, $request->string(GuardianChildren::QUERY_KEY)->value());

        $value = $request->string('bulan')->value();
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1
            ? CarbonImmutable::parse($value.'-01')
            : CarbonImmutable::today()->startOfMonth();

        return Inertia::render('Dashboard::Guardian/Timeline', [
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'month' => $month->format('Y-m'),
            'entries' => $child ? $timeline->month($child, $user, $month) : [],
        ]);
    }
}
