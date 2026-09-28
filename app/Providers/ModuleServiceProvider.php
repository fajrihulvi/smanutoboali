<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use ReflectionClass;

/**
 * Base class untuk ServiceProvider setiap modul di app/Modules/<Module>.
 *
 * Memuat routes/web.php, Database/Migrations, policy, dan listener milik modul,
 * serta mendaftarkan namespace halaman Inertia '<Module>::<Path>'.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Pemetaan model => policy milik modul.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [];

    /**
     * Pemetaan event => daftar listener milik modul.
     *
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [];

    public function register(): void
    {
        $this->registerInertiaPageNamespace();
    }

    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadModuleViews();
        $this->loadModuleRoutes();
        $this->registerPolicies();
        $this->registerEvents();
    }

    /**
     * Nama modul, diturunkan dari namespace provider (App\Modules\<Module>).
     */
    public function moduleName(): string
    {
        $namespace = (new ReflectionClass($this))->getNamespaceName();

        return class_basename($namespace);
    }

    public function modulePath(string $path = ''): string
    {
        $base = dirname((string) (new ReflectionClass($this))->getFileName());

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }

    protected function loadModuleMigrations(): void
    {
        $path = $this->modulePath('Database/Migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * Blade view modul (mis. template PDF) di `resources/views`, dipanggil sebagai
     * '<module-kebab>::nama', contoh 'report-card::pdf'.
     */
    protected function loadModuleViews(): void
    {
        $path = $this->modulePath('resources/views');

        if (is_dir($path)) {
            $this->loadViewsFrom($path, Str::kebab($this->moduleName()));
        }
    }

    protected function loadModuleRoutes(): void
    {
        $path = $this->modulePath('routes/web.php');

        if (! is_file($path) || $this->app->routesAreCached()) {
            return;
        }

        Route::middleware('web')->group($path);
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    protected function registerEvents(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * Daftarkan namespace '<Module>' ke view finder Inertia agar page name
     * 'Module::Path' bisa diverifikasi (ensure_pages_exist & assertInertia).
     */
    protected function registerInertiaPageNamespace(): void
    {
        $pages = resource_path('js/Modules/'.$this->moduleName().'/Pages');

        foreach (['inertia.view-finder', 'inertia.testing.view-finder'] as $binding) {
            $this->app->extend($binding, function (FileViewFinder $finder) use ($pages): FileViewFinder {
                $finder->addNamespace($this->moduleName(), $pages);

                return $finder;
            });
        }
    }
}
