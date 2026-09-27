<?php

namespace App\Modules\People;

use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Contracts\UserDeletionGuard;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Policies\GuardianPolicy;
use App\Modules\People\Policies\StudentPolicy;
use App\Modules\People\Policies\TeacherPolicy;
use App\Modules\People\Services\LinkedProfileDeletionGuard;
use App\Modules\People\Services\StudentVisibilityGate;
use App\Providers\ModuleServiceProvider;

class PeopleServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Teacher::class => TeacherPolicy::class,
        Guardian::class => GuardianPolicy::class,
        Student::class => StudentPolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function register(): void
    {
        parent::register();

        // Ganti implementasi deny-by-default dari Core (S05) dengan Student::visibleTo().
        $this->app->singleton(StudentOwnershipGate::class, StudentVisibilityGate::class);

        // Akun yang tertaut ke guru/orang tua tidak boleh dihapus dari User Management.
        $this->app->tag([LinkedProfileDeletionGuard::class], UserDeletionGuard::TAG);
    }
}
