<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComparisonPageCacheService
{
    // Cambiar este prefijo invalida HTML construido con otra estrategia de
    // renderizado sin tener que vaciar la cache completa de la aplicacion.
    private const CACHE_FORMAT = 'v2';
    private const CACHE_SECONDS = 86400;
    private const LOCK_SECONDS = 90;
    private const LOCK_WAIT_SECONDS = 0;
    private const BUILD_LOCK = 'fichas:comparativa:builder';

    private const VERSION_TABLES = [
        'dato_historicos',
        'dato_indicador_complejos',
        'configuracion_fichas',
        'configuracion_ficha_variable',
        'municipios',
        'variables',
        'indicadors',
        'tematicas',
        'dimensions',
        'microrregions',
        'macrorregions',
    ];

    public function dataVersion(): string
    {
        $timestamps = [];

        foreach (self::VERSION_TABLES as $table) {
            $timestamps[$table] = DB::table($table)->max('updated_at');
        }

        return hash('sha256', json_encode($timestamps));
    }

    /**
     * @param Closure(): string $renderer
     */
    public function remember(
        string $slug1,
        string $slug2,
        string $dataVersion,
        Closure $renderer,
        ?Closure $onCacheStatus = null
    ): string
    {
        $key = 'fichas:comparativa:'.self::CACHE_FORMAT.':'.hash('sha256', $dataVersion.'|'.$slug1.'|'.$slug2);

        return $this->rememberByKey($key, $renderer, $onCacheStatus);
    }

    /**
     * Cachea el HTML de una sola dimensión de la comparativa. Mantiene el
     * mismo candado global de construcción para que una carga progresiva no
     * vuelva a saturar los workers cuando varias secciones entren a pantalla.
     *
     * @param Closure(): string $renderer
     */
    public function rememberDimension(
        string $slug1,
        string $slug2,
        int $dimensionId,
        string $dataVersion,
        Closure $renderer
    ): string {
        $key = 'fichas:comparativa:dimension:'.self::CACHE_FORMAT.':'.hash(
            'sha256',
            $dataVersion.'|'.$slug1.'|'.$slug2.'|'.$dimensionId
        );

        return $this->rememberByKey($key, $renderer);
    }

    /**
     * @param Closure(): string $renderer
     */
    private function rememberByKey(string $key, Closure $renderer, ?Closure $onCacheStatus = null): string
    {

        if (Cache::has($key)) {
            if ($onCacheStatus) {
                $onCacheStatus('hit');
            }

            return Cache::get($key);
        }

        if ($onCacheStatus) {
            $onCacheStatus('miss');
        }

        return Cache::lock($key.':lock', self::LOCK_SECONDS)->block(self::LOCK_WAIT_SECONDS, function () use ($key, $renderer) {
            // Cada par tiene su propio candado, pero la construcción de una
            // comparativa es costosa aunque el par sea distinto. Sólo una puede
            // ocupar la base de datos a la vez.
            return Cache::lock(self::BUILD_LOCK, self::LOCK_SECONDS)->block(0, function () use ($key, $renderer) {
                return Cache::remember($key, self::CACHE_SECONDS, $renderer);
            });
        });
    }
}
