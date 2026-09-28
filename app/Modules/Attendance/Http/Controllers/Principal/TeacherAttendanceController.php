<?php

namespace App\Modules\Attendance\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Services\AttendancePages;
use App\Modules\Attendance\Services\TeacherAttendanceRecap;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekap kehadiran guru per periode (read-only).
 */
class TeacherAttendanceController extends Controller
{
    public function index(Request $request, AttendancePages $pages, TeacherAttendanceRecap $recap): Response
    {
        [$from, $to] = $pages->range($request);

        return Inertia::render('Attendance::Shared/TeacherRecap', [
            'base' => '/kepala-sekolah/presensi-guru',
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'rows' => $recap->period($from, $to),
            'statuses' => TeacherAttendanceStatus::options(),
        ]);
    }
}
