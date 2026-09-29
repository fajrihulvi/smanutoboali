<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cms\Actions\ChangeContentStatusAction;
use App\Modules\Cms\Actions\DeleteContentAction;
use App\Modules\Cms\Actions\SavePageAction;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Http\Controllers\Public\PageController as PublicPageController;
use App\Modules\Cms\Http\Requests\PageRequest;
use App\Modules\Cms\Http\Resources\PageResource;
use App\Modules\Cms\Models\WebsitePage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $this->authorize('manage', WebsitePage::class);

        $status = $request->string('status')->value();
        $search = $request->string('q')->value();

        $pages = WebsitePage::query()
            ->with('featuredImage')
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', "%{$search}%"))
            ->when(ContentStatus::tryFrom($status), fn (Builder $q, ContentStatus $s) => $q->where('status', $s))
            ->orderByRaw("CASE WHEN type = 'CUSTOM' THEN 1 ELSE 0 END")
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Cms::Operator/Pages/Index', [
            'pages' => PageResource::collection($pages),
            'filters' => ['status' => $status, 'q' => $search],
            'statuses' => ContentStatus::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manage', WebsitePage::class);

        return Inertia::render('Cms::Operator/Pages/Form', ['page' => null, 'statuses' => ContentStatus::options()]);
    }

    public function store(PageRequest $request, SavePageAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $page = $save->handle($request->payload(), $user);

        return to_route('operator.cms.pages.edit', $page)->with('success', 'Halaman disimpan.');
    }

    public function edit(Request $request, WebsitePage $page): Response
    {
        $this->authorize('update', $page);
        $page->load('featuredImage');

        return Inertia::render('Cms::Operator/Pages/Form', [
            'page' => (new PageResource($page))->resolve($request),
            'statuses' => ContentStatus::options(),
        ]);
    }

    public function update(PageRequest $request, WebsitePage $page, SavePageAction $save): RedirectResponse
    {
        $this->authorize('update', $page);

        /** @var User $user */
        $user = $request->user();
        $save->handle($request->payload(), $user, $page);

        return back()->with('success', 'Halaman disimpan.');
    }

    public function status(Request $request, WebsitePage $page, ChangeContentStatusAction $change): RedirectResponse
    {
        $this->authorize('update', $page);
        $validated = $request->validate(['status' => ['required', Rule::enum(ContentStatus::class)]]);
        $change->handle($page, ContentStatus::from($validated['status']));

        return back()->with('success', "Status halaman menjadi {$page->status->label()}.");
    }

    public function preview(WebsitePage $page): View
    {
        $this->authorize('view', $page);
        $page->load('featuredImage');

        return PublicPageController::render($page, preview: true);
    }

    public function destroy(WebsitePage $page, DeleteContentAction $delete): RedirectResponse
    {
        $this->authorize('delete', $page);
        $delete->handle($page);

        return $this->backToIndex('operator.cms.pages.index', to_route('operator.cms.pages.index'))
            ->with('success', 'Halaman dihapus.');
    }
}
