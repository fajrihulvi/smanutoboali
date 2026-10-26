<?php

namespace App\Modules\GuestBook;

use App\Modules\GuestBook\Models\GuestVisit;
use App\Modules\GuestBook\Policies\GuestVisitPolicy;
use App\Providers\ModuleServiceProvider;

class GuestBookServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        GuestVisit::class => GuestVisitPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
