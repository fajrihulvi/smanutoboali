<?php

namespace App\Modules\Announcement;

use App\Modules\Announcement\Console\PublishDueAnnouncements;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Policies\AnnouncementPolicy;
use App\Providers\ModuleServiceProvider;

class AnnouncementServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Announcement::class => AnnouncementPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([PublishDueAnnouncements::class]);
        }
    }
}
