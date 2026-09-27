<?php

use App\Modules\Access\AccessServiceProvider;
use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Modules\Dashboard\DashboardServiceProvider;
use App\Modules\School\SchoolServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AccessServiceProvider::class,
    AuthServiceProvider::class,
    CoreServiceProvider::class,
    DashboardServiceProvider::class,
    SchoolServiceProvider::class,
    AppServiceProvider::class,
];
