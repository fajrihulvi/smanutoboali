<?php

namespace App\Modules\Cms\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsiteBanner;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Services\PublicSite;
use App\Modules\Ppdb\Models\PpdbPeriod;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(PublicSite $site): View
    {
        $settings = $site->shared()['settings'];

        return view('public.home', [
            'banners' => WebsiteBanner::query()->live()->whereNotNull('image_file_id')->with('image')->orderBy('position')->orderBy('id')->get(),
            'greeting' => WebsitePage::query()->live()->where('type', PageType::Greeting)->with('featuredImage')->first(),
            'news' => WebsitePost::query()->live()->where('type', PostType::News)
                ->with(['featuredImage', 'category'])
                ->orderByDesc('is_featured')->orderByDesc('published_at')->orderByDesc('id')
                ->limit(4)->get(),
            'agenda' => WebsitePost::query()->live()->where('type', PostType::Agenda)
                ->where('event_starts_at', '>=', now()->startOfDay())
                ->orderBy('event_starts_at')->limit(3)->get(),
            'galleries' => WebsiteGallery::query()->live()
                ->withCount('photos')
                ->with(['photos' => fn ($q) => $q->limit(1), 'photos.file'])
                ->orderByDesc('date')->orderByDesc('id')->limit(3)->get(),
            'ppdbPeriod' => PpdbPeriod::query()->where('is_active', true)->where('closes_at', '>=', now())->orderBy('opens_at')->first(),
            'seo' => [
                'title' => null,
                'description' => PublicSite::describe($settings['about'], $settings['tagline']),
            ],
        ]);
    }
}
