<?php

namespace App\Services;

use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\DatoIndicadorComplejo;
use App\Models\Municipio;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ComparisonPdfDataService
{
    /**
     * Construye una comparativa de lectura rápida. Sólo consulta los dos
     * municipios seleccionados y conserva el último corte de cada variable.
     */
    public function build(string $slug1, string $slug2): array
    {
        $municipio1 = Municipio::where('slug', $slug1)->firstOrFail();
        $municipio2 = Municipio::where('slug', $slug2)->firstOrFail();
        $municipio1->load('microrregion.macrorregion');
        $municipio2->load('microrregion.macrorregion');

        $configuraciones = ConfiguracionFicha::with([
            'indicador.tematica.dimension',
            'indicador.variables',
            'variables',
        ])
            ->visibleEnPerfilPublico()
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get();

        $variablesPorConfiguracion = $configuraciones->mapWithKeys(function ($configuracion) {
            $variables = $configuracion->variables->where('visible_en_ficha', true);

            return [$configuracion->id => $variables->isNotEmpty()
                ? $variables->values()
                : $configuracion->indicador?->variables->where('visible_en_ficha', true)->values()];
        });
        $variableIds = $variablesPorConfiguracion->flatten()->pluck('id')->unique()->values();
        $municipioIds = [$municipio1->id, $municipio2->id];

        $historicos = DatoHistorico::with(['variable', 'motivoSinDato'])
            ->whereIn('municipio_id', $municipioIds)
            ->whereIn('variable_id', $variableIds)
            ->orderByDesc('anio')
            ->get()
            ->groupBy(fn ($dato) => $dato->municipio_id.':'.$dato->variable_id)
            ->map(fn ($datos) => $datos->first());

        $indicadorIdsComplejos = $configuraciones
            ->filter(fn ($configuracion) => (bool) ($configuracion->indicador?->es_complejo))
            ->pluck('indicador_id')
            ->unique()
            ->values();
        $complejos = DatoIndicadorComplejo::whereIn('municipio_id', $municipioIds)
            ->whereIn('indicador_id', $indicadorIdsComplejos)
            ->orderByDesc('anio')
            ->get()
            ->groupBy(fn ($dato) => $dato->municipio_id.':'.$dato->indicador_id)
            ->map(fn ($datos) => $datos->first());

        $secciones = [];
        foreach ($configuraciones as $configuracion) {
            $indicador = $configuracion->indicador;
            if (! $indicador) {
                continue;
            }

            $dimension = $indicador->tematica?->dimension;
            $claveDimension = $dimension?->id ?? 'sin-dimension';
            if (! isset($secciones[$claveDimension])) {
                $secciones[$claveDimension] = [
                    'nombre' => $dimension?->nombre ?? 'Sin dimensión',
                    'color' => $dimension?->color ?: '#872341',
                    'indicadores' => [],
                ];
            }

            $filas = $indicador->es_complejo
                ? $this->filasComplejas($complejos, $municipio1, $municipio2, $indicador->id)
                : $this->filasHistoricas($variablesPorConfiguracion->get($configuracion->id, collect()), $historicos, $municipio1, $municipio2);

            if ($filas === []) {
                continue;
            }

            $secciones[$claveDimension]['indicadores'][] = [
                'nombre' => $configuracion->titulo_reporte ?: $indicador->nombre_amigable,
                'tematica' => $indicador->tematica?->nombre ?? 'Sin temática',
                'fuente' => $indicador->fuente,
                'filas' => $filas,
            ];
        }

        return [
            'municipio1' => $municipio1,
            'municipio2' => $municipio2,
            'hero1' => app(FichaProfilerService::class)->getHeroStats($municipio1),
            'hero2' => app(FichaProfilerService::class)->getHeroStats($municipio2),
            'secciones' => array_values($secciones),
            'generadoEn' => now(),
        ];
    }

    private function filasHistoricas($variables, $historicos, Municipio $municipio1, Municipio $municipio2): array
    {
        return $variables->map(function ($variable) use ($historicos, $municipio1, $municipio2) {
            return [
                'nombre' => $variable->nombre_amigable,
                'unidad' => $variable->unidad_medida,
                'izquierda' => $this->datoHistorico($historicos->get($municipio1->id.':'.$variable->id)),
                'derecha' => $this->datoHistorico($historicos->get($municipio2->id.':'.$variable->id)),
            ];
        })->all();
    }

    private function filasComplejas($complejos, Municipio $municipio1, Municipio $municipio2, int $indicadorId): array
    {
        $izquierda = $this->aplanarDatosComplejos($complejos->get($municipio1->id.':'.$indicadorId)?->datos ?? []);
        $derecha = $this->aplanarDatosComplejos($complejos->get($municipio2->id.':'.$indicadorId)?->datos ?? []);
        $anioIzquierda = $complejos->get($municipio1->id.':'.$indicadorId)?->anio;
        $anioDerecha = $complejos->get($municipio2->id.':'.$indicadorId)?->anio;

        return collect(array_unique(array_merge(array_keys($izquierda), array_keys($derecha))))
            ->map(fn ($clave) => [
                'nombre' => $this->etiquetaCompleja($clave),
                'unidad' => null,
                'izquierda' => ['valor' => $izquierda[$clave] ?? 'N/D', 'anio' => $anioIzquierda],
                'derecha' => ['valor' => $derecha[$clave] ?? 'N/D', 'anio' => $anioDerecha],
            ])
            ->values()
            ->all();
    }

    private function datoHistorico(?DatoHistorico $dato): array
    {
        return ['valor' => $dato?->valor_display ?? 'N/D', 'anio' => $dato?->anio];
    }

    private function aplanarDatosComplejos(array $datos): array
    {
        return collect(Arr::dot($datos))
            ->filter(fn ($valor) => is_scalar($valor) || $valor === null)
            ->map(fn ($valor) => $valor === null ? 'N/D' : (is_numeric($valor) ? number_format((float) $valor, 2) : (string) $valor))
            ->all();
    }

    private function etiquetaCompleja(string $clave): string
    {
        return Str::of($clave)
            ->replaceMatches('/(^|\.)\d+(?=\.|$)/', ' ')
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->headline();
    }
}
