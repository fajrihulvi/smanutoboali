<?php

namespace App\Modules\Attendance\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Services\AttendancePages;
use App\Modules\Attendance\Services\AttendanceRecap;
use App\Modules\People\Services\GuardianChildren;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Presensi anak: status hari ini + kalender bulanan + ringkasan.
 */
class AttendanceController extends Controller
{
    public function index(Request $request, GuardianChildren $guardianChildren, AttendancePages $pages, AttendanceRecap $recap): Response
    {
        /** @var User $user */
        $user = $request->user();
        $children = $guardianChildren->of($user);
        $child = $guardianChildren->selected($children, $request->string(GuardianChildren::QUERY_KEY)->value());
        $month = $pages->month($request);
        $today = CarbonImmutable::today();

        $todayStatus = $child
            ? $recap->dailyStatuses($today, $today, studentIds: [$child->id])->get($child->id)?->first()
            : null;

        return Inertia::render('Attendance::Guardian/Index', [
            ...$guardianChildren->switcherProps($children, $child),
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'today' => ['date' => $today->toDateString(), 'status' => $todayStatus?->value],
            'month' => $month->format('Y-m'),
            'calendar' => $child ? $recap->studentMonth($child, $month) : null,
            'statuses' => AttendanceStatus::options(),
        ]);
    }
}
