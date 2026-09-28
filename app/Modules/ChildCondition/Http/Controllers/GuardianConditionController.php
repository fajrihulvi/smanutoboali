<?php

namespace App\Modules\ChildCondition\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ChildCondition\Actions\SubmitConditionAction;
use App\Modules\ChildCondition\Actions\WithdrawConditionAction;
use App\Modules\ChildCondition\Enums\ConditionType;
use App\Modules\ChildCondition\Http\Requests\SubmitConditionRequest;
use App\Modules\ChildCondition\Http\Resources\ConditionResource;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Services\ConditionQuery;
use App\Modules\ChildCondition\Services\ConditionTeachers;
use App\Modules\People\Models\Student;
use App\Modules\People\Services\GuardianChildren;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal orang tua: riwayat, lapor, dan tarik laporan kondisi anak.
 */
class GuardianConditionController extends Controller
{
    public function __construct(private GuardianChildren $children) {}

    public function index(Request $request, ConditionQuery $query): Response
    {
        /** @var User $user */
        $user = $request->user();
        $children = $this->children->of($user);
        $childKey = $request->string(GuardianChildren::QUERY_KEY)->value();
        $child = $childKey !== '' ? $this->children->selected($children, $childKey) : null;

        return Inertia::render('ChildCondition::Guardian/Index', [
            ...$this->children->switcherProps($children, $child),
            'conditions' => ConditionResource::collection(
                $query->forGuardian($user, $child)->through(fn (ChildCondition $c) => (new ConditionResource($c))->summary()),
            ),
        ]);
    }

    public function create(Request $request, ConditionTeachers $teachers): Response
    {
        $this->authorize('create', ChildCondition::class);

        /** @var User $user */
        $user = $request->user();
        $children = $this->children->of($user);

        return Inertia::render('ChildCondition::Guardian/Create', [
            'children' => $children->map(fn (Student $child) => [
                'public_id' => $child->public_id,
                'name' => $child->name,
                'teachers' => array_map(fn (array $row) => [
                    'public_id' => $row['teacher']->public_id,
                    'name' => $row['teacher']->name,
                    'label' => $row['label'],
                    'homeroom' => $row['homeroom'],
                ], $teachers->forStudent($child)),
            ])->values(),
            'selected' => $request->string(GuardianChildren::QUERY_KEY)->value() ?: null,
            'types' => ConditionType::options(),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(SubmitConditionRequest $request, SubmitConditionAction $submit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Student $child */
        $child = $request->child();

        $condition = $submit->handle($user, $child, $request->payload(), $request->string('target_teacher')->value() ?: null, $request->photos());

        return redirect("/orang-tua/kondisi-anak/{$condition->public_id}")->with('success', 'Laporan terkirim ke guru.');
    }

    public function destroy(ChildCondition $condition, WithdrawConditionAction $withdraw): RedirectResponse
    {
        $this->authorize('delete', $condition);

        $withdraw->handle($condition);

        return redirect('/orang-tua/kondisi-anak')->with('success', 'Laporan ditarik.');
    }
}
