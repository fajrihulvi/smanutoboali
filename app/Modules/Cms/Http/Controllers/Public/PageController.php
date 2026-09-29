<?php

namespace App\Modules\Cms\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Services\PublicSite;
use Illuminate\View\View;

/**
 * Halaman statis `/{slug}` (profil, visi misi, kontak, custom). Kontak menampilkan data kontak & peta.
 */
class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = WebsitePage::query()->live()->where('slug', $slug)->with('featuredImage')->firstOrFail();

        return self::render($page);
    }

    public static function render(WebsitePage $page, bool $preview = false): View
    {
        return view('public.page', [
            'page' => $page,
            'isContact' => $page->type === PageType::Contact,
            'preview' => $preview,
            'seo' => [
                'title' => $page->seo_title ?: $page->title,
                'description' => PublicSite::describe($page->seo_description, $page->excerpt, $page->content),
                'image' => $page->featuredImage,
                'noindex' => $preview,
            ],
        ]);
    }
}
