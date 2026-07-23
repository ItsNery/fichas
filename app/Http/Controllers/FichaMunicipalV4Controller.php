<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionFicha;
use App\Models\Dimension;
use App\Models\Municipio;
use App\Services\FichaComposerService;
use App\Services\FichaDataStore;
use App\Services\FichaNarratorService;
use App\Services\FichaProfilerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FichaMunicipalV4Controller extends Controller
{
    public function index(Municipio $municipio)
    {
        $municipio->load('microrregion.macrorregion');
        $configs = $this->publicConfigs();
        $summary = $this->summary($municipio);
        $sections = $configs->groupBy(fn ($config) => $config->indicador->tematica->dimension->id)
            ->map(function ($items) use ($municipio) {
                $dimension = $items->first()->indicador->tematica->dimension;

                return [
                    'id' => $dimension->id,
                    'name' => $dimension->nombre,
                    'slug' => Str::slug($dimension->nombre),
                    'color' => $dimension->color,
                    'count' => $items->count(),
                    'url' => route('ficha-municipal.v4.section', [
                        'municipio' => $municipio->slug,
                        'dimension' => Str::slug($dimension->nombre),
                    ]),
                ];
            })->values();

        return view('municipios.v4.perfil', compact('municipio', 'summary', 'sections'));
    }

    public function section(Municipio $municipio, string $dimension): JsonResponse
    {
        $configs = $this->publicConfigs()->filter(
            fn ($config) => Str::slug($config->indicador->tematica->dimension->nombre) === $dimension
        )->values();

        abort_if($configs->isEmpty(), 404);

        $dataStore = new FichaDataStore($municipio, FichaDataStore::extractVariableIds($configs));
        $composer = app(FichaComposerService::class);
        $items = $configs->map(function ($config) use ($municipio, $dataStore, $composer) {
            $datos = $composer->obtenerDatosParaConfig($config, $municipio, $dataStore);
            $indicador = $config->indicador;
            $variables = $config->variables->where('visible_en_ficha', true);
            $variable = $variables->first()
                ?? $indicador->variables->where('visible_en_ficha', true)->first();
            $variableIds = ($variables->isNotEmpty() ? $variables : $indicador->variables->where('visible_en_ficha', true))
                ->pluck('id');
            $quality = $this->quality($dataStore, $variableIds);
            $displayValue = match ($config->tipo_visualizacion) {
                'scatter' => is_array($datos) && $datos['correlacion'] !== null
                    ? 'r = ' . number_format((float) $datos['correlacion'], 3)
                    : 'Sin correlación',
                'piramide' => 'Distribución por edad y sexo',
                default => is_array($datos) ? ($datos['valor_actual'] ?? $datos['total'] ?? null) : $datos,
            };

            return [
                'id' => $config->id,
                'title' => $config->titulo_reporte ?: $indicador->nombre_amigable,
                'subtitle' => $config->subtitulo_reporte,
                'visualization' => $config->tipo_visualizacion,
                'icon' => $config->icono,
                'value' => $displayValue,
                'year' => is_array($datos) ? ($datos['anio'] ?? null) : null,
                'unit' => $config->tipo_visualizacion === 'scatter' ? 'Coeficiente de Pearson' : $variable?->unidad_medida,
                'source' => $indicador->fuente,
                'definition' => $variable?->definicion_operativa ?: $indicador->descripcion,
                'method' => is_array($datos) ? ($datos['metodo_calculo'] ?? null) : null,
                'narrative' => FichaNarratorService::procesar($config->plantilla_narrativa, $municipio, $datos ?? []),
                'data' => $datos,
                'quality' => $quality,
            ];
        })->filter(fn ($item) => $item['data'] !== null)->values();

        return response()->json([
            'section' => $dimension,
            'items' => $items,
        ]);
    }

    public function searchComparison(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Municipio::where('nombre', 'like', "%{$query}%")
                ->orderBy('nombre')
                ->limit(8)
                ->get(['slug', 'nombre'])
                ->map(fn ($municipio) => [
                    'id' => $municipio->slug,
                    'text' => $municipio->nombre,
                ])
                ->values()
        );
    }

    private function publicConfigs()
    {
        return ConfiguracionFicha::with([
            'indicador.variables',
            'indicador.tematica.dimension',
            'variables',
        ])->where('activo', true)
            ->whereHas('indicador', fn ($query) => $query->visiblePublicamente())
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    private function summary(Municipio $municipio): array
    {
        $hero = FichaProfilerService::getHeroStats($municipio);

        return [
            'headline' => 'Resumen estadístico municipal',
            'quality' => [
                'status' => 'provisional',
                'label' => 'Calidad provisional',
                'message' => 'El año de referencia y la cobertura se calculan automáticamente desde los datos disponibles. Pendiente de validación oficial.',
            ],
            'cards' => [
                ['label' => 'Población total', 'value' => number_format($hero['poblacionTotal']), 'unit' => 'habitantes'],
                ['label' => 'Población en pobreza', 'value' => $hero['porcentajePobreza'], 'unit' => 'último dato disponible'],
                ['label' => 'Población activa', 'value' => number_format($hero['pea']), 'unit' => 'personas'],
                ['label' => 'Marginación', 'value' => $hero['gradoMarginacion'], 'unit' => 'grado'],
            ],
        ];
    }

    private function quality(FichaDataStore $dataStore, $variableIds): array
    {
        $rows = $dataStore->globalData
            ->whereIn('variable_id', $variableIds)
            ->filter(fn ($row) => $row->valor !== null);

        if ($rows->isEmpty()) {
            return [
                'status' => 'sin_datos',
                'label' => 'Sin datos',
                'message' => 'No hay observaciones históricas disponibles para esta configuración.',
            ];
        }

        $year = (int) $rows->max('anio');
        $coverage = $rows->where('anio', $year)->pluck('municipio_id')->unique()->count();
        $expected = max(1, Municipio::count());

        return [
            'status' => 'provisional',
            'label' => 'Cobertura calculada',
            'year' => $year,
            'coverage' => $coverage,
            'expected' => $expected,
            'coverage_percent' => round(($coverage / $expected) * 100, 1),
            'message' => 'Cálculo automático desde datos históricos; pendiente de validación oficial.',
        ];
    }
}
