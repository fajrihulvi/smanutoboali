<?php

namespace App\Modules\People\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\People\Actions\CreateTeacherAction;
use App\Modules\People\Actions\DeletePersonAction;
use App\Modules\People\Actions\LinkPersonAccountAction;
use App\Modules\People\Actions\UpdateTeacherAction;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Http\Requests\LinkAccountRequest;
use App\Modules\People\Http\Requests\TeacherRequest;
use App\Modules\People\Http\Resources\TeacherResource;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Services\PeopleDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(Request $request, PeopleDirectoryService $directory): Response
    {
        $this->authorize('viewAny', Teacher::class);
        $filters = $directory->teacherFilters($request);

        return Inertia::render('People::Operator/Teachers/Index', [
            'teachers' => TeacherResource::collection($directory->teachers($filters)),
            'filters' => array_filter($filters),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Teacher::class);

        return Inertia::render('People::Operator/Teachers/Form', [
            'teacher' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(TeacherRequest $request, CreateTeacherAction $create): RedirectResponse
    {
        $result = $create->handle($request->payload(), $request->file('photo'), $request->accountEmail());
        $teacher = $result['teacher'];

        return to_route('operator.teachers.edit', $teacher)
            ->with('success', "Guru {$teacher->name} berhasil ditambahkan.")
            ->with('credentials', $this->credentials($teacher, $result['temporary_password']));
    }

    public function edit(Teacher $teacher): Response
    {
        $this->authorize('update', $teacher);

        $teacher->load(['user:id,email,is_active', 'photo']);

        return Inertia::render('People::Operator/Teachers/Form', [
            'teacher' => (new TeacherResource($teacher))->resolve(request()),
            'options' => $this->options(),
        ]);
    }

    public function update(TeacherRequest $request, Teacher $teacher, UpdateTeacherAction $update): RedirectResponse
    {
        $update->handle($teacher, $request->payload(), $request->file('photo'), $request->boolean('remove_photo'));

        return to_route('operator.teachers.edit', $teacher)->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher, DeletePersonAction $delete): RedirectResponse
    {
        $this->authorize('delete', $teacher);

        $delete->handle($teacher);

        return to_route('operator.teachers.index')->with('success', "Data guru {$teacher->name} dihapus.");
    }

    public function linkAccount(LinkAccountRequest $request, Teacher $teacher, LinkPersonAccountAction $link): RedirectResponse
    {
        $password = $link->handle($teacher, $request->string('account_email')->value());

        return back()
            ->with('success', 'Akun login berhasil ditautkan.')
            ->with('credentials', $this->credentials($teacher->refresh(), $password));
    }

    /**
     * Kredensial sementara ditampilkan SEKALI ke operator (flash session).
     *
     * @return array{email: string, password: string}|null
     */
    private function credentials(Teacher $teacher, ?string $password): ?array
    {
        $teacher->loadMissing('user');

        return $password && $teacher->user ? ['email' => $teacher->user->email, 'password' => $password] : null;
    }

    /**
     * @return array{genders: list<array{value: string, label: string}>, employment: list<array{value: string, label: string}>}
     */
    private function options(): array
    {
        return [
            'genders' => array_map(fn (Gender $g) => ['value' => $g->value, 'label' => $g->label()], Gender::cases()),
            'employment' => array_map(fn (EmploymentStatus $s) => ['value' => $s->value, 'label' => $s->label()], EmploymentStatus::cases()),
        ];
    }
}
