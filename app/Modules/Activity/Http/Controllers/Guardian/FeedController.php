<?php

namespace App\Modules\Activity\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Activity\Http\Resources\ActivityResource;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\People\Services\GuardianChildren;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Feed kegiatan anak (terbaru dulu) untuk portal orang tua.
 */
class FeedController extends Controller
{
    public const PER_PAGE = 10;

    public function __invoke(Request $request, GuardianChildren $guardianChildren, ActivityVisibility $visibility): Response
    {
        /** @var User $user */
        $user = $request->user();
        $children = $guardianChildren->of($user);
        $child = $guardianChildren->selected($children, $request->string(GuardianChildren::QUERY_KEY)->value());

        $activities = $child
            ? $visibility->publishedForStudents([$child->id])
                ->with(['participants.classroom:id,public_id,name', 'participants.student:id,public_id,name', 'photos', 'creator:id,name'])
                ->latest('date')->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString()
            : null;

        return Inertia::render('Activity::Guardian/Feed', [
            ...$guardianChildren->switcherProps($children, $child),
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'activities' => $activities
                ? ActivityResource::collection($activities->through(fn (Activity $a) => (new ActivityResource($a))->onlyStudents($children->modelKeys())))
                : null,
        ]);
    }
}
