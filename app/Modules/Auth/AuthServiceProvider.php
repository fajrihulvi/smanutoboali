<?php

namespace App\Modules\Auth;

use App\Modules\Auth\Listeners\RecordAuthActivity;
use App\Providers\ModuleServiceProvider;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

class AuthServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [
        Login::class => [RecordAuthActivity::class],
        Logout::class => [RecordAuthActivity::class],
        PasswordReset::class => [RecordAuthActivity::class],
        Lockout::class => [RecordAuthActivity::class],
    ];
}
