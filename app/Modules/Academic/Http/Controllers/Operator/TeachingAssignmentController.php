<?php

namespace App\Modules\Academic\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Actions\AssignTeacherAction;
use App\Modules\Academic\Http\Requests\AssignTeacherRequest;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Services\AcademicOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Matriks penugasan mengajar: baris = rombel, kolom = mapel aktif, sel = guru.
 */
class TeachingAssignmentController extends Controller
{
    public function index(Request $request, AcademicOptions $options): Response
    {
        $this->authorize('viewAny', Classroom::class);

        $year = $options->selectedYear($request);

        $classrooms = Classroom::query()
            ->where('academic_year_id', $year?->getKey() ?? 0)
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'public_id', 'name', 'grade']);

        $assignments = TeachingAssignment::query()
            ->whereIn('classroom_id', $classrooms->modelKeys())
            ->with(['classroom:id,public_id', 'subject:id,public_id', 'teacher:id,public_id'])
            ->get()
            ->mapWithKeys(fn (TeachingAssignment $a) => [
                $a->classroom->public_id.':'.$a->subject->public_id => $a->teacher->public_id,
            ]);

        return Inertia::render('Academic::Operator/Assignments/Index', [
            'year' => $year ? ['value' => $year->public_id, 'label' => $year->name, 'is_active' => $year->is_active] : null,
            'years' => $options->years(),
            'classrooms' => $classrooms->map(fn (Classroom $c) => ['public_id' => $c->public_id, 'name' => $c->name])->values(),
            'subjects' => Subject::query()->where('is_active', true)->orderBy('name')->get(['public_id', 'code', 'name']),
            'teachers' => $options->teachers(),
            'assignments' => $assignments,
        ]);
    }

    public function update(AssignTeacherRequest $request, AssignTeacherAction $assign): RedirectResponse
    {
        $assign->handle($request->classroom(), $request->subject(), $request->teacher());

        return back()->with('success', 'Penugasan disimpan.');
    }
}
