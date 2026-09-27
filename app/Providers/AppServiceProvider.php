<?php

namespace App\Providers;

use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ActivePeriod::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! app()->isProduction());

        // App\Modules\<M>\Models\<Name> => App\Modules\<M>\Database\Factories\<Name>Factory
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            if (preg_match('/^App\\\\Modules\\\\(\w+)\\\\Models\\\\(.+)$/', $modelName, $matches)) {
                return 'App\\Modules\\'.$matches[1].'\\Database\\Factories\\'.$matches[2].'Factory';
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });

        // Dibaca dari config (bukan env di bootstrap) agar tetap jalan setelah config:cache.
        if ($proxies = config('skoolia.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', (string) $proxies)));
        }

        if (config('skoolia.force_https')) {
            URL::forceScheme('https');
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
