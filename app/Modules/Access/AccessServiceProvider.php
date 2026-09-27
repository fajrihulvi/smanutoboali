<?php

namespace App\Modules\Access;

use App\Models\User;
use App\Modules\Access\Policies\RolePolicy;
use App\Modules\Access\Policies\UserPolicy;
use App\Providers\ModuleServiceProvider;
use Spatie\Permission\Models\Role;

class AccessServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
