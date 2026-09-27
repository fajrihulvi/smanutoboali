<?php

namespace App\Modules\Core;

use App\Providers\ModuleServiceProvider;

class CoreServiceProvider extends ModuleServiceProvider
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
