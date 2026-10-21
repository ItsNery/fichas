<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Las comparativas cargan datos de todos los municipios y son costosas.
        RateLimiter::for('ficha-comparativa', function (Request $request) {
            return [
                Limit::perMinute(5)->by('ficha-comparativa:'.$request->ip()),
                Limit::perHour(30)->by('ficha-comparativa:global'),
            ];
        });

        // La generación de PDF puede iniciar procesos de navegador; se limita
        // como un recurso compartido entre todos los PDF municipales.
        RateLimiter::for('ficha-pdf', function (Request $request) {
            return [
                Limit::perMinute(2)->by('ficha-pdf:'.$request->ip()),
                Limit::perHour(30)->by('ficha-pdf:global'),
            ];
        });
    }
}
