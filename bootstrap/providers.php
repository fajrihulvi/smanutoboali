<?php

use App\Modules\Academic\AcademicServiceProvider;
use App\Modules\Access\AccessServiceProvider;
use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Modules\Dashboard\DashboardServiceProvider;
use App\Modules\People\PeopleServiceProvider;
use App\Modules\School\SchoolServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AcademicServiceProvider::class,
    AccessServiceProvider::class,
    AuthServiceProvider::class,
    CoreServiceProvider::class,
    DashboardServiceProvider::class,
    PeopleServiceProvider::class,
    SchoolServiceProvider::class,
    AppServiceProvider::class,
];
