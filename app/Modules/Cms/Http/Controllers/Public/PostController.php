<?php

namespace App\Modules\Cms\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Services\PublicSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Berita (`/berita`) & agenda (`/agenda`).
 */
class PostController extends Controller
{
    public const PER_PAGE = 9;

    public function news(Request $request): View
    {
        $category = WebsiteCategory::query()->where('slug', $request->string('kategori')->value())->first();

        $posts = WebsitePost::query()->live()->where('type', PostType::News)
            ->with(['featuredImage', 'category'])
            ->when($category, fn (Builder $q) => $q->where('category_id', $category?->id))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('public.posts.index', [
            'posts' => $posts,
            'category' => $category,
            'categories' => WebsiteCategory::query()
                ->whereHas('posts', fn (Builder $q) => $q->live()->where('type', PostType::News))
                ->orderBy('name')->get(),
            'seo' => [
                'title' => $category ? "Berita {$category->name}" : 'Berita',
                'description' => 'Berita dan kabar kegiatan terbaru sekolah.',
            ],
        ]);
    }

    public function agenda(): View
    {
        $base = fn () => WebsitePost::query()->live()->where('type', PostType::Agenda)->with('featuredImage');

        return view('public.posts.agenda', [
            'upcoming' => $base()->where('event_starts_at', '>=', now()->startOfDay())->orderBy('event_starts_at')->limit(30)->get(),
            'past' => $base()->where('event_starts_at', '<', now()->startOfDay())->orderByDesc('event_starts_at')
                ->paginate(self::PER_PAGE)->withQueryString(),
            'seo' => ['title' => 'Agenda', 'description' => 'Jadwal kegiatan sekolah yang akan datang.'],
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $type = PostType::fromSegment((string) $request->route()?->defaults['type']);
        $post = WebsitePost::query()->live()->where('type', $type)->where('slug', $slug)
            ->with(['featuredImage', 'category', 'author'])->firstOrFail();

        return self::render($post);
    }

    public static function render(WebsitePost $post, bool $preview = false): View
    {
        $related = WebsitePost::query()->live()->where('type', $post->type)->whereKeyNot($post->id)
            ->with(['featuredImage', 'category'])
            ->when($post->category_id, fn (Builder $q) => $q->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$post->category_id]))
            ->orderByDesc('published_at')->limit(3)->get();

        return view('public.posts.show', [
            'post' => $post,
            'related' => $related,
            'preview' => $preview,
            'seo' => [
                'title' => $post->seo_title ?: $post->title,
                'description' => PublicSite::describe($post->seo_description, $post->excerpt, $post->content),
                'image' => $post->featuredImage,
                'type' => 'article',
                'published_at' => $post->published_at,
                'noindex' => $preview,
            ],
        ]);
    }
}
