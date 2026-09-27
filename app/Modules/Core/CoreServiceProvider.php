<?php

namespace App\Modules\Core;

use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Services\DefaultStudentOwnershipGate;
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

    public function register(): void
    {
        parent::register();

        // Diganti implementasi berbasis Student::visibleTo di S10.
        $this->app->singletonIf(StudentOwnershipGate::class, DefaultStudentOwnershipGate::class);
    }
}
