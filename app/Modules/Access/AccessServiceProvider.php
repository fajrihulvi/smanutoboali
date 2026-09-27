<?php

namespace App\Modules\Access;

use App\Providers\ModuleServiceProvider;

class AccessServiceProvider extends ModuleServiceProvider
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
