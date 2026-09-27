<?php

namespace App\Modules\Academic\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Http\Resources\ScheduleResource;
use App\Modules\Academic\Services\Timetable;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jadwal Saya: hari ini + mingguan (tahun ajaran aktif).
 */
class ScheduleController extends Controller
{
    public function index(Request $request, ActivePeriod $period, Timetable $timetable): Response
    {
        /** @var User $user */
        $user = $request->user();
        $teacher = Teacher::activeForUser($user);
        $yearId = $period->academicYearId();

        $schedules = $teacher && $yearId ? $timetable->forTeacher($teacher->id, $yearId) : collect();
        $today = Weekday::tryFrom(now()->dayOfWeekIso);

        return Inertia::render('Academic::Teacher/Schedules/Index', [
            'hasTeacherProfile' => $teacher !== null,
            'today' => $today ? ['value' => $today->value, 'label' => $today->label()] : null,
            'schedules' => ScheduleResource::collection($schedules)->resolve($request),
            'days' => Weekday::options(),
        ]);
    }
}
