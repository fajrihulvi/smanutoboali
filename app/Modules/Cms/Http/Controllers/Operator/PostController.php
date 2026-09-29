<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cms\Actions\ChangeContentStatusAction;
use App\Modules\Cms\Actions\DeleteContentAction;
use App\Modules\Cms\Actions\SavePostAction;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Http\Controllers\Public\PostController as PublicPostController;
use App\Modules\Cms\Http\Requests\PostRequest;
use App\Modules\Cms\Http\Resources\PostResource;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Models\WebsitePost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Berita & agenda (route default `type` = segmen URL).
 */
class PostController extends Controller
{
    public const PER_PAGE = 15;

    public function index(Request $request): Response
    {
        $this->authorize('manage', WebsitePost::class);
        $type = $this->type($request);

        $status = $request->string('status')->value();
        $search = $request->string('q')->value();
        $category = $request->string('category')->value();

        $posts = WebsitePost::query()
            ->where('type', $type)
            ->with(['category', 'featuredImage', 'author'])
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', "%{$search}%"))
            ->when(ContentStatus::tryFrom($status), fn (Builder $q, ContentStatus $s) => $q->where('status', $s))
            ->when($category !== '', fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('public_id', $category)))
            ->when(
                $type === PostType::Agenda,
                fn (Builder $q) => $q->orderByDesc('event_starts_at'),
                fn (Builder $q) => $q->orderByRaw('CASE WHEN published_at IS NULL THEN 0 ELSE 1 END')->orderByDesc('published_at'),
            )
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Cms::Operator/Posts/Index', [
            ...$this->typeProps($type),
            'posts' => PostResource::collection($posts),
            'filters' => ['status' => $status, 'q' => $search, 'category' => $category],
            'statuses' => ContentStatus::options(),
            'categories' => $this->categories(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manage', WebsitePost::class);

        return Inertia::render('Cms::Operator/Posts/Form', [...$this->formProps($this->type($request)), 'post' => null]);
    }

    public function store(PostRequest $request, SavePostAction $save): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $type = $request->type();
        $post = $save->handle($type, $request->payload(), $user);

        return to_route("operator.cms.{$type->segment()}.edit", $post)->with('success', "{$type->label()} disimpan.");
    }

    public function edit(Request $request, WebsitePost $post): Response
    {
        $this->authorizePost($request, $post, 'update');
        $post->load(['category', 'featuredImage', 'author']);

        return Inertia::render('Cms::Operator/Posts/Form', [
            ...$this->formProps($post->type),
            'post' => (new PostResource($post))->resolve($request),
        ]);
    }

    public function update(PostRequest $request, WebsitePost $post, SavePostAction $save): RedirectResponse
    {
        $this->authorizePost($request, $post, 'update');

        /** @var User $user */
        $user = $request->user();
        $save->handle($post->type, $request->payload(), $user, $post);

        return back()->with('success', "{$post->type->label()} disimpan.");
    }

    public function status(Request $request, WebsitePost $post, ChangeContentStatusAction $change): RedirectResponse
    {
        $this->authorizePost($request, $post, 'update');
        $validated = $request->validate(['status' => ['required', Rule::enum(ContentStatus::class)]]);
        $change->handle($post, ContentStatus::from($validated['status']));

        return back()->with('success', "Status {$post->type->label()} menjadi {$post->status->label()}.");
    }

    public function preview(Request $request, WebsitePost $post): View
    {
        $this->authorizePost($request, $post, 'view');
        $post->load(['category', 'featuredImage', 'author']);

        return PublicPostController::render($post, preview: true);
    }

    public function destroy(Request $request, WebsitePost $post, DeleteContentAction $delete): RedirectResponse
    {
        $this->authorizePost($request, $post, 'delete');
        $delete->handle($post);
        $index = "operator.cms.{$post->type->segment()}.index";

        return $this->backToIndex($index, to_route($index))->with('success', "{$post->type->label()} dihapus.");
    }

    private function type(Request $request): PostType
    {
        /** @var string $segment */
        $segment = $request->route()?->defaults['type'] ?? 'berita';

        return PostType::fromSegment($segment);
    }

    /**
     * Berita tidak bisa dibuka lewat URL agenda (dan sebaliknya).
     */
    private function authorizePost(Request $request, WebsitePost $post, string $ability): void
    {
        abort_unless($post->type === $this->type($request), 404);
        $this->authorize($ability, $post);
    }

    /**
     * @return array<string, mixed>
     */
    private function typeProps(PostType $type): array
    {
        return [
            'type' => ['value' => $type->value, 'label' => $type->label(), 'segment' => $type->segment()],
            'base' => "/operator/cms/{$type->segment()}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(PostType $type): array
    {
        return [...$this->typeProps($type), 'statuses' => ContentStatus::options(), 'categories' => $this->categories()];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categories(): array
    {
        return WebsiteCategory::query()->orderBy('name')->get()
            ->map(fn (WebsiteCategory $c) => ['value' => $c->public_id, 'label' => $c->name])
            ->values()
            ->all();
    }
}
