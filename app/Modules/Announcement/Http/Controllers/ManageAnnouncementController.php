<?php

namespace App\Modules\Announcement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Announcement\Actions\DeleteAnnouncementAction;
use App\Modules\Announcement\Actions\SaveAnnouncementAction;
use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Enums\AnnouncementStatus;
use App\Modules\Announcement\Http\Requests\AnnouncementRequest;
use App\Modules\Announcement\Http\Resources\AnnouncementResource;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Services\AnnouncementAudienceResolver;
use App\Modules\Core\Enums\Role;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola pengumuman (operator & kepala sekolah). `base` = prefix URL portal.
 */
class ManageAnnouncementController extends Controller
{
    public const PER_PAGE = 15;

    public function index(Request $request): Response
    {
        $this->authorize('manage', Announcement::class);

        $status = $request->string('status')->value();
        $search = $request->string('q')->value();

        $announcements = Announcement::query()
            ->with(['attachments', 'classrooms', 'creator'])
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', "%{$search}%"))
            ->when($status === AnnouncementStatus::Draft->value, fn (Builder $q) => $q->whereNull('publish_at'))
            ->when($status === AnnouncementStatus::Scheduled->value, fn (Builder $q) => $q->where('publish_at', '>', now()))
            ->when($status === AnnouncementStatus::Published->value, fn (Builder $q) => $q->published())
            ->orderByRaw('CASE WHEN publish_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('pinned')
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Announcement::Manage/Index', [
            'base' => $this->base($request),
            'announcements' => AnnouncementResource::collection($announcements->through(fn (Announcement $a) => (new AnnouncementResource($a))->summary())),
            'filters' => ['status' => $status, 'q' => $search],
            'statuses' => array_map(fn (AnnouncementStatus $s) => ['value' => $s->value, 'label' => $s->label()], AnnouncementStatus::cases()),
        ]);
    }

    public function create(Request $request, ActivePeriod $period): Response
    {
        $this->authorize('manage', Announcement::class);

        return Inertia::render('Announcement::Manage/Form', [...$this->formProps($request, $period), 'announcement' => null]);
    }

    public function store(AnnouncementRequest $request, SaveAnnouncementAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $announcement = $save->handle($request->payload(), $user, $request->uploads());

        return redirect($this->base($request))->with('success', $this->savedMessage($announcement));
    }

    public function edit(Request $request, Announcement $announcement, ActivePeriod $period): Response
    {
        $this->authorize('update', $announcement);
        $announcement->load(['attachments', 'classrooms', 'creator']);

        return Inertia::render('Announcement::Manage/Form', [
            ...$this->formProps($request, $period),
            'announcement' => (new AnnouncementResource($announcement))->resolve($request),
        ]);
    }

    public function update(AnnouncementRequest $request, Announcement $announcement, SaveAnnouncementAction $save): RedirectResponse
    {
        $this->authorize('update', $announcement);

        /** @var User $user */
        $user = $request->user();
        $save->handle($request->payload($announcement), $user, $request->uploads(), $request->removeFiles(), $announcement);

        return redirect($this->base($request))->with('success', $this->savedMessage($announcement));
    }

    public function destroy(Request $request, Announcement $announcement, DeleteAnnouncementAction $delete): RedirectResponse
    {
        $this->authorize('delete', $announcement);
        $delete->handle($announcement);

        return $this->backToIndex($this->routePrefix($request).'.announcements.index', redirect($this->base($request)))
            ->with('success', 'Pengumuman dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ActivePeriod $period): array
    {
        return [
            'base' => $this->base($request),
            'audiences' => AnnouncementAudience::options(),
            'roles' => array_map(fn (Role $r) => ['value' => $r->value, 'label' => $r->label()], AnnouncementAudienceResolver::READER_ROLES),
            'classrooms' => Classroom::query()
                ->where('academic_year_id', $period->academicYearId() ?? 0)
                ->orderBy('grade')->orderBy('name')
                ->get(['public_id', 'name'])
                ->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])
                ->values(),
            'maxAttachments' => Announcement::MAX_ATTACHMENTS,
        ];
    }

    private function savedMessage(Announcement $announcement): string
    {
        return match ($announcement->status()) {
            AnnouncementStatus::Draft => 'Pengumuman disimpan sebagai draf.',
            AnnouncementStatus::Scheduled => 'Pengumuman dijadwalkan tayang '.$announcement->publish_at?->translatedFormat('d M Y, H.i').'.',
            AnnouncementStatus::Published => 'Pengumuman terbit.',
        };
    }

    private function base(Request $request): string
    {
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/pengumuman';

        return $base;
    }

    private function routePrefix(Request $request): string
    {
        return str_starts_with($this->base($request), '/kepala-sekolah') ? 'principal' : 'operator';
    }
}
