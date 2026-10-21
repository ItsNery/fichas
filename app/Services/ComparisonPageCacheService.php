<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComparisonPageCacheService
{
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
    public function remember(string $slug1, string $slug2, string $dataVersion, Closure $renderer): string
    {
        $key = 'fichas:comparativa:'.hash('sha256', $dataVersion.'|'.$slug1.'|'.$slug2);

        if (Cache::has($key)) {
            return Cache::get($key);
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
