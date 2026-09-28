<?php

namespace App\Modules\Assessment\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Services\AssessmentSettings;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Modules\People\Services\GuardianChildren;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Nilai anak yang sudah DIPUBLIKASIKAN guru, per mapel, semester terpilih.
 * Nilai akhir hanya tampil bila semua komponen mapel itu sudah dipublikasikan dan terisi.
 */
class GradeController extends Controller
{
    public function index(
        Request $request,
        GuardianChildren $guardianChildren,
        SemesterPicker $semesters,
        ActivePeriod $period,
        AssessmentSettings $settings,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $children = $guardianChildren->of($user);
        $child = $guardianChildren->selected($children, $request->string(GuardianChildren::QUERY_KEY)->value());
        $semester = $semesters->selected($request);

        $classroomId = $child
            ? ClassEnrollment::query()->where('student_id', $child->id)->where('academic_year_id', $period->academicYearId() ?? 0)
                ->where('status', EnrollmentStatus::Active)->value('classroom_id')
            : null;

        $subjects = collect();

        if ($child && $classroomId && $semester) {
            $assignments = TeachingAssignment::query()->where('classroom_id', $classroomId)->with('subject:id,name')->get()->keyBy('id');
            $components = GradeComponent::query()
                ->where('semester_id', $semester->id)
                ->whereIn('teaching_assignment_id', $assignments->keys())
                ->orderBy('position')->orderBy('id')
                ->get();
            $scores = Grade::query()
                ->where('student_id', $child->id)
                ->whereIn('grade_component_id', $components->modelKeys())
                ->pluck('score', 'grade_component_id');

            $subjects = $components->groupBy('teaching_assignment_id')->map(function ($parts, $assignmentId) use ($assignments, $scores, $settings) {
                $published = $parts->filter(fn (GradeComponent $c) => $c->isPublished());
                $allPublished = $published->count() === $parts->count() && (int) $parts->sum('weight') === 100;
                $complete = $allPublished && $parts->every(fn (GradeComponent $c) => $scores->has($c->id));
                $final = $complete
                    ? (int) round($parts->sum(fn (GradeComponent $c) => (float) $scores->get($c->id) * $c->weight) / 100, 0, PHP_ROUND_HALF_UP)
                    : null;

                return [
                    'subject' => $assignments->get($assignmentId)?->subject->name,
                    'components' => $published->map(fn (GradeComponent $c) => [
                        'public_id' => $c->public_id,
                        'name' => $c->name,
                        'weight' => $c->weight,
                        'score' => $scores->has($c->id) ? (float) $scores->get($c->id) : null,
                        'published_at' => $c->published_at?->toIso8601String(),
                    ])->values(),
                    'final' => $final,
                    'predicate' => $final === null ? null : $settings->predicateFor($final)->value,
                ];
            })->filter(fn (array $row) => $row['components']->isNotEmpty())->sortBy('subject')->values();
        }

        return Inertia::render('Assessment::Guardian/Index', [
            ...$guardianChildren->switcherProps($children, $child),
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'semester' => $semester?->public_id,
            'semesters' => $semesters->toOptions(),
            'subjects' => $subjects,
            'kkm' => $settings->kkm(),
        ]);
    }
}
