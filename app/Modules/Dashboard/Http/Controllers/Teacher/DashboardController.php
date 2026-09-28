<?php

namespace App\Modules\Dashboard\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Services\TeacherAttendanceRecap;
use App\Modules\ChildCondition\Http\Resources\ConditionResource;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Services\ConditionAccess;
use App\Modules\ChildCondition\Services\ConditionQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TeacherAttendanceRecap $attendance, ConditionAccess $access, ConditionQuery $conditions): Response
    {
        /** @var User $user */
        $user = $request->user();
        $teacher = $access->teacherFor($user);

        return Inertia::render('Dashboard::Teacher/Index', [
            'selfAttendance' => $attendance->card($user),
            // Widget "Kondisi Anak Hari Ini" (S22).
            'conditions' => [
                'today' => $teacher
                    ? $conditions->todayForTeacher($teacher, CarbonImmutable::today())
                        ->map(fn (ChildCondition $c) => (new ConditionResource($c))->summary()->resolve($request))
                        ->values()
                    : [],
                'newCount' => $teacher ? $conditions->newCountForTeacher($teacher) : 0,
            ],
        ]);
    }
}
