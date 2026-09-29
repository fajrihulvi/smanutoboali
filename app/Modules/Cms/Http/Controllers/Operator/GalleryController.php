<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cms\Actions\ChangeContentStatusAction;
use App\Modules\Cms\Actions\DeleteContentAction;
use App\Modules\Cms\Actions\SaveGalleryAction;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Http\Controllers\Public\GalleryController as PublicGalleryController;
use App\Modules\Cms\Http\Requests\GalleryRequest;
use App\Modules\Cms\Http\Resources\GalleryResource;
use App\Modules\Cms\Models\WebsiteGallery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public const PER_PAGE = 12;

    public function index(Request $request): Response
    {
        $this->authorize('manage', WebsiteGallery::class);

        $status = $request->string('status')->value();
        $search = $request->string('q')->value();

        $galleries = WebsiteGallery::query()
            ->withCount('photos')
            ->with(['photos' => fn ($q) => $q->limit(1), 'photos.file'])
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', "%{$search}%"))
            ->when(ContentStatus::tryFrom($status), fn (Builder $q, ContentStatus $s) => $q->where('status', $s))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Cms::Operator/Galleries/Index', [
            'galleries' => GalleryResource::collection($galleries),
            'filters' => ['status' => $status, 'q' => $search],
            'statuses' => ContentStatus::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manage', WebsiteGallery::class);

        return Inertia::render('Cms::Operator/Galleries/Form', [
            'gallery' => null,
            'statuses' => ContentStatus::options(),
            'maxPhotos' => WebsiteGallery::MAX_PHOTOS,
        ]);
    }

    public function store(GalleryRequest $request, SaveGalleryAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gallery = $save->handle($request->payload(), $user);

        return to_route('operator.cms.galleries.edit', $gallery)->with('success', 'Album disimpan.');
    }

    public function edit(Request $request, WebsiteGallery $gallery): Response
    {
        $this->authorize('update', $gallery);
        $gallery->load('photos.file');

        return Inertia::render('Cms::Operator/Galleries/Form', [
            'gallery' => (new GalleryResource($gallery))->resolve($request),
            'statuses' => ContentStatus::options(),
            'maxPhotos' => WebsiteGallery::MAX_PHOTOS,
        ]);
    }

    public function update(GalleryRequest $request, WebsiteGallery $gallery, SaveGalleryAction $save): RedirectResponse
    {
        $this->authorize('update', $gallery);

        /** @var User $user */
        $user = $request->user();
        $save->handle($request->payload(), $user, $gallery);

        return back()->with('success', 'Album disimpan.');
    }

    public function status(Request $request, WebsiteGallery $gallery, ChangeContentStatusAction $change): RedirectResponse
    {
        $this->authorize('update', $gallery);
        $validated = $request->validate(['status' => ['required', Rule::enum(ContentStatus::class)]]);
        $change->handle($gallery, ContentStatus::from($validated['status']));

        return back()->with('success', "Status album menjadi {$gallery->status->label()}.");
    }

    public function preview(WebsiteGallery $gallery): View
    {
        $this->authorize('view', $gallery);
        $gallery->load('photos.file');

        return PublicGalleryController::render($gallery, preview: true);
    }

    public function destroy(WebsiteGallery $gallery, DeleteContentAction $delete): RedirectResponse
    {
        $this->authorize('delete', $gallery);
        $delete->handle($gallery);

        return $this->backToIndex('operator.cms.galleries.index', to_route('operator.cms.galleries.index'))
            ->with('success', 'Album dihapus.');
    }
}
