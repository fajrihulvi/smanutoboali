<?php

namespace App\Modules\Activity;

use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Policies\ActivityPolicy;
use App\Providers\ModuleServiceProvider;

class ActivityServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Activity::class => ActivityPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
