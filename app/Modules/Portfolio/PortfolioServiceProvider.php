<?php

namespace App\Modules\Portfolio;

use App\Modules\Portfolio\Events\PortfolioPublished;
use App\Modules\Portfolio\Listeners\NotifyGuardiansOfPortfolio;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Portfolio\Policies\PortfolioPolicy;
use App\Providers\ModuleServiceProvider;

class PortfolioServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Portfolio::class => PortfolioPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [
        PortfolioPublished::class => [NotifyGuardiansOfPortfolio::class],
    ];
}
