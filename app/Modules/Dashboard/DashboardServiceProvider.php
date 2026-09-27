<?php

namespace App\Modules\Dashboard;

use App\Providers\ModuleServiceProvider;

class DashboardServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
