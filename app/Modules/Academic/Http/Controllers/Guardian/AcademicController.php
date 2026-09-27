<?php

namespace App\Modules\Academic\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Http\Resources\LearningMaterialResource;
use App\Modules\Academic\Http\Resources\ScheduleResource;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Services\Timetable;
use App\Modules\People\Services\GuardianChildren;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Akademik anak: jadwal mingguan + mapel (guru & materi) rombel aktif anak terpilih.
 */
class AcademicController extends Controller
{
    public function index(Request $request, GuardianChildren $guardianChildren, ActivePeriod $period, Timetable $timetable): Response
    {
        /** @var User $user */
        $user = $request->user();
        $children = $guardianChildren->of($user);
        $child = $guardianChildren->selected($children, $request->string(GuardianChildren::QUERY_KEY)->value());

        $classroom = $child
            ? ClassEnrollment::query()
                ->where('student_id', $child->id)
                ->where('academic_year_id', $period->academicYearId() ?? 0)
                ->where('status', EnrollmentStatus::Active)
                ->with('classroom:id,public_id,name')
                ->first()?->classroom
            : null;

        $subjects = $classroom
            ? TeachingAssignment::query()
                ->where('classroom_id', $classroom->id)
                ->with([
                    'subject:id,public_id,code,name',
                    'teacher:id,name',
                    'materials' => fn ($q) => $q->latest()->with(['file', 'teachingAssignment.subject:id,name', 'teachingAssignment.classroom:id,name']),
                ])
                ->get()
                ->sortBy(fn (TeachingAssignment $a) => $a->subject->name)
                ->map(fn (TeachingAssignment $a) => [
                    'assignment' => $a->public_id,
                    'subject' => ['code' => $a->subject->code, 'name' => $a->subject->name],
                    'teacher' => $a->teacher->name,
                    'materials' => LearningMaterialResource::collection($a->materials)->resolve($request),
                ])->values()
            : collect();

        return Inertia::render('Academic::Guardian/Academic/Index', [
            ...$guardianChildren->switcherProps($children, $child),
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'classroom' => $classroom ? ['name' => $classroom->name] : null,
            'schedules' => $classroom ? ScheduleResource::collection($timetable->forClassroom($classroom->id))->resolve($request) : [],
            'subjects' => $subjects,
            'days' => Weekday::options(),
        ]);
    }
}
