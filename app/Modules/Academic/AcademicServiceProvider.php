<?php

namespace App\Modules\Academic;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Policies\ClassroomPolicy;
use App\Modules\Academic\Policies\SubjectPolicy;
use App\Providers\ModuleServiceProvider;

class AcademicServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Classroom::class => ClassroomPolicy::class,
        Subject::class => SubjectPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
