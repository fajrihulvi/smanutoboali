<?php

namespace App\Modules\Auth;

use App\Providers\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
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
