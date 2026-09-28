<?php

namespace App\Modules\ChildCondition\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ChildCondition\Actions\RespondConditionAction;
use App\Modules\ChildCondition\Actions\TransitionConditionAction;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Http\Resources\ConditionResource;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Services\ConditionAccess;
use App\Modules\ChildCondition\Services\ConditionQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inbox guru (tanggapi/tinjau/tutup), monitoring kepala sekolah (hanya-baca),
 * dan halaman detail untuk semua yang berhak (termasuk orang tua pelapor).
 */
class ConditionController extends Controller
{
    public function __construct(private ConditionAccess $access) {}

    public function teacherIndex(Request $request, ConditionQuery $query): Response
    {
        /** @var User $user */
        $user = $request->user();
        $teacher = $this->access->teacherFor($user);
        $closed = $request->string('tab')->value() === 'selesai';
        $search = $request->string('q')->value();

        return Inertia::render('ChildCondition::Staff/Index', [
            'base' => '/guru/kondisi-anak',
            'conditions' => $teacher
                ? ConditionResource::collection($query->forTeacher($teacher, $closed, $search)->through(fn (ChildCondition $c) => (new ConditionResource($c))->summary()))
                : null,
            'filters' => ['tab' => $closed ? 'selesai' : 'aktif', 'q' => $search],
            'newCount' => $teacher ? $query->newCountForTeacher($teacher) : 0,
            'readOnly' => false,
        ]);
    }

    public function principalIndex(Request $request, ConditionQuery $query): Response
    {
        $closed = $request->string('tab')->value() === 'selesai';
        $search = $request->string('q')->value();

        return Inertia::render('ChildCondition::Staff/Index', [
            'base' => '/kepala-sekolah/kondisi-anak',
            'conditions' => ConditionResource::collection($query->forPrincipal($closed, $search)->through(fn (ChildCondition $c) => (new ConditionResource($c))->summary())),
            'filters' => ['tab' => $closed ? 'selesai' : 'aktif', 'q' => $search],
            'newCount' => 0,
            'readOnly' => true,
        ]);
    }

    /**
     * Detail. Guru penangan yang membuka laporan baru → otomatis RECEIVED.
     */
    public function show(Request $request, ChildCondition $condition, TransitionConditionAction $transition): Response
    {
        $this->authorize('view', $condition);

        /** @var User $user */
        $user = $request->user();
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/orang-tua/kondisi-anak';

        if ($condition->status === ConditionStatus::Submitted && $base === '/guru/kondisi-anak' && $this->access->isHandler($user, $condition)) {
            $transition->handle($condition, ConditionStatus::Received, $user);
        }

        $condition->load(['student', 'reporter', 'targetTeacher', 'photos', 'responses.user']);

        return Inertia::render('ChildCondition::Shared/Show', [
            'base' => $base,
            'condition' => (new ConditionResource($condition))->resolve($request),
        ]);
    }

    public function respond(Request $request, ChildCondition $condition, RespondConditionAction $respond): RedirectResponse
    {
        $this->authorize('respond', $condition);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']], [], ['body' => 'tanggapan']);

        /** @var User $user */
        $user = $request->user();
        $respond->handle($condition, $user, $data['body']);

        return back()->with('success', 'Tanggapan terkirim ke orang tua.');
    }

    public function review(Request $request, ChildCondition $condition, TransitionConditionAction $transition): RedirectResponse
    {
        $this->authorize('respond', $condition);

        /** @var User $user */
        $user = $request->user();
        $transition->handle($condition, ConditionStatus::Reviewed, $user);

        return back()->with('success', 'Laporan ditandai sudah ditinjau.');
    }

    public function close(Request $request, ChildCondition $condition, TransitionConditionAction $transition): RedirectResponse
    {
        $this->authorize('respond', $condition);

        /** @var User $user */
        $user = $request->user();

        if ($condition->status === ConditionStatus::Submitted) {
            $transition->handle($condition, ConditionStatus::Received, $user);
        }

        $transition->handle($condition, ConditionStatus::Closed, $user);

        return back()->with('success', 'Laporan ditandai selesai.');
    }
}
