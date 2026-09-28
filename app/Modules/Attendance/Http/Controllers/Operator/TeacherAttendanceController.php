<?php

namespace App\Modules\Attendance\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Actions\CorrectTeacherAttendanceAction;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Http\Requests\CorrectTeacherAttendanceRequest;
use App\Modules\Attendance\Services\AttendancePages;
use App\Modules\Attendance\Services\TeacherAttendanceRecap;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Presensi guru untuk operator: daftar harian + koreksi/input, dan rekap periode.
 */
class TeacherAttendanceController extends Controller
{
    public const BASE = '/operator/presensi-guru';

    public function __construct(
        private AttendancePages $pages,
        private TeacherAttendanceRecap $recap,
    ) {}

    public function index(Request $request): Response
    {
        $date = $this->pages->date($request);

        return Inertia::render('Attendance::Operator/Teachers/Index', [
            'date' => $date->toDateString(),
            'rows' => $this->recap->daily($date),
            'statuses' => TeacherAttendanceStatus::options(),
        ]);
    }

    public function update(CorrectTeacherAttendanceRequest $request, Teacher $teacher, CorrectTeacherAttendanceAction $correct): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $correct->handle($teacher, $request->attendanceDate(), $request->payload(), $user);

        return back()->with('success', "Presensi {$teacher->name} diperbarui.");
    }

    public function recap(Request $request): Response
    {
        [$from, $to] = $this->pages->range($request);

        return Inertia::render('Attendance::Shared/TeacherRecap', [
            'base' => self::BASE,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'rows' => $this->recap->period($from, $to),
            'statuses' => TeacherAttendanceStatus::options(),
        ]);
    }
}
