<?php

namespace App\Modules\Dashboard\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Services\TeacherAttendanceRecap;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TeacherAttendanceRecap $attendance): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard::Teacher/Index', [
            'selfAttendance' => $attendance->card($user),
        ]);
    }
}
