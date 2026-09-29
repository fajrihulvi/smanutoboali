<?php

namespace App\Modules\Cms;

use App\Modules\Cms\Models\WebsiteBanner;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Cms\Policies\WebsiteContentPolicy;
use App\Providers\ModuleServiceProvider;

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
}
