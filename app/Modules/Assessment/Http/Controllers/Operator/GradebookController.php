<?php

namespace App\Modules\Assessment\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Services\GradeAccess;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Buku nilai read-only (operator: grade.view; kepala sekolah memakai halaman yang sama lewat rekap).
 */
class GradebookController extends Controller
{
    public function __construct(
        private SemesterPicker $semesters,
        private GradeAccess $access,
    ) {}

    public function index(Request $request, ActivePeriod $period): Response
    {
        $semester = $this->semesters->selected($request);

        $classrooms = Classroom::query()
            ->where('academic_year_id', $period->academicYearId() ?? 0)
            ->orderBy('grade')->orderBy('name')
            ->get(['id', 'public_id', 'name']);

        $classroom = $classrooms->firstWhere('public_id', $request->string('rombel')->value()) ?? $classrooms->first();

        $assignments = $classroom
            ? TeachingAssignment::query()
                ->where('classroom_id', $classroom->id)
                ->with(['subject:id,name', 'teacher:id,name'])
                ->get()
                ->sortBy(fn (TeachingAssignment $a) => $a->subject->name)
                ->values()
            : collect();

        $components = $semester
            ? GradeComponent::query()->where('semester_id', $semester->id)->whereIn('teaching_assignment_id', $assignments->pluck('id'))
                ->get(['teaching_assignment_id', 'weight', 'published_at'])->groupBy('teaching_assignment_id')
            : collect();

        return Inertia::render('Assessment::Operator/Index', [
            'semester' => $semester?->public_id,
            'semesters' => $this->semesters->toOptions(),
            'classrooms' => $classrooms->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
            'classroom' => $classroom?->public_id,
            'assignments' => $assignments->map(fn (TeachingAssignment $a) => [
                'public_id' => $a->public_id,
                'subject' => $a->subject->name,
                'teacher' => $a->teacher->name,
                'components' => $components->get($a->id, collect())->count(),
                'total_weight' => (int) $components->get($a->id, collect())->sum('weight'),
            ]),
        ]);
    }

    public function show(Request $request, TeachingAssignment $assignment): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($this->access->canView($user, $assignment), 403);

        $semester = $this->semesters->selected($request);
        abort_if($semester === null, 404);

        return Inertia::render('Assessment::Shared/Gradebook', [
            ...Gradebook::make($assignment, $semester)->toProps(readOnly: true),
            'semesters' => $this->semesters->toOptions(),
            'types' => [],
            'base' => '/operator/nilai',
        ]);
    }
}
