<?php

namespace App\Modules\Academic\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Actions\DeleteLearningMaterialAction;
use App\Modules\Academic\Actions\SaveLearningMaterialAction;
use App\Modules\Academic\Enums\MaterialType;
use App\Modules\Academic\Http\Requests\LearningMaterialRequest;
use App\Modules\Academic\Http\Resources\LearningMaterialResource;
use App\Modules\Academic\Models\LearningMaterial;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Materi pembelajaran milik guru (per mapel di rombel yang diampu, tahun ajaran aktif).
 */
class MaterialController extends Controller
{
    public const PER_PAGE = 15;

    public function index(Request $request, ActivePeriod $period, SettingsService $settings): Response
    {
        /** @var User $user */
        $user = $request->user();
        $teacher = Teacher::activeForUser($user);
        $yearId = $period->academicYearId();

        $assignments = $teacher && $yearId
            ? TeachingAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->where('academic_year_id', $yearId)
                ->with(['subject:id,name', 'classroom:id,name,grade'])
                ->get()
                ->sortBy(fn (TeachingAssignment $a) => [$a->classroom->grade, $a->classroom->name, $a->subject->name])
            : collect();

        $filter = $request->string('penugasan')->value();
        $search = $request->string('search')->trim()->value();

        $materials = LearningMaterial::query()
            ->whereIn('teaching_assignment_id', $assignments->pluck('id'))
            ->when($filter !== '', fn (Builder $q) => $q->whereHas('teachingAssignment', fn (Builder $a) => $a->where('public_id', $filter)))
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', "%{$search}%"))
            ->with(['file', 'teachingAssignment.subject:id,name', 'teachingAssignment.classroom:id,name'])
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Academic::Teacher/Materials/Index', [
            'hasTeacherProfile' => $teacher !== null,
            'materials' => LearningMaterialResource::collection($materials),
            'assignments' => $assignments->map(fn (TeachingAssignment $a) => [
                'value' => $a->public_id,
                'label' => "{$a->subject->name} — {$a->classroom->name}",
            ])->values(),
            'types' => array_map(fn (MaterialType $t) => ['value' => $t->value, 'label' => $t->label()], MaterialType::cases()),
            'filters' => array_filter(['penugasan' => $filter, 'search' => $search]),
            'maxUploadKb' => $settings->uploadMaxKb(),
        ]);
    }

    public function store(LearningMaterialRequest $request, SaveLearningMaterialAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $save->handle($request->payload(), $user, $request->upload());

        return back()->with('success', 'Materi ditambahkan.');
    }

    public function update(LearningMaterialRequest $request, LearningMaterial $material, SaveLearningMaterialAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $save->handle($request->payload(), $user, $request->upload(), $material);

        return back()->with('success', 'Materi diperbarui.');
    }

    public function destroy(LearningMaterial $material, DeleteLearningMaterialAction $delete): RedirectResponse
    {
        $this->authorize('delete', $material);

        $delete->handle($material);

        return back()->with('success', 'Materi dihapus.');
    }
}
