<?php

namespace App\Modules\Academic\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Http\Resources\ScheduleResource;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Services\Timetable;
use App\Modules\School\Services\SchoolProfile;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Monitoring akademik (read-only): ringkasan jadwal per rombel tahun aktif + grid rombel terpilih.
 */
class AcademicController extends Controller
{
    public function index(Request $request, ActivePeriod $period, Timetable $timetable, SchoolProfile $school): Response
    {
        $yearId = $period->academicYearId() ?? 0;
        $level = $school->level();

        $classrooms = Classroom::query()
            ->where('academic_year_id', $yearId)
            ->with(['homeroomTeacher:id,name', 'teachingAssignments.subject:id,name'])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $schedules = Schedule::query()
            ->where('academic_year_id', $yearId)
            ->with('teachingAssignment:id,classroom_id')
            ->get(['id', 'teaching_assignment_id', 'start_time', 'end_time']);

        $scheduledAssignments = $schedules->pluck('teaching_assignment_id')->unique()->flip();
        $minutesByClassroom = $schedules->groupBy(fn (Schedule $s) => $s->teachingAssignment->classroom_id)
            ->map(fn ($items) => $items->sum(fn (Schedule $s) => CarbonImmutable::parse($s->start_time)->diffInMinutes(CarbonImmutable::parse($s->end_time))));

        $selected = $classrooms->firstWhere('public_id', $request->string('rombel')->value()) ?? $classrooms->first();

        return Inertia::render('Academic::Principal/Academic/Index', [
            'year' => $period->academicYear()?->name,
            'classrooms' => $classrooms->map(fn (Classroom $c) => [
                'public_id' => $c->public_id,
                'name' => $c->name,
                'grade_label' => $level->gradeLabel($c->grade),
                'homeroom_teacher' => $c->homeroomTeacher?->name,
                'minutes_per_week' => (int) $minutesByClassroom->get($c->id, 0),
                'subjects_count' => $c->teachingAssignments->count(),
                'unscheduled_subjects' => $c->teachingAssignments
                    ->reject(fn (TeachingAssignment $a) => $scheduledAssignments->has($a->id))
                    ->map(fn (TeachingAssignment $a) => $a->subject->name)
                    ->sort()->values(),
            ])->values(),
            'classroom' => $selected ? ['public_id' => $selected->public_id, 'name' => $selected->name] : null,
            'schedules' => $selected ? ScheduleResource::collection($timetable->forClassroom($selected->id))->resolve($request) : [],
            'days' => Weekday::options(),
        ]);
    }
}
