<?php

namespace App\Modules\ChildCondition;

use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Policies\ChildConditionPolicy;
use App\Modules\ChildCondition\Services\ConditionAttendanceSuggestions;
use App\Modules\Core\Contracts\AttendanceSuggestions;
use App\Providers\ModuleServiceProvider;

class ChildConditionServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        ChildCondition::class => ChildConditionPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function register(): void
    {
        parent::register();

        $this->app->tag([ConditionAttendanceSuggestions::class], AttendanceSuggestions::TAG);
    }
}
