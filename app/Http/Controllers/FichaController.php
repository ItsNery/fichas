<?php

namespace App\Http\Controllers;

use App\Models\DatoHistorico;
use App\Models\DatoIndicadorComplejo;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Variable;
use App\Models\ConfiguracionFicha;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\ExportService;
use App\Services\ExportV3Service;
use App\Services\FichaDataStore;
use App\Services\FichaLayoutService;
use App\Services\FichaNarratorService;
use App\Services\FichaProfilerService;
use App\Services\IndicadorQueryService;
use App\Services\MapDataService;
use App\Services\MunicipalityMapImageService;
use App\Services\RankingService;
use App\Services\FichaComposerService;
use App\Services\CachedPdfService;
use App\Services\ComparisonPageCacheService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Clase FichaController
 *
 * Controlador principal encargado de la gestión, consulta, renderizado y exportación
 * de la información estadística e histórica de las Fichas Municipales.
 * Proporciona endpoints para visualizaciones dinámicas (ECharts), generación de PDFs,
 * exportaciones de datos en formatos tabulares (Excel/CSV) y herramientas comparativas intermunicipales.
 *
 * @package App\Http\Controllers
 */
class FichaController extends Controller
{
    /**
     * Retrieves catalog data (Dimensions, Tematicas, Indicadores, Variables)
     * and geographical catalogs (Municipios, Microrregiones, Macrorregiones)
     * to display the main fact sheets view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $dimensiones = Dimension::where('visible_en_ficha', true)->with([
            'tematicas' => function ($q) {
                $q->where('visible_en_ficha', true)->orderBy('orden')->orderBy('nombre');
            },
            'tematicas.indicadores' => function ($query) {
                $query->where('visible_en_ficha', true)
                    ->where('solo_resumen', false)
                    ->orderBy('orden')->orderBy('nombre_amigable');
            },
            'tematicas.indicadores.variables' => function ($q) {
                $q->where('visible_en_ficha', true)->orderBy('orden')->orderBy('nombre_amigable');
            },
        ])->orderBy('orden')->orderBy('nombre')->get();

        $indicadoresEstatales = app(IndicadorQueryService::class)->getStateAvailableIndicatorIds();
        foreach ($dimensiones as $dimension) {
            foreach ($dimension->tematicas as $tematica) {
                foreach ($tematica->indicadores as $indicador) {
                    $indicador->disponible_estatal = strtolower(trim((string) $indicador->tipo_dato)) === 'absoluto'
                        || $indicadoresEstatales->contains($indicador->id);
                }
            }
        }

        // Obtenemos los catálogos geográficos
        $municipios     = Municipio::orderBy('nombre', 'asc')->get();
        $microrregiones = Microrregion::orderBy('nombre', 'asc')->get();
        $macrorregiones = Macrorregion::orderBy('nombre', 'asc')->get();

        return view('fichas', [
            'dimensiones'    => $dimensiones,
            'municipios'     => $municipios,
            'microrregiones' => $microrregiones,
            'macrorregiones' => $macrorregiones,
        ]);
    }

    /**
     * Obtiene y formatea los datos para uno o varios municipios, o el total estatal.
     * Responde a llamadas AJAX con el método POST.
     * @param string $municipioId El ID del municipio o la palabra 'estatal'.
     * @param Indicador $indicador El objeto del indicador a consultar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $validated = $request->validate([
            'indicador_id'        => 'required|integer|exists:indicadors,id',
            'nivel_de_agregacion' => 'required|string|in:municipio,microrregion,macrorregion,estatal',
            'municipio_ids'       => 'nullable|array',
            'municipio_ids.*'     => 'string',
            'region_id'           => 'nullable|integer',
            'anios'               => 'nullable|array',
            'anios.*'             => 'integer',
        ]);
        $chartData = $this->getChartData($validated);

        return response()->json($chartData);
    }

    /**
     * Retrieves and processes data to generate the structure required for a chart (Highcharts/ApexCharts compatible).
     * It handles complex, comparative, and aggregated indicators based on geographic level.
     *
     * @param  array  $validated  The validated input parameters (indicador_id, nivel_de_agregacion, anios, etc.).
     * @return array
     */
    public function getChartData(array $validated)
    {
        $queryService = app(IndicadorQueryService::class);
        $chartData = $queryService->getChartData($validated);

        $indicador = Indicador::find($validated['indicador_id']);
        $nivel = $validated['nivel_de_agregacion'];
        $selection = $queryService->prepareGeographicSelection($nivel, $validated);

        if ($nivel === 'estatal' || in_array('estatal', $selection['ids'] ?? [])) {
            $anioParaMapa = $chartData['selected_years'][0] ?? $chartData['available_years']->first() ?? null;
            if ($anioParaMapa) {
                $chartData['mapData'] = app(MapDataService::class)->getMapData($indicador, $anioParaMapa);
            }
        }

        return $chartData;
    }

    /**
     * Exports processed chart data (obtained via getChartData) into a downloadable Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportData(Request $request)
    {
        $validated = $request->validate([
            'indicador_id'        => 'required|integer|exists:indicadors,id',
            'nivel_de_agregacion' => 'required|string|in:municipio,microrregion,macrorregion,estatal',
            'municipio_ids'       => 'nullable|array',
            'municipio_ids.*'     => 'string',
            'region_id'           => 'nullable|integer',
            'anios'               => 'nullable|array',
            'anios.*'             => 'integer',
        ]);

        $chartData = $this->getChartData($validated);

        return app(ExportService::class)->exportChartData($chartData);
    }






    /**
     * Generates and exports a PDF summary of key performance indicators (KPIs)
     * for the specified municipality, grouping the data by Dimension and Tematica.
     *
     * @param  \App\Models\Municipio  $municipio // Asume que el modelo se llama 'Municipio'
     * @return \Illuminate\Http\Response // Retorna una respuesta de descarga de archivo (PDF)
     */
    public function exportarResumenPDF(Municipio $municipio)
    {
        return app(ExportService::class)->exportResumenPDF($municipio);
    }

    public function exportarResumenV3PDF(Request $request, Municipio $municipio)
    {
        $municipio->load('microrregion.macrorregion');
        $hero = $this->getHeroStats($municipio);
        $datosAgrupados = $this->buildResumenV3Structure($municipio);

        if ($request->query('preview')) {
            return view('municipios.resumen_v3_pdf', [
                'municipio'        => $municipio,
                'poblacionTotal'   => $hero['poblacionTotal'],
                'gradoMarginacion' => $hero['gradoMarginacion'],
                'superficieKm2'    => $hero['superficieKm2'],
                'presupuestoTotal' => $hero['presupuesto'],
                'datosAgrupados'   => $datosAgrupados,
            ]);
        }

        $html = view('municipios.resumen_v3_pdf', [
            'municipio'        => $municipio,
            'poblacionTotal'   => $hero['poblacionTotal'],
            'gradoMarginacion' => $hero['gradoMarginacion'],
            'superficieKm2'    => $hero['superficieKm2'],
            'presupuestoTotal' => $hero['presupuesto'],
            'datosAgrupados'   => $datosAgrupados,
        ])->render();

        $fileName = 'resumen-' . str($municipio->nombre)->slug() . '.pdf';
        return app(ExportV3Service::class)->exportResumenV3PDF($html, $fileName);
    }

    public function exportarPerfilPDF(Request $request, Municipio $municipio)
    {
        $municipio->load('microrregion.macrorregion');

        // Get the configured indicators for the public perfil (same as interactive /perfil view)
        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->visibleEnPerfilPublico()
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        // Build dimension colors from configurations
        $dimensionColors = [];
        foreach ($configuraciones as $config) {
            $dimensionNombre = $config->indicador?->tematica?->dimension?->nombre;
            if ($dimensionNombre && !isset($dimensionColors[$dimensionNombre])) {
                $dimensionColors[$dimensionNombre] = $config->indicador?->tematica?->dimension?->color ?: '#872341';
            }
        }

        // Collect all variable IDs from configured indicators only
        $indicadorIds = $configuraciones->pluck('indicador_id')->unique()->filter()->all();
        $variableIds = $configuraciones->pluck('variables')->flatten()->pluck('id')->all();
        $indicadorVariableIds = Variable::whereIn('indicador_id', $indicadorIds)
            ->where('visible_en_ficha', true)
            ->pluck('id')
            ->all();
        $allVariableIds = array_unique(array_merge($variableIds, $indicadorVariableIds));

        // Query historical data for these variables only
        $datosHistoricos = DatoHistorico::with(['variable', 'motivoSinDato'])
            ->where('municipio_id', $municipio->id)
            ->whereIn('variable_id', $allVariableIds)
            ->get()
            ->groupBy('variable_id');

        // Also fetch complex indicator data
        $complejoIndicadorIds = $configuraciones->where('indicador.es_complejo', true)
            ->pluck('indicador_id')
            ->unique()
            ->filter()
            ->all();
        $datosComplejos = DatoIndicadorComplejo::where('municipio_id', $municipio->id)
            ->whereIn('indicador_id', $complejoIndicadorIds)
            ->latest('anio')
            ->get()
            ->groupBy('indicador_id');

        $perfil = [];
        $processedIndicadorIds = [];

        // Deduplicate by unique indicador_id (keep first config per indicator)
        // This matches the panorama view behavior: one card per indicator, not per config
        $uniqueConfigs = $configuraciones->unique('indicador_id');

        foreach ($uniqueConfigs as $config) {
            $indicador = $config->indicador;
            $indicadorId = $indicador?->id;

            if (in_array($indicadorId, $processedIndicadorIds)) {
                continue;
            }
            $processedIndicadorIds[] = $indicadorId;

            $dimensionNombre = $indicador?->tematica?->dimension?->nombre ?? 'Sin Dimensión';
            $tematicaNombre = $indicador?->tematica?->nombre ?? 'Sin Temática';

            // Use config's tipo_visualizacion if set, otherwise fall back to indicator's default
            $tipoVisualizacion = $config->tipo_visualizacion ?? $indicador?->tipo_grafico_default ?? 'bar';

            $datos = null;

            if ($indicador && $indicador->es_complejo) {
                $registro = $datosComplejos->get($indicadorId, collect())->first();
                $datos = $registro ? ['anio' => $registro->anio, 'complejo' => $registro->datos] : null;
            } elseif ($indicador && $indicador->variables->isNotEmpty()) {
                $variables = $indicador->variables->where('visible_en_ficha', true);
                $aniosComunes = $variables->map(function ($variable) use ($datosHistoricos) {
                    return $datosHistoricos->get($variable->id, collect())->pluck('anio')->all();
                })->reduce(fn ($carry, $anios) => $carry === null ? $anios : array_values(array_intersect($carry, $anios)));
                $anio = $aniosComunes ? max($aniosComunes) : null;

                // Fallback: if no common years across all variables, use the latest year
                // available for each variable independently (handles multi-variable indicators
                // where variables have different year ranges)
                if (!$anio) {
                    $latestAnio = $variables->map(function ($variable) use ($datosHistoricos) {
                        $years = $datosHistoricos->get($variable->id, collect())->pluck('anio')->all();
                        return $years ? max($years) : null;
                    })->filter()->max();
                    $anio = $latestAnio;
                }

                if ($anio) {
                    $valores = $variables->map(function ($variable) use ($datosHistoricos, $anio) {
                        $dato = $datosHistoricos->get($variable->id, collect())->firstWhere('anio', $anio);
                        return [
                            'nombre' => $variable->nombre_amigable,
                            'valor' => $dato?->valor,
                            'display' => $dato?->valor_display ?? 'N/D',
                            'unidad' => $variable->unidad_medida,
                        ];
                    });
                    $datos = [
                        'anio' => $anio,
                        'type' => $tipoVisualizacion, // Use config's visualization type
                        'categorias' => $valores->pluck('nombre')->all(),
                        'series' => [[
                            'name' => $indicador->nombre_amigable,
                            'data' => $valores->pluck('valor')->map(fn ($valor) => $valor === null ? 0 : (float) $valor)->all(),
                        ]],
                        'valores' => $valores->all(),
                    ];
                }
            }

            $perfil[$dimensionNombre][$tematicaNombre][] = [
                'indicador' => $indicador,
                'datos' => $datos,
                'config' => $config,
            ];
        }

        if ($request->query('preview')) {
            // Get map image and cintillo for the preview too
            $mapImageService = app(MunicipalityMapImageService::class);
            $mapImageDataUri = 'data:image/png;base64,'.base64_encode(
                $mapImageService->render((string) $municipio->cvegeo)
            );
            $cintillo = base64_encode(file_get_contents(public_path('img/Cintillo SEI-07.png')));

            return view('municipios.panorama_pdf', [
                'municipio'       => $municipio,
                'perfil'          => $perfil,
                'dimensionColors' => $dimensionColors,
                'hero'            => $this->getHeroStats($municipio),
                'mapImageDataUri' => $mapImageDataUri,
                'cintillo'        => $cintillo,
                'geojsonUrl'      => asset('geojson/municipios_puebla_slim.geojson'),
            ]);
        }

        // Get map image data for the panorama layout
        $mapImageService = app(MunicipalityMapImageService::class);
        $mapImageDataUri = 'data:image/png;base64,'.base64_encode(
            $mapImageService->render((string) $municipio->cvegeo)
        );
        $cintillo = base64_encode(file_get_contents(public_path('img/Cintillo SEI-07.png')));

        $html = view('municipios.panorama_pdf', [
            'municipio'       => $municipio,
            'perfil'          => $perfil,
            'dimensionColors' => $dimensionColors,
            'hero'            => $this->getHeroStats($municipio),
            'mapImageDataUri' => $mapImageDataUri,
            'cintillo'        => $cintillo,
            'geojsonUrl'      => asset('geojson/municipios_puebla_slim.geojson'),
        ])->render();

        $fileName = 'perfil-' . str($municipio->nombre)->slug() . '.pdf';
        return $this->downloadCachedPdf(
            'perfil/'.$municipio->getKey(),
            $html,
            $fileName,
            fn () => app(ExportV3Service::class)->renderPerfilPDF($html)
        );
    }



    /**
     * Retrieves and aggregates historical data by 'cvegeo' (municipio code) for a specific year,
     * prioritizing the 'Total' variable of the Indicator if available, to generate data for a thematic map.
     *
     * @param  \App\Models\Indicador  $indicador // The Indicador model instance.
     * @param  int|string  $anio // The year for which the data is required.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMapData(Indicador $indicador, $anio)
    {
        abort_unless(Indicador::visiblePublicamente()->whereKey($indicador->id)->exists(), 404);

        return response()->json(
            app(MapDataService::class)->getMapData($indicador, $anio)
        );
    }

    /**
     * Retrieves a distinct list of all available historical years for the specified Indicator.
     *
     * @param  \App\Models\Indicador  $indicador // The Indicador model instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndicatorYears(Indicador $indicador)
    {
        abort_unless(Indicador::visiblePublicamente()->whereKey($indicador->id)->exists(), 404);

        return response()->json(
            app(IndicadorQueryService::class)->getIndicatorYears($indicador)
        );
    }

    /**
     * Retrieves Key Performance Indicators (KPIs) for the specified municipality,
     * grouping the latest historical data by Dimension and Tematica, and displays the summary view.
     *
     * @param  \App\Models\Municipio  $municipio // Asume que el modelo se llama 'Municipio'
     * @return \Illuminate\View\View
     */
    public function resumenMunicipal(Municipio $municipio)
    {
        $datos = $this->buildKpisFromVariables($municipio);
        $this->injectInstrumentosFantasma($municipio, $datos);

        return view('municipios.resumen', [
            'municipio' => $municipio,
            'datosAgrupados' => array_values($datos),
        ]);
    }

    /**
     * Muestra la versión 3 del resumen municipal.
     * Incluye datos básicos del Hero (población, marginación, presupuesto, pobreza, PEA)
     * e indicadores agrupados por su dimensión y temática para la ficha general.
     *
     * @param  \App\Models\Municipio  $municipio El municipio a visualizar.
     * @return \Illuminate\View\View Vista del resumen municipal V3.
     */
    public function resumenMunicipalV3(Municipio $municipio)
    {
        $hero = $this->getHeroStats($municipio);

        return view('municipios.resumen_v3', [
            'municipio'        => $municipio,
            'poblacionTotal'   => $hero['poblacionTotal'],
            'gradoMarginacion' => $hero['gradoMarginacion'],
            'superficieKm2'    => $hero['superficieKm2'],
            'presupuestoTotal' => $hero['presupuesto'],
            'anioPresupuesto'  => $hero['ultimoAnioPres'] ?? 'N/D',
            'wikiSummary'      => app(FichaComposerService::class)->getWikipediaSummary($municipio->nombre),
            'datosAgrupados'   => $this->buildResumenV3Structure($municipio),
        ]);
    }

    /**
     * Endpoint de pruebas para el resumen municipal.
     * Permite probar la optimización de consultas del Hero y la estructuración
     * de los KPIs del municipio.
     *
     * @param  \App\Models\Municipio  $municipio El municipio a probar.
     * @return \Illuminate\View\View Vista resumen_test.
     */
    public function resumenMunicipalTest(Municipio $municipio)
    {
        // ponytail: Obtener estadísticas optimizadas del Hero usando FichaProfilerService
        $hero = $this->getHeroStats($municipio);
        $poblacionTotal = $hero['poblacionTotal'];
        $gradoMarginacion = $hero['gradoMarginacion'];
        $superficieKm2 = $hero['superficieKm2'];
        $presupuestoTotal = $hero['presupuesto'];
        $anioPresupuesto = $hero['ultimoAnioPres'] ?? 'N/D';

        $variablesKPI = Variable::with('indicador.tematica.dimension')
            ->where('es_kpi', true)
            ->get();

        $datosPorDimension = [];

        foreach ($variablesKPI as $variable) {
            $datos = DatoHistorico::where('variable_id', $variable->id)
                ->where('municipio_id', $municipio->id)
                ->orderBy('anio', 'desc')
                ->take(5)
                ->get();

            $datoActual = $datos->first();

            if (!$datoActual) {
                continue;
            }

            $tendencia = null;
            $tendenciaClase = '';
            $tendenciaIcono = '';
            $historial = [];

            if ($datos->count() > 1) {
                $datoAnterior = $datos->get(1);
                if ($datoAnterior && $datoAnterior->valor > 0) {
                    $cambio = (($datoActual->valor - $datoAnterior->valor) / $datoAnterior->valor) * 100;
                    $tendencia = round($cambio, 1);

                    if ($tendencia > 0) {
                        $tendenciaClase = 'text-success';
                        $tendenciaIcono = 'fas fa-arrow-up';
                    } elseif ($tendencia < 0) {
                        $tendenciaClase = 'text-danger';
                        $tendenciaIcono = 'fas fa-arrow-down';
                    } else {
                        $tendenciaClase = 'text-muted';
                        $tendenciaIcono = 'fas fa-minus';
                    }
                }
            }

            $datosAsc = $datos->sortBy('anio');
            foreach ($datosAsc as $d) {
                $historial[] = ['anio' => $d->anio, 'valor' => $d->valor];
            }

            $unidad = strtolower($variable->unidad_medida ?? '');
            $tipoVisual = 'absoluto';
            if ($config->tipo_visualizacion === 'scatter') {
                $tipoVisual = 'scatter';
            } elseif (str_contains($unidad, '%') || str_contains($unidad, 'porcentaje')) {
                $tipoVisual = 'porcentaje';
            } elseif (str_contains($unidad, '$') || str_contains($unidad, 'pesos')) {
                $tipoVisual = 'moneda';
            }

            $dimension      = $variable->indicador->tematica->dimension;
            $tematicaNombre = $variable->indicador->tematica->nombre;

            if (! isset($datosPorDimension[$dimension->id])) {
                $datosPorDimension[$dimension->id] = [
                    'nombre'    => $dimension->nombre,
                    'color'     => $dimension->color,
                    'slug'      => Str::slug($dimension->nombre),
                    'tematicas' => [],
                ];
            }

            $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = [
                'indicador_id'     => $variable->indicador->id,
                'indicador_nombre' => $variable->indicador->nombre_amigable,
                'nombre'           => $variable->nombre_amigable,
                'valor'            => $datoActual->valor ?? 'N/D',
                'anio'             => $datoActual->anio ?? 'N/D',
                'valor_display'    => $datoActual->valor_display ?? 'N/D',
                'unidad'           => $variable->unidad_medida,
                'solo_resumen'     => $variable->indicador->solo_resumen,
                'tipo_visual'      => $tipoVisual,
                'tendencia'        => $tendencia,
                'tendenciaClase'   => $tendenciaClase,
                'tendenciaIcono'   => $tendenciaIcono,
                'historial'        => json_encode($historial),
            ];
        }

        $this->injectInstrumentosFantasma($municipio, $datosPorDimension, true);
        $datosAgrupados = array_values($datosPorDimension);

        $wikiSummary = app(FichaComposerService::class)->getWikipediaSummary($municipio->nombre);

        return view('municipios.resumen_test', [
            'municipio'        => $municipio,
            'datosAgrupados'   => $datosAgrupados,
            'presupuestoTotal' => $presupuestoTotal,
            'anioPresupuesto'  => $anioPresupuesto,
            'poblacionTotal'   => $poblacionTotal,
            'gradoMarginacion' => $gradoMarginacion,
            'superficieKm2'    => $superficieKm2,
            'wikiSummary'      => $wikiSummary,
        ]);
    }

    /**
     * Muestra el directorio visual de municipios.
     * Carga los municipios y sus regiones para la cuadrícula interactiva.
     *
     * @return \Illuminate\View\View Vista del directorio municipal.
     */
    public function directorioVisual()
    {
        $macrorregiones = Macrorregion::with(['microrregiones' => function ($q) {
            $q->orderBy('nombre', 'asc');
        }])->orderBy('id', 'asc')->get();

        $municipios = Municipio::with('microrregion.macrorregion')
            ->orderBy('nombre', 'asc')
            ->get();

        return view('municipios.directorio', compact('macrorregiones', 'municipios'));
    }



    /**
     * Obtiene todos los años históricos con registros para una Dimensión específica.
     * Es útil para actualizar selectores de año basados en dimensiones.
     *
     * @param  \App\Models\Dimension  $dimension El modelo de Dimensión a consultar.
     * @return \Illuminate\Http\JsonResponse Lista ordenada descendentemente de años únicos.
     */
    public function getAniosPorDimension(Dimension $dimension)
    {
        abort_unless($dimension->visible_en_ficha, 404);

        return response()->json(
            app(IndicadorQueryService::class)->getAniosPorDimension($dimension)
        );
    }

    /**
     * Devuelve los años disponibles para un indicador complejo específico.
     * (Responde a la llamada AJAX del Paso 2)
     */
    public function getAniosPorIndicadorComplejo(Indicador $indicador)
    {
        if (!$indicador->es_complejo || !Indicador::visiblePublicamente()->whereKey($indicador->id)->exists()) {
            return response()->json(['error' => 'Indicador no válido'], 404);
        }

        return response()->json(
            app(IndicadorQueryService::class)->getAniosPorIndicadorComplejo($indicador)
        );
    }

    /**
     * Inicia la descarga de datos complejos filtrados por indicador y año.
     * (Responde a la ruta de descarga del Paso 2)
     */
    public function exportDatosComplejos(Indicador $indicador, $anio)
    {
        return app(ExportService::class)->exportDatosComplejos($indicador, $anio);
    }

    /**
     * Muestra la vista del perfil interactivo municipal.
     * Carga todos los estadísticos del Hero, genera la estructura del perfil
     * dinámico con base en la configuración activa de la ficha, e identifica
     * los municipios similares por macrorregión y población.
     *
     * @param  \App\Models\Municipio  $municipio El municipio a perfilar.
     * @return \Illuminate\View\View Vista del perfil interactivo.
     */
    public function perfilMunicipal(Municipio $municipio)
    {
        $municipio->load('microrregion.macrorregion');

        $hero = $this->getHeroStats($municipio);

        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->visibleEnPerfilPublico()
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $allVariableIds = FichaDataStore::extractVariableIds($configuraciones);
        $dataStore = new FichaDataStore($municipio, $allVariableIds);

        $perfil = [];
        $dimensionColors = [];

        foreach ($configuraciones as $config) {
            $datos = app(FichaComposerService::class)->obtenerDatosParaConfig($config, $municipio, $dataStore);

            $dimensionKey = $this->dimensionKey($config);
            $dimensionColors[$dimensionKey] ??= $config->indicador?->tematica?->dimension?->color ?: '#861e34';
            $perfil[$dimensionKey][] = [
                'config' => $config,
                'datos' => $datos,
                'narrativa' => FichaNarratorService::procesar($config->plantilla_narrativa, $municipio, $datos),
            ];
        }

        $layout = app(FichaLayoutService::class);
        foreach ($perfil as $dimension => $items) {
            $perfil[$dimension] = $layout->compose($items);
        }

        // Ordenar secciones del perfil por orden de dimensión
        $dimensionOrden = [];
        foreach ($configuraciones as $config) {
            $dim = $config->indicador?->tematica?->dimension;
            if ($dim && !isset($dimensionOrden[$dim->nombre])) {
                $dimensionOrden[$dim->nombre] = (int) ($dim->orden ?? 0);
            }
        }

        $general = $perfil['general'] ?? [];
        unset($perfil['general']);

        $clavesOrdenadas = [];
        foreach ($dimensionOrden as $nombre => $orden) {
            $key = str_replace(' ', '_', strtolower($nombre));
            if (isset($perfil[$key])) {
                $clavesOrdenadas[$key] = $perfil[$key];
                unset($perfil[$key]);
            }
        }
        foreach ($perfil as $key => $items) {
            $clavesOrdenadas[$key] = $items;
        }

        $perfil = ['general' => $general] + $clavesOrdenadas;

        return view('municipios.perfil', array_merge($hero, [
            'municipio' => $municipio,
            'perfil' => $perfil,
            'dimensionColors' => $dimensionColors,
            'similaresPoblacion' => app(RankingService::class)->getSimilaresPorPoblacion($municipio, $hero['poblacionTotal']),
            'similaresRegion' => app(RankingService::class)->getSimilaresPorRegion($municipio),
        ]));
    }

    public function panoramaMunicipal(Municipio $municipio)
    {
        return view('municipios.panorama_pdf', $this->panoramaData($municipio));
    }

    public function panoramaMap(Municipio $municipio, MunicipalityMapImageService $mapImageService)
    {
        return response($mapImageService->render((string) $municipio->cvegeo), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function exportarPanoramaPDF(Municipio $municipio, MunicipalityMapImageService $mapImageService)
    {
        $data = $this->panoramaData($municipio);
        $data['mapImageDataUri'] = 'data:image/png;base64,'.base64_encode(
            $mapImageService->render((string) $municipio->cvegeo)
        );
        $html = view('municipios.panorama_pdf', $data)->render();

        return $this->downloadCachedPdf(
            'panorama/'.$municipio->getKey(),
            $html,
            'panorama-'.str($municipio->nombre)->slug().'.pdf',
            fn () => app(ExportV3Service::class)->renderPanoramaPDF($html)
        );
    }

    private function downloadCachedPdf(string $cacheKey, string $html, string $fileName, \Closure $generator)
    {
        try {
            $pdf = app(CachedPdfService::class)->remember(
                $cacheKey,
                $html,
                $generator
            );
        } catch (LockTimeoutException) {
            return $this->temporaryUnavailableResponse(
                'El PDF se está generando. Intenta descargarlo nuevamente en unos segundos.',
                10
            );
        } catch (ProcessFailedException $exception) {
            report($exception);

            return $this->temporaryUnavailableResponse(
                'El servicio de PDF no está disponible temporalmente. Intenta nuevamente en unos segundos.',
                30
            );
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function temporaryUnavailableResponse(string $message, int $retryAfter)
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $message], 503, ['Retry-After' => $retryAfter]);
        }

        return response()->view('errors.503', ['message' => $message], 503, [
            'Retry-After' => $retryAfter,
        ]);
    }

    private function panoramaData(Municipio $municipio): array
    {
        $municipio->load('microrregion.macrorregion');
        $dimensiones = Dimension::with(['tematicas' => function ($query) {
            $query->where('visible_en_ficha', true)->orderBy('orden')->orderBy('nombre');
        }, 'tematicas.indicadores' => function ($query) {
            $query->where('visible_en_ficha', true)->orderBy('orden')->orderBy('nombre_amigable');
        }, 'tematicas.indicadores.variables' => function ($query) {
            $query->where('visible_en_ficha', true)->orderBy('orden')->orderBy('nombre_amigable');
        }])
            ->where('visible_en_ficha', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $variableIds = $dimensiones->pluck('tematicas')->flatten()
            ->pluck('indicadores')->flatten()->pluck('variables')->flatten()->pluck('id');
        $datosHistoricos = DatoHistorico::with(['variable', 'motivoSinDato'])
            ->where('municipio_id', $municipio->id)
            ->whereIn('variable_id', $variableIds)
            ->get()
            ->groupBy('variable_id');
        $perfil = [];
        $dimensionColors = $dimensiones->mapWithKeys(fn (Dimension $dimension) => [
            $dimension->nombre => $dimension->color ?: '#872341',
        ])->all();

        foreach ($dimensiones as $dimension) {
            foreach ($dimension->tematicas as $tematica) {
                foreach ($tematica->indicadores as $indicador) {
                    $variables = $indicador->variables;
                    $datos = null;

                    if ($indicador->es_complejo) {
                        $registro = DatoIndicadorComplejo::where('municipio_id', $municipio->id)
                            ->where('indicador_id', $indicador->id)->latest('anio')->first();
                        $datos = $registro ? ['anio' => $registro->anio, 'complejo' => $registro->datos] : null;
                    } elseif ($variables->isNotEmpty()) {
                        $aniosComunes = $variables->map(function ($variable) use ($datosHistoricos) {
                            return $datosHistoricos->get($variable->id, collect())->pluck('anio')->all();
                        })->reduce(fn ($carry, $anios) => $carry === null ? $anios : array_values(array_intersect($carry, $anios)));
                        $anio = $aniosComunes ? max($aniosComunes) : null;

                        if ($anio) {
                            $valores = $variables->map(function ($variable) use ($datosHistoricos, $anio) {
                                $dato = $datosHistoricos->get($variable->id, collect())->firstWhere('anio', $anio);
                                return [
                                    'nombre' => $variable->nombre_amigable,
                                    'valor' => $dato?->valor,
                                    'display' => $dato?->valor_display ?? 'N/D',
                                    'unidad' => $variable->unidad_medida,
                                ];
                            });
                            $datos = [
                                'anio' => $anio,
                                'type' => $indicador->tipo_grafico_default,
                                'categorias' => $valores->pluck('nombre')->all(),
                                'series' => [[
                                    'name' => $indicador->nombre_amigable,
                                    'data' => $valores->pluck('valor')->map(fn ($valor) => $valor === null ? 0 : (float) $valor)->all(),
                                ]],
                                'valores' => $valores->all(),
                            ];
                        }
                    }

                    $perfil[$dimension->nombre][$tematica->nombre][] = [
                        'indicador' => $indicador,
                        'datos' => $datos,
                    ];
                }
            }
        }

        return [
            'municipio' => $municipio,
            'hero' => $this->getHeroStats($municipio),
            'perfil' => $perfil,
            'dimensionColors' => $dimensionColors,
            'geojsonUrl' => asset('geojson/municipios_puebla_slim.geojson'),
            'cintillo' => base64_encode(file_get_contents(public_path('img/Cintillo SEI-07.png'))),
        ];
    }

    /**
     * Obtiene municipios similares ordenados por la distancia absoluta del valor de un indicador o variable.
     * Utilizado para comparar al municipio actual con su contexto de macrorregión.
     *
     * @param  \App\Models\Municipio  $municipio Municipio base para la comparación.
     * @param  int|string  $configKeyOrId ID de la configuración de la ficha o clave del Hero.
     * @return \Illuminate\Http\JsonResponse Datos formateados de municipios similares con su valor actual.
     */
    public function getSimilitudIndicador(Municipio $municipio, $configKeyOrId)
    {
        return response()->json(
            app(RankingService::class)->getSimilitud($municipio, $configKeyOrId)
        );
    }

    /**
     * Reemplaza los tokens de datos e información dinámica del municipio en el texto descriptivo.
     *
     * @param  string  $plantilla Texto de la plantilla con tokens.
     * @param  \App\Models\Municipio  $municipio Municipio del cual obtener contexto.
     * @param  array  $datos Datos del indicador asociados.
     * @return string Narrativa final en lenguaje natural procesada.
     */
    /**
     * Recupera y calcula los estadísticos esenciales (KPIs) del Hero para un municipio.
     * Delega el procesamiento al servicio FichaProfilerService.
     *
     * @param  \App\Models\Municipio  $municipio El municipio a evaluar.
     * @return array Conjunto de estadísticos clave estructurados.
     */
    private function getHeroStats(Municipio $municipio)
    {
        return FichaProfilerService::getHeroStats($municipio);
    }

    private function buildResumenV3Structure(Municipio $municipio): array
    {
        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->visibleEnPerfilPublico()
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $datosAgrupados = [];

        foreach ($configuraciones as $config) {
            $indicador = $config->indicador;
            $dimension = $indicador->tematica->dimension;
            $tematica = $indicador->tematica;

            $datos = app(FichaComposerService::class)->obtenerDatosParaConfig($config, $municipio);
            $variablePrincipal = $indicador->variables->first();
            $unidad = strtolower($variablePrincipal->unidad_medida ?? '');

            $tipoVisual = 'absoluto';
            if (str_contains($unidad, '%') || str_contains($unidad, 'porcentaje')) {
                $tipoVisual = 'porcentaje';
            } elseif (str_contains($unidad, '$') || str_contains($unidad, 'pesos')) {
                $tipoVisual = 'moneda';
            }

            $historial = [];
            if ($variablePrincipal) {
                $datosHist = DatoHistorico::where('variable_id', $variablePrincipal->id)
                    ->where('municipio_id', $municipio->id)
                    ->orderBy('anio', 'desc')
                    ->take(10)
                    ->get()
                    ->sortBy('anio');
                foreach ($datosHist as $dh) {
                    $historial[] = ['anio' => $dh->anio, 'valor' => $dh->valor];
                }
            }

            if (!isset($datosAgrupados[$dimension->id])) {
                $datosAgrupados[$dimension->id] = [
                    'nombre' => $dimension->nombre,
                    'slug' => Str::slug($dimension->nombre),
                    'color' => $dimension->color,
                    'tematicas' => [],
                ];
            }

            $datosAgrupados[$dimension->id]['tematicas'][$tematica->nombre][] = [
                'config' => $config,
                'indicador' => $indicador,
                'datos' => $datos,
                'narrativa' => FichaNarratorService::procesar($config->plantilla_narrativa, $municipio, $datos),
                'indicador_id' => $indicador->id,
                'indicador_nombre' => $indicador->nombre_amigable,
                'nombre' => $indicador->nombre_amigable,
                'valor' => $tipoVisual === 'scatter' ? null : (is_array($datos) ? ($datos['total'] ?? 0) : (is_numeric($datos) ? $datos : 0)),
                'valor_display' => $tipoVisual === 'scatter'
                    ? 'Relación entre variables'
                    : (is_array($datos) ? ($datos['valor_actual'] ?? (isset($datos['total']) ? number_format($datos['total']) : 'N/D')) : ($datos ?? 'N/D')),
                'anio' => is_array($datos) ? ($datos['anio'] ?? 'N/D') : 'N/D',
                'unidad' => $variablePrincipal->unidad_medida ?? '',
                'solo_resumen' => $indicador->solo_resumen,
                'tipo_visual' => ($config->tipo_visualizacion === 'lista') ? 'lista' : $tipoVisual,
                'historial' => json_encode($historial),
                'echarts' => is_array($datos) ? ($datos['echarts'] ?? null) : null,
                'correlacion' => is_array($datos) ? ($datos['correlacion'] ?? null) : null,
                'correlacion_lectura' => is_array($datos) ? ($datos['correlacion_lectura'] ?? null) : null,
                'variables' => is_array($datos) ? ($datos['variables'] ?? []) : [],
            ];
        }

        return $datosAgrupados;
    }

    private function dimensionKey($config): string
    {
        $dimension = $config->indicador->tematica->dimension->nombre ?? 'Sin Dimensión';
        return str_replace(' ', '_', strtolower($dimension));
    }

    private function buildKpisFromVariables(Municipio $municipio): array
    {
        $variablesKPI = Variable::with('indicador.tematica.dimension')
            ->where('es_kpi', true)
            ->get();

        $datosPorDimension = [];

        foreach ($variablesKPI as $variable) {
            $dato = DatoHistorico::where('variable_id', $variable->id)
                ->where('municipio_id', $municipio->id)
                ->orderBy('anio', 'desc')
                ->first();

            $dimension = $variable->indicador->tematica->dimension;
            $tematicaNombre = $variable->indicador->tematica->nombre;

            if (!isset($datosPorDimension[$dimension->id])) {
                $datosPorDimension[$dimension->id] = [
                    'nombre' => $dimension->nombre,
                    'color' => $dimension->color,
                    'slug' => Str::slug($dimension->nombre),
                    'tematicas' => [],
                ];
            }

            $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = [
                'indicador_id' => $variable->indicador->id,
                'nombre' => $variable->nombre_amigable,
                'valor' => $dato->valor ?? 'N/D',
                'anio' => $dato->anio ?? 'N/D',
                'valor_display' => $dato ? $dato->valor_display : 'N/D',
                'unidad' => $variable->unidad_medida,
                'solo_resumen' => $variable->indicador->solo_resumen,
            ];
        }

        return $datosPorDimension;
    }

    private function injectInstrumentosFantasma(Municipio $municipio, array &$datos, bool $extended = false): void
    {
        if ($municipio->instrumentos->isEmpty()) {
            return;
        }

        $dimension = Dimension::where('nombre', 'Geográfica y Medio Ambiente')->first();
        if (!$dimension) {
            return;
        }

        if (!isset($datos[$dimension->id])) {
            $datos[$dimension->id] = [
                'nombre' => $dimension->nombre,
                'color' => $dimension->color,
                'slug' => Str::slug($dimension->nombre),
                'tematicas' => [],
            ];
        }

        $kpiFantasma = [
            'indicador_id' => null,
            'nombre' => 'Tipo de instrumentos de planeación en materia territorial',
            'valor' => 'lista',
            'anio' => '',
            'valor_display' => $municipio->instrumentos,
            'unidad' => '',
            'solo_resumen' => true,
        ];

        if ($extended) {
            $kpiFantasma['indicador_nombre'] = 'Planeación';
            $kpiFantasma['tipo_visual'] = 'lista';
            $kpiFantasma['tendencia'] = null;
            $kpiFantasma['tendenciaClase'] = '';
            $kpiFantasma['tendenciaIcono'] = '';
            $kpiFantasma['historial'] = '[]';
        }

        $datos[$dimension->id]['tematicas']['Ordenamiento Territorial'][] = $kpiFantasma;
    }

    public function compararMunicipal(Request $request, $slug1, $slug2)
    {
        $profileToken = config('traffic.comparison_profile_token');
        $profiling = is_string($profileToken) && $profileToken !== ''
            && hash_equals($profileToken, (string) $request->header('X-Comparison-Profile'));
        $profile = [
            'slug_1' => $slug1,
            'slug_2' => $slug2,
            'query_count' => 0,
            'query_time_ms' => 0.0,
        ];
        $requestStartedAt = microtime(true);

        if ($profiling) {
            DB::listen(function (QueryExecuted $query) use (&$profile) {
                $profile['query_count']++;
                $profile['query_time_ms'] += $query->time;
            });
        }

        try {
            $cache = app(ComparisonPageCacheService::class);
            $versionStartedAt = microtime(true);
            $dataVersion = $cache->dataVersion();
            $profile['data_version_ms'] = round((microtime(true) - $versionStartedAt) * 1000, 1);
            $html = $cache->remember(
                $slug1,
                $slug2,
                $dataVersion,
                function () use ($slug1, $slug2, &$profile) {
                    return $this->renderComparacionMunicipal($slug1, $slug2, $profile);
                },
                function (string $status) use (&$profile) {
                    $profile['cache'] = $status;
                }
            );
            $profile['outcome'] = 'ok';
        } catch (LockTimeoutException) {
            $profile['outcome'] = 'lock_timeout';

            return $this->temporaryUnavailableResponse(
                'La comparativa se está preparando. Intenta nuevamente en unos segundos.',
                10
            );
        } finally {
            if ($profiling) {
                $profile['total_ms'] = round((microtime(true) - $requestStartedAt) * 1000, 1);
                $profile['query_time_ms'] = round($profile['query_time_ms'], 1);
                Log::info('comparison.profile', $profile);
            }
        }

        return response($html);
    }

    private function renderComparacionMunicipal(string $slug1, string $slug2, array &$profile = []): string
    {
        $rankingStartedAt = microtime(true);
        $data = app(RankingService::class)->cargarComparativaResumen($slug1, $slug2);
        $profile['ranking_ms'] = round((microtime(true) - $rankingStartedAt) * 1000, 1);

        $municipiosStartedAt = microtime(true);
        $data['todosMunicipios'] = Municipio::orderBy('nombre', 'asc')->get();
        $profile['municipios_ms'] = round((microtime(true) - $municipiosStartedAt) * 1000, 1);

        $dimensionColors = [];
        $dimensions = [];
        foreach ($data['configuraciones'] as $config) {
            $dimension = $config->indicador?->tematica?->dimension;
            if (!$dimension || isset($dimensions[$dimension->id])) {
                continue;
            }

            $key = $this->dimensionKey($config);
            $color = $dimension->color ?: '#861e34';
            $dimensionColors[$key] = $color;
            $dimensions[$dimension->id] = [
                'id' => $dimension->id,
                'key' => $key,
                'nombre' => $dimension->nombre,
                'color' => $color,
            ];
        }

        $profile['composition_ms'] = 0.0;
        $profile['configuration_count'] = count($data['configuraciones']);
        $viewStartedAt = microtime(true);
        $html = view('municipios.comparar', array_merge($data, [
            'todosMunicipios' => $data['todosMunicipios'],
            'dimensionColors' => $dimensionColors,
            'dimensions' => array_values($dimensions),
            'progressive' => true,
        ]))->render();
        $profile['view_ms'] = round((microtime(true) - $viewStartedAt) * 1000, 1);

        return $html;
    }

    /**
     * Renderiza una sola dimension cuando la persona llega a ella en pantalla.
     */
    public function compararDimension(Request $request, string $slug1, string $slug2, Dimension $dimension)
    {
        $profileToken = config('traffic.comparison_profile_token');
        $profiling = is_string($profileToken) && $profileToken !== ''
            && hash_equals($profileToken, (string) $request->header('X-Comparison-Profile'));
        $profile = [
            'slug_1' => $slug1,
            'slug_2' => $slug2,
            'dimension_id' => $dimension->getKey(),
            'query_count' => 0,
            'query_time_ms' => 0.0,
        ];
        $startedAt = microtime(true);

        if ($profiling) {
            DB::listen(function (QueryExecuted $query) use (&$profile) {
                $profile['query_count']++;
                $profile['query_time_ms'] += $query->time;
            });
        }

        try {
            $cache = app(ComparisonPageCacheService::class);
            $html = $cache->rememberDimension(
                $slug1,
                $slug2,
                $dimension->getKey(),
                $cache->dataVersion(),
                fn () => $this->renderComparacionDimension($slug1, $slug2, $dimension)
            );

            return response()->json(['html' => $html]);
        } catch (LockTimeoutException) {
            $profile['outcome'] = 'lock_timeout';

            return $this->temporaryUnavailableResponse(
                'Esta seccion se esta preparando. Intenta nuevamente en unos segundos.',
                10
            );
        } finally {
            if ($profiling) {
                $profile['outcome'] = $profile['outcome'] ?? 'ok';
                $profile['query_time_ms'] = round($profile['query_time_ms'], 1);
                $profile['total_ms'] = round((microtime(true) - $startedAt) * 1000, 1);
                Log::info('comparison.section.profile', $profile);
            }
        }
    }

    private function renderComparacionDimension(string $slug1, string $slug2, Dimension $dimension): string
    {
        $data = app(RankingService::class)->cargarComparativaDimension($slug1, $slug2, $dimension->getKey());
        $composer = app(FichaComposerService::class);

        foreach ($data['configuraciones'] as $config) {
            $datos1 = $composer->obtenerDatosParaConfig($config, $data['municipio1'], $data['dataStore1']);
            $datos2 = $composer->obtenerDatosParaConfig($config, $data['municipio2'], $data['dataStore2']);
            $key = $this->dimensionKey($config);
            $data['comparativa'][$key][] = [
                'config' => $config,
                'datos1' => $datos1,
                'datos2' => $datos2,
                'echarts_combinado' => $composer->combinarDatosParaECharts(
                    $config, $datos1, $datos2, $data['municipio1'], $data['municipio2']
                ),
            ];
        }

        return view('municipios.partials.comparar-dimension', array_merge($data, [
            'dimension' => $dimension,
            'dimensionKey' => str_replace(' ', '_', strtolower($dimension->nombre)),
            'dimensionAccent' => $dimension->color ?: '#861e34',
        ]))->render();
    }

    /**
     * Kept temporarily as the full-page rendering reference while individual
     * sections are served progressively.
     */
    private function renderComparacionMunicipalLegacy(string $slug1, string $slug2, array &$profile = []): string
    {
        $rankingStartedAt = microtime(true);
        $data = app(RankingService::class)->cargarComparativa($slug1, $slug2);
        $profile['ranking_ms'] = round((microtime(true) - $rankingStartedAt) * 1000, 1);

        $municipiosStartedAt = microtime(true);
        $data['todosMunicipios'] = Municipio::orderBy('nombre', 'asc')->get();
        $profile['municipios_ms'] = round((microtime(true) - $municipiosStartedAt) * 1000, 1);

        $composer = app(FichaComposerService::class);
        $compositionStartedAt = microtime(true);

        foreach ($data['configuraciones'] as $config) {
            $datos1 = $composer->obtenerDatosParaConfig($config, $data['municipio1'], $data['dataStore1']);
            $datos2 = $composer->obtenerDatosParaConfig($config, $data['municipio2'], $data['dataStore2']);

            $dimensionKey = $this->dimensionKey($config);
            $data['comparativa'][$dimensionKey][] = [
                'config' => $config,
                'datos1' => $datos1,
                'datos2' => $datos2,
                'echarts_combinado' => $composer->combinarDatosParaECharts(
                    $config, $datos1, $datos2, $data['municipio1'], $data['municipio2']
                ),
            ];
        }
        $profile['composition_ms'] = round((microtime(true) - $compositionStartedAt) * 1000, 1);
        $profile['configuration_count'] = count($data['configuraciones']);

        // Construir colores por dimensión para la vista de comparación
        $dimensionColors = [];
        foreach ($data['configuraciones'] as $config) {
            $dim = $config->indicador?->tematica?->dimension;
            if ($dim) {
                $dimKey = str_replace(' ', '_', strtolower($dim->nombre));
                if (!isset($dimensionColors[$dimKey])) {
                    $dimensionColors[$dimKey] = $dim->color ?: '#861e34';
                }
            }
        }

        $viewStartedAt = microtime(true);
        $html = view('municipios.comparar', array_merge($data, [
            'todosMunicipios' => $data['todosMunicipios'],
            'dimensionColors' => $dimensionColors,
        ]))->render();
        $profile['view_ms'] = round((microtime(true) - $viewStartedAt) * 1000, 1);

        return $html;
    }

    /**
     * Combina las estructuras de datos de dos municipios para generar una opción única de ECharts.
     * Esto permite graficar las comparaciones lado a lado en series combinadas de barras o líneas temporales.
     *
     * @param  \App\Models\ConfiguracionFicha  $config Configuración visual de la ficha.
     * @param  array|null  $datos1 Datos estructurados del primer municipio.
     * @param  array|null  $datos2 Datos estructurados del segundo municipio.
     * @param  \App\Models\Municipio  $municipio1 Primer municipio.
     * @param  \App\Models\Municipio  $municipio2 Segundo municipio.
     * @return array|null Estructura JSON para el gráfico combinado de ECharts, o null si no es combinable.
     */
    /**
     * Genera y exporta un documento PDF comparativo entre dos municipios.
     * Integra la información de Hero de ambos municipios, sus métricas clave,
     * y genera la estructura de secciones configuradas activas en la ficha.
     *
     * @param  string  $slug1 Slug del primer municipio.
     * @param  string  $slug2 Slug del segundo municipio.
     * @return \Illuminate\Http\Response Respuesta HTTP para descarga del archivo PDF.
     */
    public function exportarComparativaPDF($slug1, $slug2)
    {
        $data = app(RankingService::class)->cargarComparativa($slug1, $slug2);

        foreach ($data['configuraciones'] as $config) {
            $datos1 = app(FichaComposerService::class)->obtenerDatosParaConfig($config, $data['municipio1'], $data['dataStore1']);
            $datos2 = app(FichaComposerService::class)->obtenerDatosParaConfig($config, $data['municipio2'], $data['dataStore2']);

            $dimensionKey = $this->dimensionKey($config);
            $data['comparativa'][$dimensionKey][] = [
                'config' => $config,
                'datos1' => $datos1,
                'datos2' => $datos2,
            ];
        }

        return app(ExportService::class)->exportComparativaPDF(
            $data['municipio1'], $data['municipio2'],
            $data['hero1'], $data['hero2'],
            $data['configuraciones'],
            $data['comparativa']
        );
    }

    /**
     * Endpoint API para consultar y devolver dinámicamente los datos de un indicador y su narrativa
     * filtrados por un año específico para un municipio.
     *
     * @param  \App\Models\Municipio  $municipio El municipio a consultar.
     * @param  int  $configId ID de la configuración de ficha.
     * @param  int  $anio Año forzado de consulta.
     * @return \Illuminate\Http\JsonResponse Datos del indicador y narrativa estructurada.
     */
    public function getGraficoDatosApi(Municipio $municipio, $configId, $anio)
    {
        $config = ConfiguracionFicha::with(['indicador.variables', 'variables'])
            ->visibleEnPerfilPublico()
            ->find($configId);
        if (!$config) {
            return response()->json(['success' => false, 'error' => 'Configuración no encontrada'], 404);
        }

        // Obtener datos específicos forzando el año
        $datos = app(FichaComposerService::class)->obtenerDatosParaConfig($config, $municipio, null, (int)$anio);

        if (!$datos) {
            return response()->json(['success' => false, 'error' => 'No hay datos para el año especificado'], 404);
        }

        // Procesar la plantilla de narrativa descriptiva con los nuevos datos obtenidos
        $narrativa = FichaNarratorService::procesar($config->plantilla_narrativa, $municipio, $datos);

        return response()->json([
            'success' => true,
            'datos' => $datos,
            'narrativa' => $narrativa
        ]);
    }
}
