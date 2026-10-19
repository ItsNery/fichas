<?php

namespace App\Services;

use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Formula;
use App\Models\Indicador;
use App\Models\LoteDatos;
use App\Models\Municipio;
use App\Models\SiteEvaluation;
use App\Models\Tematica;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/**
 * Métricas de panorama del portal para el tablero del administrador.
 */
class DashboardService
{
    public function resumen(): array
    {
        $ultimoAnio = (int) DatoHistorico::max('anio');

        $resumen = [
            'ultimoAnio' => $ultimoAnio,
            'kpis' => $this->kpis($ultimoAnio),
            'serieAnual' => $this->serieAnual(),
            'porDimension' => $this->porDimension($ultimoAnio),
            'catalogoUltimoAnio' => $this->catalogoUltimoAnio($ultimoAnio),
            'lotes' => $this->lotes(),
            'fichas' => $this->fichas(),
            'topIndicadores' => $this->topIndicadores(),
            'motivosSinDato' => $this->motivosSinDato(),
            'salud' => $this->salud($ultimoAnio),
            'actividad' => $this->actividad(),
            'satisfaccion' => $this->satisfaccion(),
        ];

        return $resumen;
    }

    private function kpis(int $ultimoAnio): array
    {
        $totalIndicadores = Indicador::count();
        $indicadoresConDatos = $ultimoAnio
            ? Indicador::whereHas('variables.datosHistoricos', fn ($q) => $q->where('anio', $ultimoAnio))->count()
            : 0;

        $totalMunicipios = Municipio::count();
        $municipiosConDatos = $ultimoAnio
            ? DatoHistorico::where('anio', $ultimoAnio)->distinct()->count('municipio_id')
            : 0;
        return [
            'datos' => DatoHistorico::count(),
            'indicadores' => $totalIndicadores,
            'indicadoresConDatos' => $indicadoresConDatos,
            'variables' => Variable::count(),
            'variablesKpi' => Variable::where('es_kpi', true)->count(),
            'variablesConstruidas' => Variable::where('es_construida', true)->count(),
            'municipios' => $totalMunicipios,
            'municipiosConDatos' => $municipiosConDatos,
            'dimensiones' => Dimension::count(),
            'tematicas' => Tematica::count(),
            'usuarios' => User::count(),
            'lotes' => LoteDatos::count(),
            'lotesPorRevisar' => LoteDatos::where('estado', LoteDatos::EN_REVISION)->count(),
            'lotesRechazados' => LoteDatos::where('estado', LoteDatos::RECHAZADO)->count(),
            'formulas' => Formula::count(),
            'formulasActivas' => Formula::where('activa', true)->count(),
            'fichas' => ConfiguracionFicha::count(),
            'fichasActivas' => ConfiguracionFicha::where('activo', true)->count(),
        ];
    }

    /**
     * @return array{anios: array<int, int>, registros: array<int, int>, conMotivo: array<int, int>, porcentaje: array<int, float>}
     */
    private function serieAnual(): array
    {
        $filas = DatoHistorico::select('anio', DB::raw('COUNT(*) as total'))
            ->selectRaw('SUM(CASE WHEN motivo_sin_dato_id IS NOT NULL THEN 1 ELSE 0 END) as con_motivo')
            ->whereNotNull('anio')
            ->groupBy('anio')
            ->orderBy('anio')
            ->get();

        $anios = $filas->pluck('anio')->map(fn ($anio) => (int) $anio)->all();
        $registros = $filas->pluck('total')->map(fn ($total) => (int) $total)->all();
        $conMotivo = $filas->pluck('con_motivo')->map(fn ($total) => (int) $total)->all();

        $porcentaje = array_map(function (int $total, int $motivo) {
            return $total > 0 ? round(($motivo / $total) * 100, 1) : 0.0;
        }, $registros, $conMotivo);

        return [
            'anios' => $anios,
            'registros' => $registros,
            'conMotivo' => $conMotivo,
            'porcentaje' => $porcentaje,
        ];
    }

    /**
     * @return array<int, array{nombre: string, indicadores: int, variables: int, datos: int, cobertura: float}>
     */
    private function porDimension(int $ultimoAnio): array
    {
        $totalMunicipios = max(1, Municipio::count());

        $indicadores = DB::table('dimensions')
            ->leftJoin('tematicas', 'tematicas.dimension_id', '=', 'dimensions.id')
            ->leftJoin('indicadors', 'indicadors.tematica_id', '=', 'tematicas.id')
            ->groupBy('dimensions.id', 'dimensions.nombre')
            ->select('dimensions.nombre', DB::raw('COUNT(DISTINCT indicadors.id) as indicadores'))
            ->pluck('indicadores', 'nombre');

        $variables = DB::table('dimensions')
            ->leftJoin('tematicas', 'tematicas.dimension_id', '=', 'dimensions.id')
            ->leftJoin('indicadors', 'indicadors.tematica_id', '=', 'tematicas.id')
            ->leftJoin('variables', 'variables.indicador_id', '=', 'indicadors.id')
            ->groupBy('dimensions.id', 'dimensions.nombre')
            ->select('dimensions.nombre', DB::raw('COUNT(DISTINCT variables.id) as variables'))
            ->pluck('variables', 'nombre');

        // Un único recorrido de los datos históricos evita unir 184 mil filas
        // con el catálogo en cada consulta.
        $datosPorVariable = DB::table('dato_historicos')
            ->whereNotNull('variable_id')
            ->groupBy('variable_id')
            ->select('variable_id', DB::raw('COUNT(*) as total'), DB::raw('COUNT(DISTINCT municipio_id) as municipios'))
            ->get()
            ->keyBy('variable_id');

        $variablesPorDimension = DB::table('variables')
            ->leftJoin('indicadors', 'indicadors.id', '=', 'variables.indicador_id')
            ->leftJoin('tematicas', 'tematicas.id', '=', 'indicadors.tematica_id')
            ->leftJoin('dimensions', 'dimensions.id', '=', 'tematicas.dimension_id')
            ->get(['variables.id', 'dimensions.nombre as dimension']);

        $datos = [];
        $coberturas = [];

        foreach ($variablesPorDimension as $variable) {
            if (! $variable->dimension) {
                continue;
            }

            $resumen = $datosPorVariable[$variable->id] ?? null;

            if (! $resumen) {
                continue;
            }

            $datos[$variable->dimension] = ($datos[$variable->dimension] ?? 0) + (int) $resumen->total;
        }

        // Cobertura municipal del último año: promedio de los municipios con
        // registro en las variables de cada dimensión.
        $municipiosPorVariable = $ultimoAnio
            ? DB::table('dato_historicos')
                ->where('anio', $ultimoAnio)
                ->whereNotNull('variable_id')
                ->groupBy('variable_id')
                ->select('variable_id', DB::raw('COUNT(DISTINCT municipio_id) as municipios'))
                ->get()
                ->keyBy('variable_id')
            : collect();

        foreach ($variablesPorDimension as $variable) {
            if (! $variable->dimension) {
                continue;
            }

            $resumen = $municipiosPorVariable[$variable->id] ?? null;

            if (! $resumen) {
                continue;
            }

            $coberturas[$variable->dimension][] = ((int) $resumen->municipios / $totalMunicipios) * 100;
        }

        $filas = [];

        foreach (Dimension::orderBy('orden')->orderBy('nombre')->pluck('nombre') as $nombre) {
            $coberturasDimension = $coberturas[$nombre] ?? [];

            $filas[] = [
                'nombre' => $nombre,
                'indicadores' => (int) ($indicadores[$nombre] ?? 0),
                'variables' => (int) ($variables[$nombre] ?? 0),
                'datos' => (int) ($datos[$nombre] ?? 0),
                'cobertura' => $coberturasDimension
                    ? round(array_sum($coberturasDimension) / count($coberturasDimension), 1)
                    : 0.0,
            ];
        }

        return $filas;
    }

    /**
     * @return array{conDatos: int, sinDatos: int, total: int, porcentaje: float}
     */
    private function catalogoUltimoAnio(int $ultimoAnio): array
    {
        $total = Indicador::count();

        $conDatos = $ultimoAnio
            ? Indicador::whereHas('variables.datosHistoricos', fn ($q) => $q->where('anio', $ultimoAnio))->count()
            : 0;

        return [
            'conDatos' => $conDatos,
            'sinDatos' => max(0, $total - $conDatos),
            'total' => $total,
            'porcentaje' => $total > 0 ? round(($conDatos / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{porEstado: array<string, int>, porTipo: array<string, int>}
     */
    private function lotes(): array
    {
        $porEstado = LoteDatos::select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->map(fn ($total) => (int) $total)
            ->all();

        $porTipo = LoteDatos::select('tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->map(fn ($total) => (int) $total)
            ->all();

        return ['porEstado' => $porEstado, 'porTipo' => $porTipo];
    }

    /**
     * @return array{porTipo: array<string, int>, activas: int, inactivas: int}
     */
    private function fichas(): array
    {
        $porTipo = ConfiguracionFicha::select('tipo_visualizacion', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo_visualizacion')
            ->pluck('total', 'tipo_visualizacion')
            ->map(fn ($total) => (int) $total)
            ->all();

        return [
            'porTipo' => $porTipo,
            'activas' => ConfiguracionFicha::where('activo', true)->count(),
            'inactivas' => ConfiguracionFicha::where('activo', false)->count(),
        ];
    }

    /**
     * @return array<int, array{nombre: string, datos: int}>
     */
    private function topIndicadores(): array
    {
        $datosPorVariable = DB::table('dato_historicos')
            ->whereNotNull('variable_id')
            ->groupBy('variable_id')
            ->select('variable_id', DB::raw('COUNT(*) as total'))
            ->get()
            ->keyBy('variable_id');

        $totales = [];

        DB::table('variables')
            ->join('indicadors', 'indicadors.id', '=', 'variables.indicador_id')
            ->get(['variables.id', 'variables.indicador_id', 'indicadors.nombre_amigable'])
            ->each(function ($variable) use ($datosPorVariable, &$totales) {
                $resumen = $datosPorVariable[$variable->id] ?? null;

                if (! $resumen) {
                    return;
                }

                $indicador = $variable->indicador_id;

                if (! isset($totales[$indicador])) {
                    $totales[$indicador] = [
                        'nombre' => $variable->nombre_amigable,
                        'datos' => 0,
                    ];
                }

                $totales[$indicador]['datos'] += (int) $resumen->total;
            });

        usort($totales, fn ($a, $b) => $b['datos'] <=> $a['datos']);

        return array_slice($totales, 0, 8);
    }

    /**
     * @return array<int, array{nombre: string, total: int}>
     */
    private function motivosSinDato(): array
    {
        return DB::table('dato_historicos')
            ->join('cat_motivos_sin_dato', 'dato_historicos.motivo_sin_dato_id', '=', 'cat_motivos_sin_dato.id')
            ->groupBy('cat_motivos_sin_dato.nombre')
            ->select('cat_motivos_sin_dato.nombre', DB::raw('COUNT(*) as total'))
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => [
                'nombre' => $fila->nombre,
                'total' => (int) $fila->total,
            ])
            ->all();
    }

    /**
     * @return array{indicadoresVacios: int, variablesHuerfanas: int, desactualizados: int, sinPolaridad: int}
     */
    private function salud(int $ultimoAnio): array
    {
        $desactualizados = 0;

        if ($ultimoAnio) {
            $actualizados = Indicador::whereHas('variables.datosHistoricos', fn ($q) => $q->where('anio', $ultimoAnio))
                ->pluck('id');

            $desactualizados = Indicador::whereNotIn('id', $actualizados)->has('variables')->count();
        }

        return [
            'indicadoresVacios' => Indicador::doesntHave('variables')->count(),
            'variablesHuerfanas' => Variable::whereNull('indicador_id')->count(),
            'desactualizados' => $desactualizados,
            'sinPolaridad' => Indicador::whereNull('polaridad')->orWhere('polaridad', '')->count(),
        ];
    }

    /**
     * @return array<int, array{usuario: string, evento: string, fecha: string}>
     */
    private function actividad(): array
    {
        return Activity::with('causer')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn ($log) => [
                'usuario' => $log->causer?->name ?? 'Sistema',
                'evento' => $log->description,
                'fecha' => $log->created_at->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return array{feliz: int, neutral: int, triste: int, total: int}
     */
    private function satisfaccion(): array
    {
        return [
            'feliz' => SiteEvaluation::where('score', '3')->count(),
            'neutral' => SiteEvaluation::where('score', '2')->count(),
            'triste' => SiteEvaluation::where('score', '1')->count(),
            'total' => SiteEvaluation::count(),
        ];
    }
}
