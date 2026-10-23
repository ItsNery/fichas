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
            // El perfilado se autoriza con un secreto de servidor enviado en
            // encabezado; permite una medición puntual aun cuando el límite
            // global esté lleno, sin exponer una ruta alternativa pública.
            $profileToken = config('traffic.comparison_profile_token');
            if (is_string($profileToken) && $profileToken !== ''
                && hash_equals($profileToken, (string) $request->header('X-Comparison-Profile'))) {
                return Limit::none();
            }

            return [
                Limit::perMinute(10)->by('ficha-comparativa:'.$this->visitorKey($request)),
                Limit::perHour(300)->by('ficha-comparativa:global'),
            ];
        });

        // Las secciones progresivas se solicitan en serie desde una sola
        // comparativa. Tienen un limite propio para no bloquear una visita
        // legitima, pero mantienen el tope horario compartido.
        RateLimiter::for('ficha-comparativa-seccion', function (Request $request) {
            $profileToken = config('traffic.comparison_profile_token');
            if (is_string($profileToken) && $profileToken !== ''
                && hash_equals($profileToken, (string) $request->header('X-Comparison-Profile'))) {
                return Limit::none();
            }

            return [
                Limit::perMinute(12)->by('ficha-comparativa-seccion:'.$this->visitorKey($request)),
                Limit::perHour(300)->by('ficha-comparativa-seccion:global'),
            ];
        });

        // Las dimensiones de una ficha se componen bajo demanda. El límite
        // conserva una visita normal fluida sin permitir que se dispare el
        // trabajo de todas las secciones en paralelo.
        RateLimiter::for('ficha-perfil-seccion', function (Request $request) {
            $visitorKey = $request->hasSession()
                ? $request->session()->getId()
                : $request->ip();

            return [
                Limit::perMinute(12)->by('ficha-perfil-seccion:'.$visitorKey),
                Limit::perHour(360)->by('ficha-perfil-seccion:global'),
            ];
        });

        // La generación de PDF puede iniciar procesos de navegador; se limita
        // por visitante para que la cuota compartida no se agote por la IP
        // del balanceador, que es la misma para todos.
        RateLimiter::for('ficha-pdf', function (Request $request) {
            return [
                Limit::perMinute(4)->by('ficha-pdf:'.$this->visitorKey($request)),
                Limit::perHour(60)->by('ficha-pdf:'.$this->visitorKey($request)),
                Limit::perHour(600)->by('ficha-pdf:global'),
            ];
        });
    }

    /**
     * Identidad propia del visitante: la sesión (o la cookie de huella)
     * evita que todos los usuarios detrás del WAF compartan una sola cuota.
     * La IP sólo queda como último recurso.
     */
    protected function visitorKey(Request $request): string
    {
        // Sólo si la petición trajo la cookie de sesión: un cliente sin
        // cookies recibiría una sesión nueva en cada request y quedaría
        // siempre fuera de límite.
        if ($request->hasSession() && $request->cookies->has(config('session.cookie'))) {
            return 'sess:'.substr($request->session()->getId(), 0, 40);
        }

        $cookie = $request->cookie((string) config('traffic.visitor_cookie'));

        if (is_string($cookie) && $cookie !== '') {
            return 'visitor:'.substr(hash('sha256', $cookie), 0, 40);
        }

        return 'ip:'.$request->ip();
    }
}
