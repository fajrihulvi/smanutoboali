<?php

namespace App\Modules\Core;

use App\Modules\Core\Console\GenerateThumbnails;
use App\Modules\Core\Contracts\StudentOwnershipGate;
use App\Modules\Core\Models\File;
use App\Modules\Core\Policies\FilePolicy;
use App\Modules\Core\Services\DefaultStudentOwnershipGate;
use App\Modules\Core\Services\HealthCheckService;
use App\Modules\Core\Services\SettingsService;
use App\Providers\ModuleServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class CoreServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        File::class => FilePolicy::class,
    ];

    /**
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(SettingsService::class);

        // Diganti implementasi berbasis Student::visibleTo di S10.
        $this->app->singletonIf(StudentOwnershipGate::class, DefaultStudentOwnershipGate::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->recordRequestContextOnActivities();

        Event::listen(JobProcessed::class, fn () => HealthCheckService::markQueueProcessed());

        $this->app->make(SettingsService::class)->applyRuntimeConfig();

        if ($this->app->runningInConsole()) {
            $this->commands([GenerateThumbnails::class]);
        }
    }

    /**
     * Setiap audit log menyimpan IP dan user agent (VIBE_PLANNING S07).
     */
    private function recordRequestContextOnActivities(): void
    {
        Activity::saving(function (Activity $activity) {
            if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
                return;
            }

            $request = request();

            $activity->properties = $activity->properties->merge([
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);
        });
    }
}
