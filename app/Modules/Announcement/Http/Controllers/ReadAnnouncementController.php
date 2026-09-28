<?php

namespace App\Modules\Announcement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Announcement\Http\Resources\AnnouncementResource;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Services\AnnouncementAudienceResolver;
use App\Modules\Auth\Services\ActiveRoleService;
use App\Modules\Core\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengumuman untuk pembaca (guru & orang tua), dan detail untuk semua yang berhak.
 */
class ReadAnnouncementController extends Controller
{
    public const PER_PAGE = 10;

    public function index(Request $request, AnnouncementAudienceResolver $audience, ActiveRoleService $roles): Response
    {
        /** @var User $user */
        $user = $request->user();
        $role = $roles->resolve($request->session(), $user) ?? Role::Guardian;

        $announcements = $audience->scopeFor(Announcement::query(), $user, $role)
            ->with(['attachments', 'classrooms', 'creator'])
            ->feedOrder()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Announcement::Read/Index', [
            'base' => '/'.$role->urlPrefix().'/pengumuman',
            'announcements' => AnnouncementResource::collection($announcements->through(fn (Announcement $a) => (new AnnouncementResource($a))->summary())),
        ]);
    }

    public function show(Request $request, Announcement $announcement): Response
    {
        $this->authorize('view', $announcement);
        $announcement->load(['attachments', 'classrooms', 'creator']);

        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/orang-tua/pengumuman';
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Announcement::Read/Show', [
            'base' => $base,
            'announcement' => (new AnnouncementResource($announcement))->resolve($request),
            'canManage' => $user->can('manage', Announcement::class) && ! str_starts_with($base, '/guru') && ! str_starts_with($base, '/orang-tua'),
        ]);
    }
}
