<?php

namespace App\Modules\School;

use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\School;
use App\Modules\School\Policies\AcademicYearPolicy;
use App\Modules\School\Policies\SchoolPolicy;
use App\Modules\School\Services\SchoolProfile;
use App\Providers\ModuleServiceProvider;

class SchoolServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        AcademicYear::class => AcademicYearPolicy::class,
        School::class => SchoolPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(SchoolProfile::class);
    }
}
