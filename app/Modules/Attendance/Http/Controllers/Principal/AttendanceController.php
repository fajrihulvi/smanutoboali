<?php

namespace App\Modules\Attendance\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Services\AttendancePages;
use App\Modules\People\Models\Student;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Monitoring presensi (read-only): statistik periode per rombel → siswa → kalender siswa.
 */
class AttendanceController extends Controller
{
    private const BASE = '/kepala-sekolah/presensi';

    public function __construct(
        private AttendancePages $pages,
        private ActivePeriod $period,
    ) {}

    public function index(Request $request): Response
    {
        [$from, $to] = $this->pages->range($request);

        $classrooms = Classroom::query()
            ->where('academic_year_id', $this->period->academicYearId() ?? 0)
            ->with('activeEnrollments:id,classroom_id,student_id')
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        return Inertia::render('Attendance::Principal/Index', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'classrooms' => $this->pages->classroomStats($classrooms, $from, $to),
            'statuses' => AttendanceStatus::options(),
        ]);
    }

    public function classroom(Request $request, Classroom $classroom): Response
    {
        [$from, $to] = $this->pages->range($request);

        return Inertia::render('Attendance::Principal/Classroom', [
            'classroom' => ['public_id' => $classroom->public_id, 'name' => $classroom->name],
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'students' => $this->pages->studentStats($classroom, $from, $to),
            'statuses' => AttendanceStatus::options(),
        ]);
    }

    public function recap(Request $request, Classroom $classroom): Response
    {
        return Inertia::render('Attendance::Shared/ClassroomRecap', $this->pages->classroomRecap(self::BASE, $classroom, $this->pages->month($request)));
    }

    public function student(Request $request, Student $student): Response
    {
        $this->authorize('view', $student);

        return Inertia::render('Attendance::Shared/StudentCalendar', $this->pages->studentCalendar(self::BASE, $student, $this->pages->month($request)));
    }
}
