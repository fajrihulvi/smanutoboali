<?php

namespace App\Modules\ReportCard;

use App\Modules\ReportCard\Events\ReportCardPublished;
use App\Modules\ReportCard\Listeners\NotifyGuardiansOfReportCard;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardExport;
use App\Modules\ReportCard\Policies\ReportCardExportPolicy;
use App\Modules\ReportCard\Policies\ReportCardPolicy;
use App\Providers\ModuleServiceProvider;

class ReportCardServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        ReportCard::class => ReportCardPolicy::class,
        ReportCardExport::class => ReportCardExportPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [
        ReportCardPublished::class => [NotifyGuardiansOfReportCard::class],
    ];
}
