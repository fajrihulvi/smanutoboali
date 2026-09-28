<?php

namespace App\Modules\Assessment;

use App\Modules\Assessment\Events\GradePublished;
use App\Modules\Assessment\Listeners\NotifyGuardiansOfGrades;
use App\Providers\ModuleServiceProvider;

class AssessmentServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [
        GradePublished::class => [NotifyGuardiansOfGrades::class],
    ];
}
