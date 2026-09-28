<?php

namespace App\Modules\Activity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Activity\Actions\DeleteActivityAction;
use App\Modules\Activity\Actions\SaveActivityAction;
use App\Modules\Activity\Enums\ActivityType;
use App\Modules\Activity\Http\Requests\ActivityRequest;
use App\Modules\Activity\Http\Resources\ActivityResource;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola kegiatan (guru & operator). `$base` = prefix portal lewat route defaults.
 */
class ActivityController extends Controller
{
    public const PER_PAGE = 15;

    public function __construct(
        private ActivityVisibility $visibility,
        private ActiveRoleService $roles,
        private ActivePeriod $period,
    ) {}

    public function index(Request $request, string $base): Response
    {
        $this->authorize('manageAny', Activity::class);
        $search = $request->string('search')->trim()->value();
        $type = $request->string('jenis')->value();
        $status = $request->string('status')->value();

        $activities = $this->visibility->forUser($this->user($request), $this->role($request))
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', "%{$search}%"))
            ->when(ActivityType::tryFrom($type), fn (Builder $q, ActivityType $t) => $q->where('type', $t))
            ->when(in_array($status, ['DRAF', 'TERBIT'], true), fn (Builder $q) => $q->where('status', $status))
            ->with(['participants.classroom:id,public_id,name', 'participants.student:id,public_id,name', 'photos', 'creator:id,name'])
            ->latest('date')->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Activity::Manage/Index', [
            'base' => $base,
            'activities' => ActivityResource::collection($activities),
            'types' => ActivityType::options(),
            'filters' => array_filter(['search' => $search, 'jenis' => $type, 'status' => $status]),
        ]);
    }

    public function create(Request $request, string $base, SettingsService $settings): Response
    {
        $this->authorize('manageAny', Activity::class);

        return Inertia::render('Activity::Manage/Form', $this->formProps($request, $base, null, $settings));
    }

    public function store(ActivityRequest $request, string $base, SaveActivityAction $save): RedirectResponse
    {
        $activity = $save->handle($request->payload(), $this->user($request), $request->uploads());

        return redirect("{$base}/{$activity->public_id}/ubah")->with('success', 'Kegiatan disimpan.');
    }

    public function edit(Request $request, Activity $activity, string $base, SettingsService $settings): Response
    {
        $this->authorize('update', $activity);
        $activity->load(['participants.classroom:id,public_id,name', 'participants.student:id,public_id,name', 'photos', 'creator:id,name']);

        return Inertia::render('Activity::Manage/Form', $this->formProps($request, $base, $activity, $settings));
    }

    public function update(ActivityRequest $request, Activity $activity, SaveActivityAction $save): RedirectResponse
    {
        $save->handle($request->payload(), $this->user($request), $request->uploads(), $request->removedPhotos(), $activity);

        return back()->with('success', 'Kegiatan diperbarui.');
    }

    public function destroy(Activity $activity, string $base, DeleteActivityAction $delete): RedirectResponse
    {
        $this->authorize('delete', $activity);
        $delete->handle($activity);

        return redirect($base)->with('success', "Kegiatan \"{$activity->title}\" dihapus.");
    }

    public function students(Request $request): JsonResponse
    {
        $this->authorize('manageAny', Activity::class);
        $term = $request->string('q')->trim()->value();

        $students = Student::query()
            ->visibleTo($this->user($request))
            ->where('students.status', StudentStatus::Active)
            ->search($term !== '' ? $term : null)
            ->with(['enrollments' => fn ($e) => $e->where('academic_year_id', $this->period->academicYearId() ?? 0)
                ->where('status', EnrollmentStatus::Active)->with('classroom:id,name')])
            ->orderBy('name')->limit(30)
            ->get(['students.id', 'public_id', 'nis', 'name']);

        return response()->json(['data' => $students->map(fn (Student $s) => [
            'public_id' => $s->public_id, 'name' => $s->name, 'nis' => $s->nis,
            'classroom' => $s->enrollments->first()?->classroom->name,
        ])->values()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, string $base, ?Activity $activity, SettingsService $settings): array
    {
        $role = $this->role($request);

        return [
            'base' => $base,
            'activity' => $activity ? (new ActivityResource($activity))->resolve($request) : null,
            'types' => array_values(array_filter(ActivityType::options(), fn (array $t) => $role === Role::Operator || $t['value'] !== ActivityType::School->value)),
            'classrooms' => $this->visibility->classroomsFor($this->user($request), $role, $this->period->academicYearId() ?? 0)
                ->orderBy('grade')->orderBy('name')->get(['id', 'public_id', 'name'])
                ->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
            'maxUploadKb' => $settings->uploadMaxKb(),
            'maxPhotos' => Activity::MAX_PHOTOS,
        ];
    }

    private function role(Request $request): Role
    {
        return $this->roles->current($request->session()) ?? Role::Teacher;
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
