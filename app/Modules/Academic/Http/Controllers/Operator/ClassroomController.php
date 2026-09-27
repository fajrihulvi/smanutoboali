<?php

namespace App\Modules\Academic\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Actions\DeleteClassroomAction;
use App\Modules\Academic\Actions\SaveClassroomAction;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Http\Requests\ClassroomRequest;
use App\Modules\Academic\Http\Resources\ClassroomResource;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Services\AcademicOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request, AcademicOptions $options): Response
    {
        $this->authorize('viewAny', Classroom::class);

        $year = $options->selectedYear($request);

        $classrooms = Classroom::query()
            ->with('homeroomTeacher:id,public_id,name')
            ->withCount('activeEnrollments')
            ->where('academic_year_id', $year?->getKey() ?? 0)
            ->when($request->filled('tingkat'), fn ($q) => $q->where('grade', $request->integer('tingkat')))
            ->orderBy('grade')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Academic::Operator/Classrooms/Index', [
            'classrooms' => ClassroomResource::collection($classrooms),
            'year' => $year ? ['value' => $year->public_id, 'label' => $year->name, 'is_active' => $year->is_active] : null,
            'filters' => array_filter(['tingkat' => $request->query('tingkat')]),
            'years' => $options->years(),
            'grades' => $options->grades(),
            'teachers' => $options->teachers(),
        ]);
    }

    public function store(ClassroomRequest $request, SaveClassroomAction $save): RedirectResponse
    {
        $classroom = $save->handle($request->payload());

        return back()->with('success', "Rombel {$classroom->name} berhasil dibuat.");
    }

    public function show(Request $request, Classroom $classroom, AcademicOptions $options): Response
    {
        $this->authorize('update', $classroom);

        $classroom->load(['academicYear:id,public_id,name,is_active', 'homeroomTeacher:id,public_id,name'])
            ->loadCount('activeEnrollments');

        $students = ClassEnrollment::query()
            ->with('student:id,public_id,nis,name,gender,status')
            ->where('classroom_id', $classroom->getKey())
            ->where('status', EnrollmentStatus::Active)
            ->get()
            ->sortBy(fn (ClassEnrollment $e) => $e->student->name)
            ->map(fn (ClassEnrollment $e) => [
                'public_id' => $e->student->public_id,
                'nis' => $e->student->nis,
                'name' => $e->student->name,
                'gender' => $e->student->gender->value,
            ])->values();

        $assignments = TeachingAssignment::query()
            ->with(['subject:id,public_id,code,name', 'teacher:id,public_id,name'])
            ->where('classroom_id', $classroom->getKey())
            ->get()
            ->sortBy(fn (TeachingAssignment $a) => $a->subject->name)
            ->map(fn (TeachingAssignment $a) => [
                'subject' => ['public_id' => $a->subject->public_id, 'code' => $a->subject->code, 'name' => $a->subject->name],
                'teacher' => ['public_id' => $a->teacher->public_id, 'name' => $a->teacher->name],
            ])->values();

        return Inertia::render('Academic::Operator/Classrooms/Show', [
            'classroom' => (new ClassroomResource($classroom))->resolve($request),
            'students' => $students,
            'assignments' => $assignments,
            'subjects' => Subject::query()->where('is_active', true)->orderBy('name')->get(['public_id', 'code', 'name']),
            'grades' => $options->grades(),
            'teachers' => $options->teachers(),
        ]);
    }

    public function update(ClassroomRequest $request, Classroom $classroom, SaveClassroomAction $save): RedirectResponse
    {
        $save->handle($request->payload(), $classroom);

        return back()->with('success', 'Rombel berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom, DeleteClassroomAction $delete): RedirectResponse
    {
        $this->authorize('delete', $classroom);

        $year = $classroom->academicYear()->value('public_id');
        $delete->handle($classroom);

        return $this->backToIndex('operator.classrooms.index', to_route('operator.classrooms.index', ['tahun' => $year]))->with('success', "Rombel {$classroom->name} dihapus.");
    }
}
