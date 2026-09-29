<?php

namespace App\Modules\Cms;

use App\Modules\Cms\Http\Controllers\Public\PageController;
use App\Modules\Cms\Http\Middleware\CachePublicPage;
use App\Modules\Cms\Models\WebsiteBanner;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsiteGalleryPhoto;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Models\WebsiteSetting;
use App\Modules\Cms\Policies\WebsiteContentPolicy;
use App\Modules\Cms\Services\PublicPageCache;
use App\Modules\Cms\Services\PublicSite;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Models\Teacher;
use App\Modules\Ppdb\Models\PpdbDocumentRequirement;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\School\Models\School;
use App\Providers\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class CmsServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        WebsitePage::class => WebsiteContentPolicy::class,
        WebsitePost::class => WebsiteContentPolicy::class,
        WebsiteGallery::class => WebsiteContentPolicy::class,
        WebsiteBanner::class => WebsiteContentPolicy::class,
        WebsiteCategory::class => WebsiteContentPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    /**
     * Model yang tampil di website publik: perubahan apa pun membersihkan cache halaman.
     *
     * @var list<class-string<Model>>
     */
    private const PUBLIC_MODELS = [
        WebsitePage::class, WebsitePost::class, WebsiteGallery::class, WebsiteGalleryPhoto::class,
        WebsiteBanner::class, WebsiteCategory::class, WebsiteSetting::class,
        PpdbPeriod::class, PpdbTrack::class, PpdbDocumentRequirement::class, School::class,
    ];

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('public.cache', CachePublicPage::class);

        parent::boot();

        View::composer('public.*', function ($view) {
            $urls = $this->app->make(FileUrlGenerator::class);
            $view->with([
                ...$this->app->make(PublicSite::class)->shared(),
                'img' => fn (?File $file) => $file ? $urls->url($file) : null,
                'thumb' => fn (?File $file) => $file ? $urls->thumbnailUrl($file) : null,
            ]);
        });

        $this->flushPageCacheOnChanges();
        $this->registerPageRouteLast();
    }

    private function flushPageCacheOnChanges(): void
    {
        $flush = fn () => $this->app->make(PublicPageCache::class)->flush();

        foreach (self::PUBLIC_MODELS as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }

        Teacher::saved(function (Teacher $teacher) use ($flush) {
            if ($teacher->show_on_website || $teacher->wasChanged('show_on_website')) {
                $flush();
            }
        });
    }

    /**
     * `/{slug}` dipasang setelah semua provider boot agar tidak menimpa rute modul lain
     * (`/login`, `/ppdb/...`, portal).
     */
    private function registerPageRouteLast(): void
    {
        $this->app->booted(function () {
            if ($this->app->routesAreCached()) {
                return;
            }

            // Slug yang dipakai sistem tidak pernah cocok (tetap 404, bukan 405 untuk POST).
            $reserved = implode('|', array_map(fn (string $s) => preg_quote($s, '#'), WebsitePage::RESERVED_SLUGS));

            Route::middleware(['web', 'public.cache'])
                ->get('{slug}', [PageController::class, 'show'])
                ->where('slug', "(?!(?:{$reserved})$)[a-z0-9]+(?:-[a-z0-9]+)*")
                ->name('public.page');
        });
    }
}
