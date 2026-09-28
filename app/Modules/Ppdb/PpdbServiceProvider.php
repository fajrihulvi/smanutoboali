<?php

namespace App\Modules\Ppdb;

use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Policies\PpdbApplicantPolicy;
use App\Modules\Ppdb\Policies\PpdbDocumentPolicy;
use App\Providers\ModuleServiceProvider;

class PpdbServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        PpdbApplicant::class => PpdbApplicantPolicy::class,
        PpdbDocument::class => PpdbDocumentPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];
}
