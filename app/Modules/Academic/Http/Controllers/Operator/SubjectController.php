<?php

namespace App\Modules\Academic\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Actions\DeleteSubjectAction;
use App\Modules\Academic\Actions\SaveSubjectAction;
use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Http\Requests\SubjectRequest;
use App\Modules\Academic\Http\Resources\SubjectResource;
use App\Modules\Academic\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Subject::class);

        $search = $request->string('search')->trim()->value();

        $subjects = Subject::query()
            ->withCount('teachingAssignments')
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Academic::Operator/Subjects/Index', [
            'subjects' => SubjectResource::collection($subjects),
            'filters' => array_filter(['search' => $search]),
            'groups' => array_map(fn (SubjectGroup $g) => ['value' => $g->value, 'label' => $g->label()], SubjectGroup::cases()),
        ]);
    }

    public function store(SubjectRequest $request, SaveSubjectAction $save): RedirectResponse
    {
        $subject = $save->handle($request->payload());

        return back()->with('success', "Mata pelajaran {$subject->name} ditambahkan.");
    }

    public function update(SubjectRequest $request, Subject $subject, SaveSubjectAction $save): RedirectResponse
    {
        $save->handle($request->payload(), $subject);

        return back()->with('success', 'Mata pelajaran diperbarui.');
    }

    public function destroy(Subject $subject, DeleteSubjectAction $delete): RedirectResponse
    {
        $this->authorize('delete', $subject);

        $delete->handle($subject);

        return back()->with('success', "Mata pelajaran {$subject->name} dihapus.");
    }
}
