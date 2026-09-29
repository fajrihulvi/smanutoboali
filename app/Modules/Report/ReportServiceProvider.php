<?php

namespace App\Modules\Report;

use App\Modules\Report\Models\ReportExport;
use App\Modules\Report\Policies\ReportExportPolicy;
use App\Providers\ModuleServiceProvider;

class ReportServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        ReportExport::class => ReportExportPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
