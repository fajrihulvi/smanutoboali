<?php

namespace App\Modules\People\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Actions\CreateStudentAction;
use App\Modules\People\Actions\DeleteStudentAction;
use App\Modules\People\Actions\UpdateStudentAction;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Enums\Religion;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Http\Requests\StudentRequest;
use App\Modules\People\Http\Resources\StudentResource;
use App\Modules\People\Models\Student;
use App\Modules\People\Services\StudentDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request, StudentDirectoryService $directory): Response
    {
        $this->authorize('create', Student::class);

        /** @var User $user */
        $user = $request->user();
        $filters = $directory->filters($request);

        return Inertia::render('People::Operator/Students/Index', [
            'students' => StudentResource::collection($directory->paginate($user, $filters)),
            'filters' => array_filter($filters),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Student::class);

        return Inertia::render('People::Operator/Students/Form', [
            'student' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(StudentRequest $request, CreateStudentAction $create): RedirectResponse
    {
        $student = $create->handle($request->payload(), $request->file('photo'));

        return to_route('operator.students.edit', ['student' => $student, 'tab' => 'orang-tua'])
            ->with('success', "Siswa {$student->name} berhasil ditambahkan. Hubungkan orang tuanya.");
    }

    public function edit(Request $request, Student $student): Response
    {
        $this->authorize('update', $student);

        $student->load(['photo', 'guardians', 'enrollments' => fn ($q) => $q->with(['classroom:id,name', 'academicYear:id,name,start_date'])->latest('id')]);

        return Inertia::render('People::Operator/Students/Form', [
            'student' => (new StudentResource($student))->withGuardianContact()->resolve($request),
            'enrollments' => $student->enrollments
                ->sortByDesc(fn ($e) => $e->academicYear->start_date)
                ->map(fn ($e) => [
                    'academic_year' => $e->academicYear->name,
                    'classroom' => $e->classroom->name,
                    'status' => ['value' => $e->status->value, 'label' => $e->status->label()],
                ])->values(),
            'options' => $this->options(),
            'tab' => in_array($request->query('tab'), ['identitas', 'orang-tua', 'riwayat'], true) ? $request->query('tab') : 'identitas',
        ]);
    }

    public function update(StudentRequest $request, Student $student, UpdateStudentAction $update): RedirectResponse
    {
        $update->handle($student, $request->payload(), $request->file('photo'), $request->boolean('remove_photo'));

        return to_route('operator.students.edit', $student)->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student, DeleteStudentAction $delete): RedirectResponse
    {
        $this->authorize('delete', $student);

        $delete->handle($student);

        return $this->backToIndex('operator.students.index', to_route('operator.students.index'))->with('success', "Data siswa {$student->name} dihapus.");
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function options(): array
    {
        $map = fn (array $cases) => array_map(fn (\BackedEnum $case) => ['value' => (string) $case->value, 'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value], $cases);

        return [
            'genders' => $map(Gender::cases()),
            'religions' => $map(Religion::cases()),
            'statuses' => $map(StudentStatus::cases()),
            'relationships' => $map(GuardianRelationship::cases()),
        ];
    }
}
