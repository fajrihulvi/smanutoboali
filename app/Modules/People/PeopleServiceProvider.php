<?php

namespace App\Modules\People;

use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Policies\GuardianPolicy;
use App\Modules\People\Policies\TeacherPolicy;
use App\Providers\ModuleServiceProvider;

class PeopleServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Teacher::class => TeacherPolicy::class,
        Guardian::class => GuardianPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
