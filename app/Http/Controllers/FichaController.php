<?php

namespace App\Http\Controllers;

use App\Exports\IndicadorExport;
use App\Models\DatoHistorico;
use App\Models\DatoIndicadorComplejo;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Variable;
use App\Models\ConfiguracionFicha;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DatosComplejosExport;
use App\Services\FichaDataStore;
use App\Services\FichaProfilerService;
use App\Services\FichaNarratorService;

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
        $dimensiones = Dimension::with([
            'tematicas' => function ($q) {
                $q->orderBy('orden')->orderBy('nombre');
            },
            'tematicas.indicadores' => function ($query) {
                $query->where('solo_resumen', false)->orderBy('orden')->orderBy('nombre_amigable');
            },
            'tematicas.indicadores.variables' => function ($q) {
                $q->orderBy('orden')->orderBy('nombre_amigable');
            },
        ])->orderBy('orden')->orderBy('nombre')->get();

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
            'nivel_de_agregacion' => 'required|string|in:municipio,microrregion,macrorregion',
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
        $indicador = Indicador::with('variables')->find($validated['indicador_id']);
        $nivel     = $validated['nivel_de_agregacion'];
        $selection = $this->prepareGeographicSelection($nivel, $validated);

        if ($indicador->es_complejo) {
            // --- LÓGICA COMPLETA Y DIRECTA AQUÍ ---
            $chartData     = null;
            $selectedYears = $validated['anios'] ?? [];
            $selectionIds  = $selection['ids'];
            $nivel         = $validated['nivel_de_agregacion'];

            // --- CASO A: COMPARACIÓN DE DOS MUNICIPIOS ---
            if ($nivel === 'municipio' && count($selectionIds) === 2) {
                $nombresMunicipios = Municipio::whereIn('id', $selectionIds)->pluck('nombre', 'id');

                // Sub-caso A.1: Comparación de TENDENCIAS (múltiples años)
                if (count($selectedYears) > 1) {
                    $seriesFinales = [];
                    foreach ($selectionIds as $municipioId) {
                        $datos = DatoIndicadorComplejo::where('indicador_id', $indicador->id)
                            ->where('municipio_id', $municipioId)
                            ->whereIn('anio', $selectedYears)
                            ->orderBy('anio', 'asc')
                            ->get();

                        $dataPoints = $datos->map(function ($registro) {
                            // Sumamos todos los valores de los cultivos para obtener un total por año
                            $datosArray = is_array($registro->datos) ? $registro->datos : json_decode($registro->datos, true);
                            $totalAnual = array_sum($datosArray ?? []);
                            return [(int)$registro->anio, (float)$totalAnual];
                        });

                        $seriesFinales[] = ['name' => $nombresMunicipios[$municipioId], 'data' => $dataPoints];
                    }
                    $chartData = [
                        'titulo'           => "{$indicador->nombre_amigable} - Tendencia Comparativa",
                        'tipo_grafico'     => 'line',
                        'series'           => $seriesFinales,
                        'eje_x'            => ['type' => 'numeric', 'titulo' => 'Año'],
                        'available_years'  => $this->getAvailableYearsForComplex($indicador, $selectionIds, 2),
                        'selected_years'   => $selectedYears,
                        'nota_explicativa' => 'Nota: Para la comparación de tendencias, se muestra la suma total de todos los cultivos para cada municipio.',
                    ];
                }
                // Sub-caso A.2: Comparación en UN SOLO AÑO (Barras)
                else {
                    $availableYears = $this->getAvailableYearsForComplex($indicador, $selectionIds, 2);
                    $anio = !empty($selectedYears) ? $selectedYears[0] : $availableYears->first();

                    if (!$anio) {
                        $chartData = ['titulo' => "{$indicador->nombre_amigable} (Sin años en común para comparar)", 'series' => []];
                    } else {
                        $datosMunA = DatoIndicadorComplejo::where(['indicador_id' => $indicador->id, 'municipio_id' => $selectionIds[0], 'anio' => $anio])->first();
                        $datosMunB = DatoIndicadorComplejo::where(['indicador_id' => $indicador->id, 'municipio_id' => $selectionIds[1], 'anio' => $anio])->first();

                        $datosArrayA = $datosMunA ? (is_array($datosMunA->datos) ? $datosMunA->datos : json_decode($datosMunA->datos, true)) : [];
                        $datosArrayB = $datosMunB ? (is_array($datosMunB->datos) ? $datosMunB->datos : json_decode($datosMunB->datos, true)) : [];

                        $todosLosCultivos = array_unique(array_merge(array_keys($datosArrayA), array_keys($datosArrayB)));
                        sort($todosLosCultivos);

                        $serieA_valores = [];
                        $serieB_valores = [];
                        foreach ($todosLosCultivos as $cultivo) {
                            $serieA_valores[] = $datosArrayA[$cultivo] ?? 0;
                            $serieB_valores[] = $datosArrayB[$cultivo] ?? 0;
                        }

                        $chartData = [
                            'titulo' => "{$indicador->nombre_amigable} - Comparación (Año: {$anio})",
                            'tipo_grafico' => 'bar',
                            'series' => [['name' => $nombresMunicipios[$selectionIds[0]], 'data' => $serieA_valores], ['name' => $nombresMunicipios[$selectionIds[1]], 'data' => $serieB_valores]],
                            'eje_x' => ['categorias' => $todosLosCultivos],
                            'available_years' => $availableYears,
                            'selected_years'  => [$anio],
                        ];
                    }
                }
            }
            // --- CASO B: VISTA ÚNICA (MUNICIPIO, REGIÓN O ESTATAL) ---
            else {
                $availableYears = $this->getAvailableYearsForComplex($indicador, $selectionIds);
                $yearsToUse     = !empty($selectedYears) ? $selectedYears : $availableYears->all();

                if (empty($yearsToUse)) {
                    $chartData = ['titulo' => "{$indicador->nombre_amigable} - {$selection['titulo']} (Sin Datos)", 'series' => []];
                } else {
                    if (count($yearsToUse) > 1) { // Gráfico de Líneas
                        $query = DatoIndicadorComplejo::where('indicador_id', $indicador->id)->whereIn('anio', $yearsToUse);
                        if (!in_array('estatal', $selectionIds)) $query->whereIn('municipio_id', $selectionIds);
                        $datosMultiAnio = $query->orderBy('anio', 'asc')->get();

                        $seriesData = [];
                        foreach ($datosMultiAnio as $registro) {
                            $datosArray = is_array($registro->datos) ? $registro->datos : json_decode($registro->datos, true);
                            if (!is_array($datosArray)) continue;
                            $anioActual = (int)$registro->anio;
                            foreach ($datosArray as $cultivo => $valor) {
                                if (!isset($seriesData[$cultivo])) $seriesData[$cultivo] = [];
                                if (!isset($seriesData[$cultivo][$anioActual])) $seriesData[$cultivo][$anioActual] = 0;
                                $seriesData[$cultivo][$anioActual] += (float)$valor;
                            }
                        }

                        $seriesFinales = [];
                        foreach ($seriesData as $cultivo => $datosAnuales) {
                            $dataPoints = [];
                            ksort($datosAnuales);
                            foreach ($datosAnuales as $anio => $valorTotal) $dataPoints[] = [$anio, $valorTotal];
                            $seriesFinales[] = ['name' => $cultivo, 'data' => $dataPoints];
                        }
                        $chartData = ['titulo' => "{$indicador->nombre_amigable} - {$selection['titulo']} (Histórico)", 'tipo_grafico' => 'line', 'series' => $seriesFinales, 'eje_x' => ['type' => 'numeric', 'titulo' => 'Año']];
                    } else { // Gráfico de Barras
                        $anio = $yearsToUse[0];
                        $queryDatos = DatoIndicadorComplejo::where('indicador_id', $indicador->id)->where('anio', $anio);
                        if (!in_array('estatal', $selectionIds)) $queryDatos->whereIn('municipio_id', $selectionIds);
                        $datosComplejos = $queryDatos->get();

                        $datosAgregados = [];
                        foreach ($datosComplejos as $registro) {
                            $datosArray = is_array($registro->datos) ? $registro->datos : json_decode($registro->datos, true);
                            if (!is_array($datosArray)) continue;
                            foreach ($datosArray as $cultivo => $valor) {
                                if (!isset($datosAgregados[$cultivo])) $datosAgregados[$cultivo] = 0;
                                $datosAgregados[$cultivo] += $valor;
                            }
                        }
                        arsort($datosAgregados);
                        $chartData = ['titulo' => "{$indicador->nombre_amigable} - {$selection['titulo']} (Año: {$anio})", 'tipo_grafico' => 'bar', 'series' => [['name' => 'Producción', 'data' => array_values($datosAgregados)]], 'eje_x' => ['categorias' => array_keys($datosAgregados)]];
                    }
                }

                if (!empty($selection['nombres_municipios'])) {
                    $chartData['municipios_incluidos'] = $selection['nombres_municipios'];
                }

                $chartData['available_years'] = $availableYears;
                $chartData['selected_years']  = $yearsToUse;
            }

            // Añadimos metadatos comunes a CUALQUIER respuesta compleja
            $chartData['eje_y']          = ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'];
            $chartData['descripcion']    = $indicador->descripcion;
            $chartData['fuente']         = $indicador->fuente;
            $chartData['metodo_calculo'] = $indicador->metodo_calculo;
        }
        // --- FIN DE LA CORRECCIÓN ---
        elseif (
            $indicador->id == 2 &&
            (($nivel === 'municipio' && count($validated['municipio_ids'] ?? []) === 1) || in_array($nivel, ['microrregion', 'macrorregion']))
        ) {
            $chartData = $this->handlePiramideChart($indicador, $selection);
        } elseif ($nivel === 'municipio' && count($selection['ids']) > 1) {
            $chartData = $this->handleComparativeView($validated, $indicador, $selection);
        } else {
            $chartData = $this->handleAggregatedView($nivel, $validated, $indicador, $selection);
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
        // Reutilizamos las mismas reglas de validación que getData
        $validated = $request->validate([
            'indicador_id'        => 'required|integer|exists:indicadors,id',
            'nivel_de_agregacion' => 'required|string|in:municipio,microrregion,macrorregion',
            'municipio_ids'       => 'nullable|array',
            'municipio_ids.*'     => 'string',
            'region_id'           => 'nullable|integer',
            'anios'               => 'nullable|array',
            'anios.*'             => 'integer',
        ]);

        // 1. Reutilizamos nuestra lógica central para obtener los datos ya procesados
        $chartData = $this->getChartData($validated);
        $export    = new IndicadorExport($chartData); // <-- Mucho más limpio
        $fileName  = Str::slug($chartData['titulo'] ?? 'export-datos') . '.csv';

        return Excel::download($export, $fileName);
    }

    /**
     * Prepares the geographic selection by determining the list of Municipio IDs
     * and the corresponding title based on the aggregation level and input parameters.
     *
     * @param  string  $nivel  The level of aggregation ('municipio', 'microrregion', or 'macrorregion').
     * @param  array  $validated  The validated input request parameters.
     * @return array{ids: array<int|string>, titulo: string}
     */
    private function prepareGeographicSelection(string $nivel, array $validated): array
    {
        $municipioIds = [];
        $titulo       = '';
        $nombresMunicipios = [];

        if ($nivel === 'municipio') {
            $municipioIds = $validated['municipio_ids'] ?? [];
            if (count($municipioIds) === 1 && $municipioIds[0] !== 'estatal') {
                $titulo = Municipio::find($municipioIds[0])->nombre;
            } elseif (in_array('estatal', $municipioIds)) {
                $titulo = 'Total Estatal';
            }
        } else {
            if ($validated['region_id']) {
                if ($nivel === 'microrregion') {
                    $region = Microrregion::with('municipios')->find($validated['region_id']);
                    if ($region) {
                        $titulo       = $region->nombre;
                        $municipioIds = $region->municipios->pluck('id')->all();
                        $nombresMunicipios = $region->municipios->sortBy('nombre')->pluck('nombre')->all();
                    }
                } elseif ($nivel === 'macrorregion') {
                    $region = Macrorregion::with('microrregiones.municipios')->find($validated['region_id']);
                    if ($region) {
                        $titulo       = $region->nombre;
                        $municipios   = $region->microrregiones->flatMap(fn($micro) => $micro->municipios);
                        $municipioIds = $municipios->pluck('id')->all();
                        $nombresMunicipios = $municipios->sortBy('nombre')->pluck('nombre')->unique()->all();
                    }
                }
            }
        }

        return ['ids' => $municipioIds, 'titulo' => $titulo, 'nombres_municipios' => $nombresMunicipios];
    }

    /**
     * Processes data for an Indicator that requires a population pyramid chart (Indicador ID 2)
     * based on the provided geographic selection.
     *
     * @param  \App\Models\Indicador  $indicador // The Indicador model instance (assumed to be the population indicator).
     * @param  array{ids: array<int|string>, titulo: string}  $selection // The prepared geographic selection (Municipio IDs and title).
     * @return array
     */
    private function handlePiramideChart(Indicador $indicador, array $selection, array $variableIds = null)
    {
        // Obtenemos la lista de IDs y el título desde el arreglo $selection
        $municipioIds    = $selection['ids'];
        $tituloSeleccion = $selection['titulo'];
        $anioConsulta    = null;

        $mapaPiramide = [
            '100 o más años' => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_100_anos_y_mas', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_100_anos_y_mas'],
            '95 a 99 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_95_a_99_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_95_a_99_anos'],
            '90 a 94 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_90_a_94_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_90_a_94_anos'],
            '85 a 89 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_85_a_89_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_85_a_89_anos'],
            '80 a 84 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_80_a_84_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_80_a_84_anos'],
            '75 a 79 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_75_a_79_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_75_a_79_anos'],
            '70 a 74 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_70_a_74_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_70_a_74_anos'],
            '65 a 69 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_65_a_69_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_65_a_69_anos'],
            '60 a 64 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_60_a_64_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_60_a_64_anos'],
            '55 a 59 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_55_a_59_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_55_a_59_anos'],
            '50 a 54 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_50_a_54_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_50_a_54_anos'],
            '45 a 49 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_45_a_49_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_45_a_49_anos'],
            '40 a 44 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_40_a_44_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_40_a_44_anos'],
            '35 a 39 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_35_a_39_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_35_a_39_anos'],
            '30 a 34 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_30_a_34_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_30_a_34_anos'],
            '25 a 29 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_25_a_29_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_25_a_29_anos'],
            '20 a 24 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_20_a_24_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_20_a_24_anos'],
            '15 a 19 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_15_a_19_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_15_a_19_anos'],
            '10 a 14 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_10_a_14_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_10_a_14_anos'],
            '5 a 9 años'     => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_5_a_9_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_5_a_9_anos'],
            '0 a 4 años'     => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_0_a_4_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_0_a_4_anos'],
            'No especifico'  => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_edad_no_especificada', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_edad_no_especificada'],
        ];

        $nombresTecnicos = collect($mapaPiramide)->flatten()->unique()->all();
        $variables       = Variable::whereIn('nombre_tecnico', $nombresTecnicos)->get()->keyBy('nombre_tecnico');
        $hombresData     = [];
        $mujeresData     = [];
        $categorias      = array_keys($mapaPiramide);

        // 1. Encontramos el año más reciente disponible PARA LA SELECCIÓN ACTUAL.
        $idsToQuery = $variableIds ?: $indicador->variables->pluck('id')->toArray();

        $queryAnio = DatoHistorico::whereIn('variable_id', $idsToQuery);
        if (!in_array('estatal', $municipioIds)) {
            $queryAnio->whereIn('municipio_id', $municipioIds);
        }
        $anioConsulta = $queryAnio->max('anio');

        // 2. Buscamos TODOS los años disponibles para esta selección (para el selector)
        $availableYearsQuery = DatoHistorico::whereIn('variable_id', $idsToQuery);
        if (!in_array('estatal', $municipioIds)) {
            $availableYearsQuery->whereIn('municipio_id', $municipioIds);
        }
        $availableYears = $availableYearsQuery->distinct()->orderBy('anio', 'desc')->pluck('anio');

        if (!$anioConsulta) {
            return [
                'titulo'         => "Sin datos de población para {$tituloSeleccion}",
                'descripcion'    => $indicador->descripcion,
                'fuente'         => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
                'tipo_grafico'   => 'piramide',
                'series'         => [['name' => 'Hombres', 'data' => []], ['name' => 'Mujeres', 'data' => []]],
                'eje_x'          => ['categorias' => $categorias],
                'eje_y'          => ['titulo' => 'Habitantes'],
                'available_years' => $availableYears, // <-- AÑADIDO
                'selected_years'  => [],           // <-- AÑADIDO
            ];
        }

        // 3. Iteramos y consultamos los datos usando ESE AÑO específico.
        $varIds = collect();
        foreach ($mapaPiramide as $grupo) {
            $varHom = $variables->get($grupo['hom']);
            $varMuj = $variables->get($grupo['muj']);
            if ($varHom) $varIds->push($varHom->id);
            if ($varMuj) $varIds->push($varMuj->id);
        }

        $queryDatos = DatoHistorico::whereIn('variable_id', $varIds)->where('anio', $anioConsulta);
        if (!in_array('estatal', $municipioIds)) {
            $queryDatos->whereIn('municipio_id', $municipioIds);
        }
        $datosAgregados = $queryDatos->get()->groupBy('variable_id');

        foreach ($mapaPiramide as $grupo) {
            // Hombres
            $varHom = $variables->get($grupo['hom']);
            if ($varHom && (!$variableIds || in_array($varHom->id, $variableIds))) {
                $hombresData[] = -$datosAgregados->get($varHom->id, collect())->sum('valor');
            } else {
                $hombresData[] = 0;
            }

            // Mujeres
            $varMuj = $variables->get($grupo['muj']);
            if ($varMuj && (!$variableIds || in_array($varMuj->id, $variableIds))) {
                $mujeresData[] = (float) $datosAgregados->get($varMuj->id, collect())->sum('valor');
            } else {
                $mujeresData[] = 0;
            }
        }

        $responseData = [
            'titulo'         => $indicador->nombre_amigable . " - " . $tituloSeleccion . " (" . ($anioConsulta ?: 'N/D') . ")",
            'descripcion'    => $indicador->descripcion,
            'fuente'         => $indicador->fuente,
            'metodo_calculo' => $indicador->metodo_calculo,
            'tipo_grafico'   => 'piramide',
            'series'         => [['name' => 'Hombres', 'data' => $hombresData], ['name' => 'Mujeres', 'data' => $mujeresData]],
            'eje_x'          => ['categorias' => $categorias],
            'eje_y'          => ['titulo' => 'Habitantes'],
            'available_years' => $availableYears,
            'selected_years'  => [$anioConsulta],
            'anio'           => $anioConsulta,
            'polaridad'      => $indicador->polaridad,
        ];

        if (!empty($selection['nombres_municipios'])) {
            $responseData['municipios_incluidos'] = $selection['nombres_municipios'];
        }

        return $responseData;
    }

    /**
     * Processes data for an Indicator when multiple Municipalities are selected,
     * generating either a Line Chart (for multi-year comparison) or a Bar Chart (for single-year comparison).
     *
     * @param  array  $validated  The validated input parameters.
     * @param  \App\Models\Indicador  $indicador // The Indicador model instance.
     * @param  array{ids: array<int|string>, titulo: string}  $selection // The prepared geographic selection (Municipio IDs and title).
     * @return array
     */
    private function handleComparativeView(array $validated, Indicador $indicador, array $selection)
    {
        $municipioIds      = $selection['ids'];
        $nombresArray      = Municipio::whereIn('id', $municipioIds)->pluck('nombre', 'id');
        $nombresMunicipios = collect($municipioIds)->map(fn($id) => $nombresArray[$id] ?? 'N/A')->all();
        $variableIds       = $indicador->variables->pluck('id');
        $selectedYears     = $validated['anios'] ?? [];

        // Sub-caso: Comparación en MÚLTIPLES años -> Gráfico de Líneas
        if (count($selectedYears) > 1) {
            $variablePrincipal = $indicador->variables->first(function ($variable) {
                return str_contains(strtolower($variable->nombre_amigable), 'total');
            });
            if (! $variablePrincipal) {
                $variablePrincipal = $indicador->variables->first();
            }
            $variableIdToUse = $variablePrincipal->id;

            $yearsToUse = $selectedYears;
            sort($yearsToUse);

            $seriesParaGrafico = [];
            foreach ($municipioIds as $munId) {
                $dataPoints = [];
                foreach ($yearsToUse as $year) {
                    $valor = DatoHistorico::where('variable_id', $variableIdToUse)
                        ->where('municipio_id', $munId)->where('anio', $year)->value('valor');
                    $dataPoints[] = $valor !== null ? (float) $valor : 0;
                }

                $seriesParaGrafico[] = ['name' => $nombresArray[$munId] ?? 'N/A', 'data' => $dataPoints];
            }

            $availableYears = DatoHistorico::whereIn('variable_id', $variableIds)->whereIn('municipio_id', $municipioIds)
                ->select('anio')->groupBy('anio')->havingRaw('COUNT(DISTINCT municipio_id) >= ?', [count($municipioIds)])
                ->orderBy('anio', 'desc')->pluck('anio');

            // IMPORTANTE: Este es el array que se devuelve
            return [
                'titulo'           => $indicador->nombre_amigable . " (" . $variablePrincipal->nombre_amigable . " - Tendencia Comparativa)",
                'tipo_grafico'     => 'line',
                'series'           => $seriesParaGrafico,
                'available_years'  => $availableYears,
                'selected_years'   => $yearsToUse,
                'eje_x'            => ['type' => 'category', 'categorias' => $yearsToUse, 'titulo' => 'Año'], // <-- CLAVE PRESENTE
                'eje_y'            => ['titulo' => $variablePrincipal->unidad_medida ?? 'Valor'],
                'descripcion'      => $indicador->descripcion,
                'metodo_calculo'   => $indicador->metodo_calculo,
                'fuente'           => $indicador->fuente,
                'nota_explicativa' => 'Nota: Para la comparación de tendencias entre municipios, se utiliza la variable principal del indicador (' . $variablePrincipal->nombre_amigable . ').',
            ];
        }

        // Sub-caso: Comparación en UN SOLO año -> Gráfico de Barras
        else {
            $availableYears = DatoHistorico::whereIn('variable_id', $variableIds)->whereIn('municipio_id', $municipioIds)
                ->select('anio')->groupBy('anio')->havingRaw('COUNT(DISTINCT municipio_id) = ?', [count($municipioIds)])
                ->orderBy('anio', 'desc')->pluck('anio');

            $yearToQuery       = ! empty($selectedYears) ? $selectedYears[0] : $availableYears->first();
            $seriesParaGrafico = [];

            if ($yearToQuery) {
                foreach ($indicador->variables->sortBy(['orden', 'nombre_amigable']) as $variable) {
                    $valores = [];
                    foreach ($municipioIds as $munId) {
                        $dato      = DatoHistorico::where('variable_id', $variable->id)->where('municipio_id', $munId)->where('anio', $yearToQuery)->first();
                        $valores[] = $dato ? (float) $dato->valor : 0;
                    }
                    $seriesParaGrafico[] = ['name' => $variable->nombre_amigable, 'data' => $valores];
                }
            }

            // IMPORTANTE: Este es el array que se devuelve
            return [
                'titulo'          => $indicador->nombre_amigable . ($yearToQuery ? " (Comparación Año: $yearToQuery)" : " (Sin años en común para comparar)"),
                'tipo_grafico'    => 'bar',
                'series'          => $seriesParaGrafico,
                'available_years' => $availableYears,
                'selected_years'  => $yearToQuery ? [$yearToQuery] : [],

                'eje_x'           => ['categorias' => $nombresMunicipios],
                'eje_y'           => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
                'descripcion'     => $indicador->descripcion,
                'metodo_calculo'  => $indicador->metodo_calculo,
                'fuente'          => $indicador->fuente,
            ];
        }
    }

    /**
     * Processes data for an Indicator when a single geographic area (Municipio, Microrregión, Macrorregión, or Estatal total)
     * is selected, generating either a Line Chart (multi-year) or a Bar Chart (single-year/variable comparison).
     *
     * @param  string  $nivel  The level of aggregation ('municipio', 'microrregion', or 'macrorregion').
     * @param  array  $validated  The validated input parameters.
     * @param  \App\Models\Indicador  $indicador // The Indicador model instance.
     * @param  array{ids: array<int|string>, titulo: string}  $selection // The prepared geographic selection (Municipio IDs and title).
     * @return array|\Illuminate\Http\JsonResponse
     */
    private function handleAggregatedView(string $nivel, array $validated, Indicador $indicador, array $selection)
    {

        $selectedYears = $validated['anios'] ?? [];

        if ($indicador->id == 1 && ($nivel !== 'municipio' || in_array('estatal', $selection['ids'])) && count($selectedYears) <= 1) {

            $anio = null;
            if (empty($selectedYears)) {
                $anio = DatoHistorico::whereIn('variable_id', $indicador->variables->pluck('id'))->max('anio');
            } else {
                $anio = $selectedYears[0];
            }

            if (! $anio) {
                return ['series' => [], 'titulo' => 'No hay datos disponibles para este indicador'];
            }

            $categorias = [];
            $valores    = [];

            foreach ($indicador->variables->sortBy(['orden', 'nombre_amigable']) as $variable) {
                $categorias[] = $variable->nombre_amigable;
                $query        = DatoHistorico::where('variable_id', $variable->id)->where('anio', $anio);
                if (! in_array('estatal', $selection['ids'])) {
                    $query->whereIn('municipio_id', $selection['ids']);
                }
                $valor     = $query->sum('valor');
                $valores[] = (float) $valor;
            }

            $tituloSeleccion = $selection['titulo'];
            $availableYears  = DatoHistorico::whereIn('variable_id', $indicador->variables->pluck('id'))
                ->distinct()->orderBy('anio', 'desc')->pluck('anio');

            // 1. Preparamos el array de respuesta para el gráfico
            $responseData = [
                'titulo' => "{$indicador->nombre_amigable} - {$tituloSeleccion} (Año: {$anio})",
                'descripcion' => $indicador->descripcion,
                'fuente' => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
                'tipo_grafico' => 'bar',
                'eje_x' => ['categorias' => $categorias],
                'series' => [['name' => 'Población', 'data' => $valores]],
                'eje_y' => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
                'available_years' => $availableYears,
                'selected_years' => [$anio],
            ];

            // --- INICIO DE LA CORRECCIÓN ---
            // 2. Añadimos los datos del mapa si la vista es estatal
            if (in_array('estatal', $selection['ids'])) {
                $responseData['mapData'] = $this->getMapData($indicador, $anio)->original;
            }
            // --- FIN DE LA CORRECCIÓN ---

            // 3. Retornamos la respuesta completa
            return $responseData;
        }

        if (empty($selection['ids']) && ! in_array('estatal', $validated['municipio_ids'] ?? [])) {
            return response()->json([
                'series' => [],
                'titulo' => 'Selecciona una ubicación para consultar ' . $indicador->nombre_amigable,
                'descripcion' => $indicador->descripcion,
                'fuente' => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
                'available_years' => []
            ]);
        }

        $selectedYears   = $validated['anios'] ?? [];
        $seriesCompletas = [];
        $tituloSeleccion = $selection['titulo'];
        $notasExplicativas = [];

        $variablesParaProcesar = $indicador->variables;
        if (
            $indicador->priorizar_total &&
            ($nivel !== 'municipio' || in_array('estatal', $selection['ids']))
        ) {
            $variableTotal = $indicador->variables->first(function ($variable) {
                return str_contains(mb_strtolower($variable->nombre_amigable, 'UTF-8'), 'total');
            });

            if ($variableTotal) {
                // Este bloque ahora solo se ejecutará para indicadores como "Población".
                $variablesParaProcesar = collect([$variableTotal]);
            }
        }


        foreach ($variablesParaProcesar->sortBy(['orden', 'nombre_amigable']) as $variable) {
            $query = DatoHistorico::where('variable_id', $variable->id);

            if ($nivel === 'municipio' && ! in_array('estatal', $selection['ids'])) {
                $query->where('municipio_id', $selection['ids'][0]);

                // 1. Cargamos la relación del motivo
                $datosHistoricos = $query->with('motivoSinDato')->orderBy('anio', 'asc')->get();
                $dataPoints = [];
                foreach ($datosHistoricos as $dato) {
                    // Valor numérico (o null)
                    $valor = $dato->valor !== null ? (float) $dato->valor : null;
                    $dataPoints[] = [(int) $dato->anio, $valor];

                    // 2. Si es nulo y tiene motivo, guardamos la nota
                    if ($dato->valor === null && $dato->motivo_sin_dato_id) {
                        // Guardamos: [2022 => "Información Confidencial"]
                        // Usamos el nombre del motivo si existe, si no 'Sin Dato'
                        $razon = $dato->motivoSinDato->nombre ?? 'Sin información';
                        $notasExplicativas[$dato->anio] = $razon;
                    }
                }
            } else {
                if (! in_array('estatal', $selection['ids'])) {
                    $query->whereIn('municipio_id', $selection['ids']);
                }
                $datosHistoricos = $query->selectRaw('anio, SUM(valor) as valor')
                    ->groupBy('anio')
                    ->orderBy('anio', 'asc')
                    ->get();
                $dataPoints        = $datosHistoricos->map(fn($dato) => [(int) $dato->anio, (float) $dato->valor]);
            }

            $seriesCompletas[] = ['name' => $variable->nombre_amigable, 'data' => $dataPoints];
        }

        $availableYears   = collect($seriesCompletas)->flatMap(fn($serie) => collect($serie['data'])->pluck(0))->unique()->sortDesc()->values();
        $tipoGraficoFinal = 'line';
        $yearsToUse       = [];
        $chartTitleYear   = '';

        if (count($selectedYears) > 1) {
            $tipoGraficoFinal = 'line';
            $yearsToUse       = $selectedYears;
            $chartTitleYear   = 'Tendencia Años Seleccionados';
        } elseif (count($selectedYears) === 1) {
            $tipoGraficoFinal = 'bar';
            $yearsToUse       = $selectedYears;
            $chartTitleYear   = 'Año: ' . $yearsToUse[0];
        } else {
            if (strtolower(trim($indicador->tipo_grafico_default)) === 'barras') {
                $tipoGraficoFinal = 'bar';
                if ($availableYears->isNotEmpty()) {
                    $yearsToUse     = [$availableYears->first()];
                    $chartTitleYear = 'Año: ' . $yearsToUse[0];
                }
            } else {
                $tipoGraficoFinal = 'line';
                $yearsToUse       = $availableYears->all();
                $chartTitleYear   = 'Histórico';
            }
        }

        $seriesFinales = [];
        $ejeXFinal     = [];

        if ($tipoGraficoFinal === 'line') {
            $yearsToCompare = array_map('strval', $yearsToUse);
            $seriesFinales  = collect($seriesCompletas)->map(function ($serie) use ($yearsToCompare) {
                $filteredData = collect($serie['data'])->filter(fn($point) => in_array((string) $point[0], $yearsToCompare, true))->values();
                return ['name' => $serie['name'], 'data' => $filteredData];
            })->all();
            $ejeXFinal = ['type' => 'numeric', 'titulo' => 'Año'];
        } elseif ($tipoGraficoFinal === 'bar' && ! empty($yearsToUse)) {
            $anioAFiltrar    = $yearsToUse[0];
            $seriesFiltradas = collect($seriesCompletas)->map(function ($serie) use ($anioAFiltrar) {
                $datoDelAnio = collect($serie['data'])->firstWhere(0, (int) $anioAFiltrar);
                return $datoDelAnio[1] ?? 0;
            });
            $ejeXFinal['categorias'] = collect($seriesCompletas)->pluck('name')->all();
            $seriesFinales           = [['name' => 'Valor', 'data' => $seriesFiltradas->all()]];
        }

        $responseData = [
            'titulo'          => $indicador->nombre_amigable . " - " . $tituloSeleccion . " ($chartTitleYear)",
            'descripcion'     => $indicador->descripcion,
            'fuente'          => $indicador->fuente,
            'metodo_calculo'  => $indicador->metodo_calculo,
            'tipo_grafico'    => $tipoGraficoFinal,
            'series'          => $seriesFinales,
            'eje_x'           => $ejeXFinal,
            'eje_y'           => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
            'available_years' => $availableYears,
            'selected_years'  => $yearsToUse,
            'notas_explicativas' => $notasExplicativas,
        ];
        if (in_array('estatal', $selection['ids'])) {
            // Determinamos el año que se usará para el mapa (el seleccionado o el más reciente).
            $anioParaMapa = ! empty($yearsToUse) ? $yearsToUse[0] : $availableYears->first();

            if ($anioParaMapa) {
                // Reutilizamos la lógica de getMapData para no duplicar código y la adjuntamos.
                // .original obtiene el array de datos de la respuesta JSON.
                $responseData['mapData'] = $this->getMapData($indicador, $anioParaMapa)->original;
            }
        }

        if (!empty($selection['nombres_municipios'])) {
            $responseData['municipios_incluidos'] = $selection['nombres_municipios'];
        }
        return $responseData;
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
        // --- INICIO: LÓGICA DE DATOS (IDÉNTICA A resumenMunicipal) ---

        $variablesKPI = Variable::with('indicador.tematica.dimension')
            ->whereNotIn('id', [200]) // Excluir variables de Instrumentos, puesto que se manejan aparte
            ->where('es_kpi', true)
            ->get();

        // 1. Usamos $datosPorDimension (array asociativo por ID)
        $datosPorDimension = [];

        foreach ($variablesKPI as $variable) {
            $dato = DatoHistorico::where('variable_id', $variable->id)
                ->where('municipio_id', $municipio->id)
                ->orderBy('anio', 'desc')
                ->first();

            // 2. Agrupamos por el objeto Dimensión y su ID
            $dimension = $variable->indicador->tematica->dimension;
            $tematicaNombre = $variable->indicador->tematica->nombre;

            // 3. Creamos la estructura completa de la dimensión
            if (!isset($datosPorDimension[$dimension->id])) {
                $datosPorDimension[$dimension->id] = [
                    'nombre'    => $dimension->nombre,
                    'color'     => $dimension->color,
                    'slug'      => Str::slug($dimension->nombre),
                    'tematicas' => [],
                ];
            }

            // 4. Guardamos TODOS los campos que la vista pueda necesitar
            $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = [
                'indicador_id'  => $variable->indicador->id,
                'nombre'        => $variable->nombre_amigable,
                'valor'         => $dato->valor ?? 'N/D', // Pasamos 'valor'
                'anio'          => $dato->anio ?? 'N/D',
                'valor_display' => $dato ? $dato->valor_display : 'N/D',
                'unidad'        => $variable->unidad_medida,
                'solo_resumen'  => $variable->indicador->solo_resumen, // Pasamos 'solo_resumen'
            ];
        }

        // 5. AÑADIMOS EL BLOQUE DEL "KPI FANTASMA" (INSTRUMENTOS)
        if ($municipio->instrumentos->isNotEmpty()) {
            $dimensionNombre = 'Geográfica y Medio Ambiente';
            $tematicaNombre = 'Ordenamiento Territorial';
            $indicadorNombre = 'Tipo de instrumentos de planeación en materia territorial';

            $dimension = Dimension::where('nombre', $dimensionNombre)->first();

            if ($dimension) {
                // Si la dimensión no existe, la creamos
                if (!isset($datosPorDimension[$dimension->id])) {
                    $datosPorDimension[$dimension->id] = [
                        'nombre' => $dimension->nombre,
                        'color' => $dimension->color,
                        'slug' => Str::slug($dimension->nombre),
                        'tematicas' => [],
                    ];
                }

                // Creamos nuestro "KPI fantasma"
                $kpiFantasma = [
                    'indicador_id'  => null,
                    'nombre'        => $indicadorNombre,
                    'valor'         => 'lista', // <-- La bandera clave para el PDF
                    'anio'          => '',
                    'valor_display' => $municipio->instrumentos, // <-- La colección
                    'unidad'        => '',
                    'solo_resumen'  => true,
                ];

                // Inyectamos el KPI fantasma
                $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = $kpiFantasma;
            }
        }

        // 6. Convertimos el array asociativo en indexado para el @foreach del PDF
        $datosAgrupados = array_values($datosPorDimension);

        // --- FIN: LÓGICA DE DATOS ---


        // Cargamos la vista del PDF (Esto se queda igual)
        // La vista 'municipios.resumen_pdf' ahora recibirá la estructura de datos correcta
        $pdf = PDF::loadView('municipios.resumen_pdf', compact('municipio', 'datosAgrupados'));

        // Generamos un nombre de archivo dinámico y lo ofrecemos para descarga (Esto se queda igual)
        $fileName = 'resumen-' . Str::slug($municipio->nombre) . '.pdf';
        return $pdf->download($fileName);
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
        // --- LÓGICA DE PRIORIZACIÓN DE VARIABLE "TOTAL" ---
        $variables = $indicador->variables;

        // Buscamos una variable cuyo nombre amigable contenga "Total".
        $variableTotal = $variables->first(function ($variable) {
            // Usamos mb_strtolower para que funcione con acentos y str_contains para buscar la palabra.
            return str_contains(mb_strtolower($variable->nombre_amigable, 'UTF-8'), 'total');
        });

        // Si encontramos una variable "Total", usamos solo su ID.
        // Si no, usamos los IDs de todas las variables.
        $variableIds = $variableTotal ? [$variableTotal->id] : $variables->pluck('id')->all();
        // --- FIN DE LA LÓGICA ---

        $datos = DatoHistorico::whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->join('municipios', 'dato_historicos.municipio_id', '=', 'municipios.id')
            ->select('municipios.cvegeo', DB::raw('SUM(valor) as total_valor'))
            ->groupBy('municipios.cvegeo')
            ->pluck('total_valor', 'municipios.cvegeo')
            ->mapWithKeys(function ($value, $key) {
                return [(string) $key => $value];
            });

        return response()->json($datos);
    }

    /**
     * Retrieves a distinct list of all available historical years for the specified Indicator.
     *
     * @param  \App\Models\Indicador  $indicador // The Indicador model instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndicatorYears(Indicador $indicador)
    {
        $variableIds = $indicador->variables()->pluck('id');

        $years = DatoHistorico::whereIn('variable_id', $variableIds)
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return response()->json($years);
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
        $variablesKPI = Variable::with('indicador.tematica.dimension')
            ->where('es_kpi', true)
            ->get();


        $datosPorDimension = [];

        foreach ($variablesKPI as $variable) {
            $dato = DatoHistorico::where('variable_id', $variable->id)
                ->where('municipio_id', $municipio->id)
                ->orderBy('anio', 'desc')
                ->first();

            $dimension      = $variable->indicador->tematica->dimension;
            $tematicaNombre = $variable->indicador->tematica->nombre;

            // Si es la primera vez que vemos esta dimensión, inicializamos su estructura
            if (! isset($datosPorDimension[$dimension->id])) {
                $datosPorDimension[$dimension->id] = [
                    'nombre'    => $dimension->nombre,
                    'color'     => $dimension->color,
                    'slug'      => Str::slug($dimension->nombre),
                    'tematicas' => [],
                ];
            }

            // Añadimos el KPI a la temática correspondiente dentro de su dimensión
            $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = [
                'indicador_id'  => $variable->indicador->id,
                'nombre'        => $variable->nombre_amigable,
                'valor'         => $dato->valor ?? 'N/D',
                'anio'          => $dato->anio ?? 'N/D',
                'valor_display' => $dato ? $dato->valor_display : 'N/D',
                'unidad'        => $variable->unidad_medida,
                'solo_resumen'  => $variable->indicador->solo_resumen,
            ];
        }
        if ($municipio->instrumentos->isNotEmpty()) {
            // Nombres exactos de la jerarquía donde queremos inyectarlo
            $dimensionNombre = 'Geográfica y Medio Ambiente';
            $tematicaNombre = 'Ordenamiento Territorial';
            $indicadorNombre = 'Tipo de instrumentos de planeación en materia territorial';

            // Buscamos el ID de la dimensión para poder agrupar correctamente
            $dimension = Dimension::where('nombre', $dimensionNombre)->first();

            if ($dimension) {
                // Si la dimensión no existe en nuestro array, la creamos
                if (!isset($datosPorDimension[$dimension->id])) {
                    $datosPorDimension[$dimension->id] = [
                        'nombre' => $dimension->nombre,
                        'color' => $dimension->color,
                        'slug' => Str::slug($dimension->nombre),
                        'tematicas' => [],
                    ];
                }

                // Creamos nuestro "KPI fantasma"
                $kpiFantasma = [
                    'indicador_id'  => null, // No es un indicador real
                    'nombre'        => $indicadorNombre,
                    'valor'         => 'lista', // Una bandera especial para la vista
                    'anio'          => '',
                    'valor_display' => $municipio->instrumentos, // ¡Aquí pasamos la colección completa!
                    'unidad'        => '',
                    'solo_resumen'  => true,
                ];

                // Inyectamos el KPI fantasma en la temática correcta
                $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = $kpiFantasma;
            }
        }
        // Convertimos el arreglo asociativo en uno indexado para el @foreach de Blade
        $datosAgrupados = array_values($datosPorDimension);

        // --- FIN DE LA NUEVA LÓGICA DE AGRUPACIÓN ---

        return view('municipios.resumen', [
            'municipio'      => $municipio,
            'datosAgrupados' => $datosAgrupados,
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
        // ponytail: Obtener estadísticas optimizadas del Hero usando FichaProfilerService (2 queries en lugar de 12)
        $hero = $this->getHeroStats($municipio);
        $poblacionTotal = $hero['poblacionTotal'];
        $gradoMarginacion = $hero['gradoMarginacion'];
        $superficieKm2 = $hero['superficieKm2'];
        $presupuestoTotal = $hero['presupuesto'];
        $anioPresupuesto = $hero['ultimoAnioPres'] ?? 'N/D';

        // 2. Carga de Wikipedia (ponytail: corregir bug de tipo de argumento)
        $wikiSummary = $this->getWikipediaSummary($municipio->nombre);

        // 3. Carga de Configuración Dinámica para V3
        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $datosAgrupados = [];
        foreach ($configuraciones as $config) {
            $indicador = $config->indicador;
            $dimension = $indicador->tematica->dimension;
            $tematica  = $indicador->tematica;

            // Obtener datos del indicador
            $datos = $this->obtenerDatosParaConfig($config, $municipio);
            $narrativa = $this->procesarNarrativa($config->plantilla_narrativa, $municipio, $datos);

            // Determinar tipo visual para la card
            $variablePrincipal = $indicador->variables->first();
            $unidad = strtolower($variablePrincipal->unidad_medida ?? '');
            $tipoVisual = 'absoluto';
            if (str_contains($unidad, '%') || str_contains($unidad, 'porcentaje')) {
                $tipoVisual = 'porcentaje';
            } elseif (str_contains($unidad, '$') || str_contains($unidad, 'pesos')) {
                $tipoVisual = 'moneda';
            }

            // Historial para sparklines (Filtrado por municipio)
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

            // Reutilizamos la estructura esperada por la vista v3
            if (!isset($datosAgrupados[$dimension->id])) {
                $datosAgrupados[$dimension->id] = [
                    'nombre' => $dimension->nombre,
                    'slug'   => Str::slug($dimension->nombre),
                    'color'  => $dimension->color,
                    'tematicas' => []
                ];
            }

            $datosAgrupados[$dimension->id]['tematicas'][$tematica->nombre][] = [
                'config'    => $config,
                'indicador' => $indicador,
                'datos'     => $datos,
                'narrativa' => $narrativa,
                'indicador_id'     => $indicador->id,
                'indicador_nombre' => $indicador->nombre_amigable,
                'nombre'           => $indicador->nombre_amigable,
                'valor'            => is_array($datos) ? ($datos['total'] ?? 0) : (is_numeric($datos) ? $datos : 0),
                'valor_display'    => is_array($datos) ? ($datos['valor_actual'] ?? (isset($datos['total']) ? number_format($datos['total']) : 'N/D')) : ($datos ?? 'N/D'),
                'anio'             => is_array($datos) ? ($datos['anio'] ?? 'N/D') : 'N/D',
                'unidad'           => $variablePrincipal->unidad_medida ?? '',
                'solo_resumen'     => $indicador->solo_resumen,
                'tipo_visual'      => ($config->tipo_visualizacion === 'lista') ? 'lista' : $tipoVisual,
                'historial'        => json_encode($historial),
            ];
        }

        return view('municipios.resumen_v3', [
            'municipio'        => $municipio,
            'poblacionTotal'   => $poblacionTotal,
            'gradoMarginacion' => $gradoMarginacion,
            'presupuestoTotal' => $presupuestoTotal,
            'anioPresupuesto'  => $anioPresupuesto,
            'superficieKm2'    => $superficieKm2,
            'wikiSummary'      => $wikiSummary,
            'datosAgrupados'   => $datosAgrupados
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
            if (str_contains($unidad, '%') || str_contains($unidad, 'porcentaje')) {
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

        if ($municipio->instrumentos->isNotEmpty()) {
            $dimensionNombre = 'Geográfica y Medio Ambiente';
            $tematicaNombre = 'Ordenamiento Territorial';
            $indicadorNombre = 'Tipo de instrumentos de planeación en materia territorial';

            $dimension = Dimension::where('nombre', $dimensionNombre)->first();

            if ($dimension) {
                if (!isset($datosPorDimension[$dimension->id])) {
                    $datosPorDimension[$dimension->id] = [
                        'nombre' => $dimension->nombre,
                        'color' => $dimension->color,
                        'slug' => Str::slug($dimension->nombre),
                        'tematicas' => [],
                    ];
                }

                $kpiFantasma = [
                    'indicador_id'     => null,
                    'indicador_nombre' => 'Planeación',
                    'nombre'           => $indicadorNombre,
                    'valor'            => 'lista',
                    'anio'             => '',
                    'valor_display'    => $municipio->instrumentos,
                    'unidad'           => '',
                    'solo_resumen'     => true,
                    'tipo_visual'      => 'lista',
                    'tendencia'        => null,
                    'tendenciaClase'   => '',
                    'tendenciaIcono'   => '',
                    'historial'        => '[]',
                ];

                $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = $kpiFantasma;
            }
        }
        $datosAgrupados = array_values($datosPorDimension);

        $wikiSummary = $this->getWikipediaSummary($municipio->nombre);

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
     * Organiza y agrupa los municipios por macrorregiones y microrregiones
     * en un diseño de cuadrícula interactivo.
     *
     * @return \Illuminate\View\View Vista del directorio municipal.
     */
    public function directorioVisual()
    {
        $macrorregiones = Macrorregion::with(['microrregiones.municipios' => function ($q) {
            $q->orderBy('nombre', 'asc');
        }])->orderBy('id', 'asc')->get();

        return view('municipios.directorio', compact('macrorregiones'));
    }



    /**
     * Retrieves a distinct, ordered list of years available for a complex indicator,
     * ensuring data exists for the minimum required number of municipalities within the selection.
     *
     * @param  \App\Models\Indicador  $indicador // The complex Indicador model instance.
     * @param  array<int|string>  $selectionIds // Array of selected Municipio IDs (or ['estatal']).
     * @param  int  $countRequired // Minimum number of municipalities required to have data for a year to be considered available.
     * @return \Illuminate\Support\Collection<int> // Collection of available years.
     */
    private function getAvailableYearsForComplex(Indicador $indicador, array $selectionIds, int $countRequired = 1)
    {
        $query = DatoIndicadorComplejo::where('indicador_id', $indicador->id);

        if (! in_array('estatal', $selectionIds)) {
            $query->whereIn('municipio_id', $selectionIds);
        }

        return $query->select('anio')
            ->groupBy('anio')
            ->havingRaw('COUNT(DISTINCT municipio_id) >= ?', [$countRequired])
            ->orderBy('anio', 'desc')
            ->pluck('anio');
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
        // Usamos 'whereHas' para hacer una consulta a través de
        // las relaciones anidadas, que es mucho más eficiente.
        $anios = DatoHistorico::whereHas('variable.indicador.tematica.dimension', function ($query) use ($dimension) {
            $query->where('id', $dimension->id);
        })
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return response()->json($anios);
    }

    /**
     * Devuelve los años disponibles para un indicador complejo específico.
     * (Responde a la llamada AJAX del Paso 2)
     */
    public function getAniosPorIndicadorComplejo(Indicador $indicador)
    {
        if (!$indicador->es_complejo) {
            return response()->json(['error' => 'Indicador no válido'], 404);
        }

        $anios = DatoIndicadorComplejo::where('indicador_id', $indicador->id)
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return response()->json($anios);
    }

    /**
     * Inicia la descarga de datos complejos filtrados por indicador y año.
     * (Responde a la ruta de descarga del Paso 2)
     */
    public function exportDatosComplejos(Indicador $indicador, $anio)
    {
        if (!$indicador->es_complejo) {
            abort(404, 'Exportación no disponible para este indicador.');
        }

        $fileName = 'exportacion-' . Str::slug($indicador->nombre_tecnico) . '-anio-' . $anio . '.csv';

        // Le pasamos el indicador y el año a la clase de exportación
        return Excel::download(new DatosComplejosExport($indicador, $anio), $fileName);
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

        // --- CÁLCULO DE DATOS PARA EL HERO (Igual que en resumen-test) ---
        $hero = $this->getHeroStats($municipio);
        $poblacionTotal = $hero['poblacionTotal'];
        $gradoMarginacion = $hero['gradoMarginacion'];
        $superficieKm2 = $hero['superficieKm2'];
        $presupuesto = $hero['presupuesto'];
        $ultimoAnioPres = $hero['ultimoAnioPres'];
        $porcentajePobreza = $hero['porcentajePobreza'];
        $pea = $hero['pea'];

        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $allVariableIds = FichaDataStore::extractVariableIds($configuraciones);
        $dataStore = new FichaDataStore($municipio, $allVariableIds);

        $perfil = [];

        foreach ($configuraciones as $config) {
            $datos = $this->obtenerDatosParaConfig($config, $municipio, $dataStore);

            $narrativa = $this->procesarNarrativa($config->plantilla_narrativa, $municipio, $datos);

            $dimension = $config->indicador->tematica->dimension->nombre ?? 'Sin Dimensión';
            $dimensionKey = str_replace(' ', '_', strtolower($dimension));

            $perfil[$dimensionKey][] = [
                'config' => $config,
                'datos' => $datos,
                'narrativa' => $narrativa
            ];
        }

        // --- CÁLCULO DE MUNICIPIOS SIMILARES ---
        $varPob = Variable::where('nombre_amigable', 'Poblacion total')->whereHas('indicador', fn($q) => $q->where('nombre_amigable','Población total segun sexo'))->first();
        $similaresPoblacion = collect();
        if ($varPob && $poblacionTotal > 0) {
            $macrorregionId = $municipio->microrregion?->macrorregion_id;

            $query = Municipio::where('municipios.id', '!=', $municipio->id)
                ->join('dato_historicos', 'municipios.id', '=', 'dato_historicos.municipio_id')
                ->where('dato_historicos.variable_id', $varPob->id)
                ->where('dato_historicos.anio', function($query) use ($varPob) {
                    $query->selectRaw('max(d2.anio)')
                        ->from('dato_historicos as d2')
                        ->whereColumn('d2.municipio_id', 'municipios.id')
                        ->where('d2.variable_id', $varPob->id);
                });

            if ($macrorregionId) {
                $query->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
                    ->where('microrregions.macrorregion_id', $macrorregionId);
            }

            $similaresPoblacion = $query->select('municipios.*', 'dato_historicos.valor as poblacion_valor')
                ->orderByRaw('ABS(dato_historicos.valor - ?) ASC', [$poblacionTotal])
                ->limit(4)
                ->get();
        }

        $similaresRegion = collect();
        if ($municipio->microrregion_id) {
            $similaresRegion = Municipio::with('microrregion')
                ->where('id', '!=', $municipio->id)
                ->where('microrregion_id', $municipio->microrregion_id)
                ->limit(4)
                ->get();
        }

        return view('municipios.perfil', compact(
            'municipio', 'perfil', 'poblacionTotal', 'gradoMarginacion', 
            'superficieKm2', 'presupuesto', 'ultimoAnioPres', 'porcentajePobreza', 
            'pea', 'similaresPoblacion', 'similaresRegion'
        ));
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
        $variable = null;
        $isPresupuesto = false;

        if (is_numeric($configKeyOrId)) {
            $config = ConfiguracionFicha::with(['variables', 'indicador.variables'])->find($configKeyOrId);
            if ($config) {
                $variable = $config->variables->first() ?? $config->indicador->variables->first();
            }
        } else {
            // Claves estáticas para los indicadores del Hero
            $key = strtolower($configKeyOrId);
            switch ($key) {
                case 'poblacion':
                    $variable = Variable::where('nombre_amigable', 'Población total')
                        ->whereHas('indicador', fn($q) => $q->where('nombre_amigable', 'Población total según sexo'))
                        ->first();
                    break;
                case 'pea':
                    $variable = Variable::where('nombre_amigable', 'Población Económicamente Activa (PEA)')->first();
                    break;
                case 'pobreza':
                    $variable = Variable::where('nombre_amigable', 'Porcentaje de población en situación de pobreza')->first();
                    break;
                case 'marginacion':
                    $variable = Variable::where('nombre_amigable', 'Grado de Marginación')->first();
                    break;
                case 'superficie':
                    $variable = Variable::where('nombre_amigable', 'Superficie territorial (Hectáreas)')->first();
                    break;
                case 'presupuesto':
                    $isPresupuesto = true;
                    break;
            }
        }

        if (!$variable && !$isPresupuesto) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la variable asociada a esta métrica.'
            ]);
        }

        // Lógica especial para presupuesto (que es la suma de FORTAMUN y FAISMUN)
        if ($isPresupuesto) {
            $varsPresupuestoIds = Variable::whereIn('nombre_amigable', ['FORTAMUN APROBADO', 'FAISMUN APROBADO'])->pluck('id');
            $anio = DatoHistorico::whereIn('variable_id', $varsPresupuestoIds)->where('municipio_id', $municipio->id)->max('anio');
            
            if (!$anio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos históricos disponibles para el presupuesto.'
                ]);
            }

            $valorActual = DatoHistorico::whereIn('variable_id', $varsPresupuestoIds)
                ->where('municipio_id', $municipio->id)
                ->where('anio', $anio)
                ->sum('valor');

            $macrorregionId = $municipio->microrregion?->macrorregion_id;

            $query = Municipio::where('municipios.id', '!=', $municipio->id)
                ->join('dato_historicos', 'municipios.id', '=', 'dato_historicos.municipio_id')
                ->whereIn('dato_historicos.variable_id', $varsPresupuestoIds)
                ->where('dato_historicos.anio', $anio);

            if ($macrorregionId) {
                $query->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
                    ->where('microrregions.macrorregion_id', $macrorregionId);
            }

            $similares = $query->select('municipios.nombre', 'municipios.slug', DB::raw('SUM(dato_historicos.valor) as total_valor'))
                ->groupBy('municipios.id', 'municipios.nombre', 'municipios.slug')
                ->orderByRaw('ABS(SUM(dato_historicos.valor) - ?) ASC', [$valorActual])
                ->limit(4)
                ->get();

            $similaresFormateados = $similares->map(function ($s) {
                return [
                    'nombre' => $s->nombre,
                    'slug' => $s->slug,
                    'valor' => '$' . number_format((float)$s->total_valor, 0)
                ];
            });

            return response()->json([
                'success' => true,
                'indicador' => 'Presupuesto de Egresos',
                'variable' => 'FORTAMUN + FAISMUN',
                'anio' => $anio,
                'valor_actual' => '$' . number_format((float)$valorActual, 0),
                'similares' => $similaresFormateados
            ]);
        }

        // Obtener el valor más reciente del municipio actual para esta variable
        $ultimoDato = DatoHistorico::where('variable_id', $variable->id)
            ->where('municipio_id', $municipio->id)
            ->orderBy('anio', 'desc')
            ->first();

        if (!$ultimoDato) {
            return response()->json([
                'success' => false,
                'message' => 'No hay datos históricos disponibles para la variable seleccionada.'
            ]);
        }

        $valorActual = $ultimoDato->valor;
        $anio = $ultimoDato->anio;

        $macrorregionId = $municipio->microrregion?->macrorregion_id;

        $query = Municipio::where('municipios.id', '!=', $municipio->id)
            ->join('dato_historicos', 'municipios.id', '=', 'dato_historicos.municipio_id')
            ->where('dato_historicos.variable_id', $variable->id)
            ->where('dato_historicos.anio', $anio);

        if ($macrorregionId) {
            $query->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
                ->where('microrregions.macrorregion_id', $macrorregionId);
        }

        if (is_numeric($valorActual)) {
            // Similitud numérica (valores más cercanos)
            $similares = $query->select('municipios.nombre', 'municipios.slug', 'dato_historicos.valor')
                ->orderByRaw('ABS(dato_historicos.valor - ?) ASC', [(float)$valorActual])
                ->limit(4)
                ->get();
        } else {
            // Similitud categórica (mismo valor textual)
            $similares = $query->select('municipios.nombre', 'municipios.slug', 'dato_historicos.valor')
                ->where('dato_historicos.valor', $valorActual)
                ->limit(4)
                ->get();
        }

        // Formatear los valores para la respuesta
        $similaresFormateados = $similares->map(function ($s) use ($variable, $configKeyOrId) {
            $val = $s->valor;
            if (is_numeric($val)) {
                $valFloat = (float)$val;
                if (strtolower($configKeyOrId) === 'superficie') {
                    $valFloat = $valFloat / 100;
                    $valFormated = number_format($valFloat, 2) . ' km²';
                } else {
                    $unidad = strtolower($variable->unidad ?? '');
                    if (str_contains($unidad, '%') || str_contains($unidad, 'porcentaje')) {
                        $valFormated = number_format($valFloat, 1) . '%';
                    } elseif (str_contains($unidad, '$') || str_contains($unidad, 'pesos') || str_contains($unidad, 'monto')) {
                        $valFormated = '$' . number_format($valFloat, 0);
                    } else {
                        $valFormated = number_format($valFloat, 0) . ' ' . $variable->unidad;
                    }
                }
            } else {
                $valFormated = $val;
            }

            return [
                'nombre' => $s->nombre,
                'slug' => $s->slug,
                'valor' => $valFormated
            ];
        });

        // Formatear también el valor del municipio actual
        if (is_numeric($valorActual)) {
            $valFloat = (float)$valorActual;
            if (strtolower($configKeyOrId) === 'superficie') {
                $valFloat = $valFloat / 100;
                $valActualFormated = number_format($valFloat, 2) . ' km²';
            } else {
                $unidad = strtolower($variable->unidad ?? '');
                if (str_contains($unidad, '%') || str_contains($unidad, 'porcentaje')) {
                    $valActualFormated = number_format($valFloat, 1) . '%';
                } elseif (str_contains($unidad, '$') || str_contains($unidad, 'pesos') || str_contains($unidad, 'monto')) {
                    $valActualFormated = '$' . number_format($valFloat, 0);
                } else {
                    $valActualFormated = number_format($valFloat, 0) . ' ' . $variable->unidad;
                }
            }
        } else {
            $valActualFormated = $valorActual;
        }

        return response()->json([
            'success' => true,
            'indicador' => is_numeric($configKeyOrId) ? ($config->titulo_reporte ?? $config->indicador->nombre_amigable) : ucwords(str_replace('marginacion', 'marginación', $configKeyOrId)),
            'variable' => $variable->nombre_amigable,
            'anio' => $anio,
            'valor_actual' => $valActualFormated,
            'similares' => $similaresFormateados
        ]);
    }

    /**
     * Obtiene y estructura los datos de un indicador o variable para una configuración de ficha específica.
     * Soporta diferentes tipos de visualizaciones: scatter (dispersión), pirámide, mapas y gráficos estándar.
     * Extrae información de rango municipal, estatal, macrorregional, rankings y tendencias históricas.
     *
     * @param  \App\Models\ConfiguracionFicha  $config Configuración de la ficha de la cual obtener los datos.
     * @param  \App\Models\Municipio  $municipio Municipio base para consultar datos históricos.
     * @param  \App\Services\FichaDataStore|null  $dataStore Almacén de datos optimizado en memoria para evitar consultas redundantes.
     * @return array|null Estructura de datos formateada para el motor de ECharts y la narrativa descriptiva.
     */
    private function obtenerDatosParaConfig($config, $municipio, FichaDataStore $dataStore = null, $anioForzado = null)
    {
        $indicador = $config->indicador;
        $variablesConfig = $config->variables;

        $variableIds = $variablesConfig->isNotEmpty()
            ? $variablesConfig->pluck('id')
            : $indicador->variables->pluck('id');

        if ($dataStore) {
            $anioMax = $anioForzado ?? $dataStore->muniData->whereIn('variable_id', $variableIds)->max('anio');
        } else {
            $anioMax = $anioForzado ?? DatoHistorico::whereIn('variable_id', $variableIds)
                ->where('municipio_id', $municipio->id)
                ->max('anio');
        }

        if (!$anioMax) return null;

        // Caso Scatter (Dispersión)
        if ($config->tipo_visualizacion === 'scatter') {
            $varXId = $variableIds->first();
            $varYId = $variableIds->skip(1)->first();

            if ($varXId && $varYId) {
                if ($dataStore) {
                    $varX = $dataStore->allVariables->get($varXId);
                    $varY = $dataStore->allVariables->get($varYId);
                } else {
                    $varX = Variable::find($varXId);
                    $varY = Variable::find($varYId);
                }

                // Obtener años máximos independientes
                if ($dataStore) {
                    $anioX = $dataStore->globalData->where('variable_id', $varXId)->max('anio');
                    $anioY = $dataStore->globalData->where('variable_id', $varYId)->max('anio');
                } else {
                    $anioX = DatoHistorico::where('variable_id', $varXId)->max('anio');
                    $anioY = DatoHistorico::where('variable_id', $varYId)->max('anio');
                }

                // Obtener datos históricos de todos los municipios para esas dos variables
                if ($dataStore) {
                    $datosX = $dataStore->globalData->where('variable_id', $varXId)->where('anio', $anioX)->keyBy('municipio_id');
                    $datosY = $dataStore->globalData->where('variable_id', $varYId)->where('anio', $anioY)->keyBy('municipio_id');
                } else {
                    $datosX = DatoHistorico::where('variable_id', $varXId)->where('anio', $anioX)->get()->keyBy('municipio_id');
                    $datosY = DatoHistorico::where('variable_id', $varYId)->where('anio', $anioY)->get()->keyBy('municipio_id');
                }

                // Obtener poblaciones totales del 2020 para calcular el per cápita
                $varPob = Variable::where('nombre_amigable', 'Población total')->first();
                $poblaciones = [];
                if ($varPob) {
                    if ($dataStore) {
                        $poblaciones = $dataStore->globalData->where('variable_id', $varPob->id)->where('anio', 2020)->keyBy('municipio_id');
                    } else {
                        $poblaciones = DatoHistorico::where('variable_id', $varPob->id)->where('anio', 2020)->get()->keyBy('municipio_id');
                    }
                }

                $municipios = Municipio::all()->keyBy('id');

                $seriesNormal = [];
                $seriesHighlight = [];

                foreach ($municipios as $munId => $mun) {
                    $datoX = $datosX->get($munId);
                    $datoY = $datosY->get($munId);
                    $pob = $poblaciones->get($munId);

                    if ($datoX && $datoY) {
                        $xVal = (float)$datoX->valor;
                        
                        // Si la unidad es pesos y tenemos población, calculamos per cápita
                        if ($pob && $pob->valor > 0 && str_contains(mb_strtolower($varX->unidad_medida, 'UTF-8'), 'pesos')) {
                            $factor = str_contains(mb_strtolower($varX->unidad_medida, 'UTF-8'), 'miles') ? 1000 : 1;
                            $xVal = ($datoX->valor * $factor) / $pob->valor;
                        }

                        $yVal = (float)$datoY->valor;

                        $punto = [
                            round($xVal, 2),
                            round($yVal, 2),
                            $mun->nombre
                        ];

                        if ($munId == $municipio->id) {
                            $seriesHighlight[] = $punto;
                        } else {
                            $seriesNormal[] = $punto;
                        }
                    }
                }

                $xValSelected = !empty($seriesHighlight) ? $seriesHighlight[0][0] : 0;
                $yValSelected = !empty($seriesHighlight) ? $seriesHighlight[0][1] : 0;

                return [
                    'anio'             => $anioY,
                    'total'            => null,
                    'variables'        => [
                        [
                            'nombre' => $varX->nombre_amigable, 
                            'unidad' => '$ por hab.',
                            'valor'  => $xValSelected
                        ],
                        [
                            'nombre' => $varY->nombre_amigable, 
                            'unidad' => $varY->unidad_medida,
                            'valor'  => $yValSelected
                        ]
                    ],
                    'echarts'          => [
                        'type' => 'scatter',
                        'series' => [
                            [
                                'name' => 'Otros Municipios',
                                'type' => 'scatter',
                                'data' => $seriesNormal,
                                'itemStyle' => [
                                    'color' => 'rgba(122, 122, 122, 0.4)', 
                                    'borderColor' => 'rgba(122, 122, 122, 0.6)'
                                ]
                            ],
                            [
                                'name' => $municipio->nombre,
                                'type' => 'scatter',
                                'data' => $seriesHighlight,
                                'symbolSize' => 16,
                                'itemStyle' => [
                                    'color' => '#861e34',
                                    'borderColor' => '#c79b66',
                                    'borderWidth' => 2,
                                    'shadowBlur' => 10,
                                    'shadowColor' => 'rgba(134, 30, 52, 0.8)'
                                ]
                            ]
                        ],
                        'eje_x' => [
                            'titulo' => $varX->nombre_amigable . ' per cápita ($/hab)'
                        ],
                        'eje_y' => [
                            'titulo' => $varY->nombre_amigable
                        ]
                    ],
                    'descripcion'    => "Este gráfico compara la inversión de {$varX->nombre_amigable} per cápita (año {$anioX}) contra el indicador {$varY->nombre_amigable} (año {$anioY}) para todos los municipios de Puebla. El punto resaltado representa a {$municipio->nombre}.",
                    'fuente'         => "CONEVAL / SHCP",
                    'metodo_calculo' => "Inversión per cápita = ({$varX->nombre_amigable} / Población total)."
                ];
            }
        }

        // Caso Pirámide
        if ($config->tipo_visualizacion === 'piramide') {
            return $this->handlePiramideChart($indicador, ['ids' => [$municipio->id], 'titulo' => $municipio->nombre], $variableIds->toArray());
        }

        // Caso Treemap o Complejo
        if ($indicador->es_complejo) {
            $registro = \App\Models\DatoIndicadorComplejo::where('indicador_id', $indicador->id)
                ->where('municipio_id', $municipio->id)
                ->where('anio', $anioMax)
                ->first();
            return $registro ? (is_array($registro->datos) ? $registro->datos : json_decode($registro->datos, true)) : null;
        }

        // Caso Estándar (Variables sumadas o listadas)
        if ($dataStore) {
            $datos = $dataStore->muniData
                ->whereIn('variable_id', $variableIds)
                ->where('anio', $anioMax);
        } else {
            $datos = DatoHistorico::with('variable')
                ->whereIn('variable_id', $variableIds)
                ->where('municipio_id', $municipio->id)
                ->where('anio', $anioMax)
                ->get();
        }

        $valorTotal = $datos->sum('valor');

        // --- Sparkline Data (Tendencia últimos años) ---
        $aniosLimite = $config->anios_historial ?? 5;
        if ($dataStore) {
            $tendencia = $dataStore->muniData
                ->whereIn('variable_id', $variableIds)
                ->groupBy('anio')
                ->map(fn($group) => [
                    'anio' => $group->first()->anio,
                    'total' => (float)$group->sum('valor')
                ])
                ->sortByDesc('anio')
                ->take($aniosLimite)
                ->sortBy('anio')
                ->values()
                ->map(fn($t) => ['anio' => $t['anio'], 'valor' => $t['total']])
                ->toArray();
        } else {
            $tendencia = DatoHistorico::whereIn('variable_id', $variableIds)
                ->where('municipio_id', $municipio->id)
                ->select('anio', \DB::raw('SUM(valor) as total'))
                ->groupBy('anio')
                ->orderBy('anio', 'desc')
                ->limit($aniosLimite)
                ->get()
                ->sortBy('anio')
                ->values()
                ->map(fn($t) => ['anio' => $t->anio, 'valor' => (float)$t->total])
                ->toArray();
        }

        // --- Benchmarking (Promedio Estatal y Macrorregional) ---
        $promediosEstado = [];
        $promediosMacrorregion = [];
        $tendenciaEstado = [];
        $tendenciaMacrorregion = [];

        $macrorregionId = $municipio->microrregion->macrorregion_id ?? null;
        $municipiosMacrorregionIds = $dataStore ? $dataStore->macrorregionIds : collect();

        $ajustes = $config->ajustes_visuales ?? [];
        $benchmarkMode = $ajustes['benchmark_mode'] ?? 'avg';
        $method = $benchmarkMode === 'sum' ? 'sum' : 'avg';

        if ($config->mostrar_comparativa) {
            if (!$dataStore && $macrorregionId) {
                $municipiosMacrorregionIds = Municipio::whereHas('microrregion', function($q) use ($macrorregionId) {
                    $q->where('macrorregion_id', $macrorregionId);
                })->pluck('id');
            }

            foreach ($datos as $d) {
                if ($dataStore) {
                    $stateData = $dataStore->globalData
                        ->where('variable_id', $d->variable_id)
                        ->where('anio', $anioMax);
                    $promediosEstado[$d->variable_id] = $benchmarkMode === 'sum' ? $stateData->sum('valor') : $stateData->avg('valor');

                    if ($municipiosMacrorregionIds->isNotEmpty()) {
                        $regionData = $stateData->whereIn('municipio_id', $municipiosMacrorregionIds);
                        $promediosMacrorregion[$d->variable_id] = $benchmarkMode === 'sum' ? $regionData->sum('valor') : $regionData->avg('valor');
                    } else {
                        $promediosMacrorregion[$d->variable_id] = 0;
                    }
                } else {
                    $promediosEstado[$d->variable_id] = DatoHistorico::where('variable_id', $d->variable_id)
                        ->where('anio', $anioMax)
                        ->$method('valor') ?? 0;

                    if ($municipiosMacrorregionIds->isNotEmpty()) {
                        $promediosMacrorregion[$d->variable_id] = DatoHistorico::where('variable_id', $d->variable_id)
                            ->whereIn('municipio_id', $municipiosMacrorregionIds)
                            ->where('anio', $anioMax)
                            ->$method('valor') ?? 0;
                    } else {
                        $promediosMacrorregion[$d->variable_id] = 0;
                    }
                }
            }

            if (!empty($tendencia)) {
                $anios = collect($tendencia)->pluck('anio')->toArray();
                
                if ($dataStore) {
                    $tendenciaEstado = $dataStore->globalData
                        ->whereIn('variable_id', $variableIds)
                        ->whereIn('anio', $anios)
                        ->groupBy('anio')
                        ->mapWithKeys(function($group, $yr) use ($benchmarkMode) {
                            $total = $benchmarkMode === 'sum' ? $group->sum('valor') : $group->avg('valor');
                            return [$yr => (float)$total];
                        })
                        ->toArray();

                    if ($municipiosMacrorregionIds->isNotEmpty()) {
                        $tendenciaMacrorregion = $dataStore->globalData
                            ->whereIn('variable_id', $variableIds)
                            ->whereIn('municipio_id', $municipiosMacrorregionIds)
                            ->whereIn('anio', $anios)
                            ->groupBy('anio')
                            ->mapWithKeys(function($group, $yr) use ($benchmarkMode) {
                                $total = $benchmarkMode === 'sum' ? $group->sum('valor') : $group->avg('valor');
                                return [$yr => (float)$total];
                            })
                            ->toArray();
                    }
                } else {
                    $aggSql = $benchmarkMode === 'sum' ? 'SUM(valor) as total' : 'AVG(valor) as total';
                    
                    $tendenciaEstado = DatoHistorico::whereIn('variable_id', $variableIds)
                        ->select('anio', DB::raw($aggSql))
                        ->whereIn('anio', $anios)
                        ->groupBy('anio')
                        ->get()
                        ->keyBy('anio')
                        ->map(fn($t) => (float)$t->total)
                        ->toArray();

                    if ($municipiosMacrorregionIds->isNotEmpty()) {
                        $tendenciaMacrorregion = DatoHistorico::whereIn('variable_id', $variableIds)
                            ->whereIn('municipio_id', $municipiosMacrorregionIds)
                            ->select('anio', DB::raw($aggSql))
                            ->whereIn('anio', $anios)
                            ->groupBy('anio')
                            ->get()
                            ->keyBy('anio')
                            ->map(fn($t) => (float)$t->total)
                            ->toArray();
                    }
                }
            }
        }

        if ($indicador->es_complejo) {
            $availableYears = DatoIndicadorComplejo::where('indicador_id', $indicador->id)
                ->where('municipio_id', $municipio->id)
                ->distinct()
                ->orderBy('anio', 'desc')
                ->pluck('anio')
                ->toArray();
        } else {
            if ($dataStore) {
                $availableYears = $dataStore->muniData
                    ->whereIn('variable_id', $variableIds)
                    ->pluck('anio')
                    ->unique()
                    ->sortDesc()
                    ->values()
                    ->toArray();
            } else {
                $availableYears = DatoHistorico::whereIn('variable_id', $variableIds)
                    ->where('municipio_id', $municipio->id)
                    ->distinct()
                    ->orderBy('anio', 'desc')
                    ->pluck('anio')
                    ->toArray();
            }
        }

        $res = [
            'anio'                    => $anioMax,
            'available_years'         => $availableYears,
            'total'                   => $valorTotal,
            'valor_actual'            => number_format($valorTotal),
            'ranking'                 => $dataStore 
                ? $this->getMunicipalityRankingInMemory($dataStore, $variableIds, $municipio->id, $anioMax)
                : $this->getMunicipalityRanking($variableIds, $municipio->id, $anioMax),
            'promedio_estatal'        => $config->mostrar_comparativa 
                ? ($dataStore ? $this->getStateAverageInMemory($dataStore, $variableIds, $anioMax, $method) : $this->getStateAverage($variableIds, $anioMax, $method)) 
                : null,
            'promedio_macrorregional' => ($config->mostrar_comparativa && $macrorregionId) 
                ? ($dataStore ? $this->getMacrorregionalAverageInMemory($dataStore, $variableIds, $municipiosMacrorregionIds, $anioMax, $method) : $this->getMacrorregionalAverage($variableIds, $municipio, $anioMax, $method)) 
                : null,
            'variables'               => $datos->map(fn($d) => [
                'nombre'                  => $d->variable->nombre_amigable,
                'valor'                   => $d->valor,
                'unidad'                  => $d->variable->unidad_medida,
                'mapeo'                   => $d->variable->mapeo_valores,
                'promedio_estatal'        => $promediosEstado[$d->variable_id] ?? null,
                'promedio_macrorregional' => $promediosMacrorregion[$d->variable_id] ?? null
            ])->toArray(),
            'polaridad'               => $indicador->polaridad,
            'descripcion'             => $indicador->descripcion,
            'fuente'                  => $indicador->fuente,
            'metodo_calculo'          => $indicador->metodo_calculo,
            'tendencia'               => $tendencia,
            'tendencia_estado'        => $tendenciaEstado,
            'tendencia_macrorregion'  => $tendenciaMacrorregion,
        ];

        // --- Aplicar Mapeo Categórico (Prioridad: JSON Config > Variable DB) ---
        $ajustes = $config->ajustes_visuales;
        $mapeo = null;

        if (isset($ajustes['mapping']) && is_array($ajustes['mapping'])) {
            $mapeo = $ajustes['mapping'];
        } elseif (count($res['variables']) === 1) {
            $firstVar = reset($res['variables']);
            if (!empty($firstVar['mapeo'])) {
                $mapeo = $firstVar['mapeo'];
            }
        }

        if ($mapeo) {
            $valorOriginal = (string)$res['total'];
            if (isset($mapeo[$valorOriginal])) {
                $res['valor_actual'] = $mapeo[$valorOriginal];
            }

            // Soporte opcional para cambio de icono vía mapeo
            // Soporte opcional para cambio de icono vía mapeo
            if (isset($ajustes['icons'][$valorOriginal])) {
                $res['icono_actual'] = $ajustes['icons'][$valorOriginal];
            }
        }

        // Caso especial: Gráfico de Barras de Ranking de Vecinos Regionales (Variable Única)
        $esBarrasVariableUnica = ($config->tipo_visualizacion === 'barras' || $config->tipo_visualizacion === 'bar') && $variableIds->count() === 1;

        if ($esBarrasVariableUnica && $macrorregionId) {
            if ($municipiosMacrorregionIds->isEmpty()) {
                if ($dataStore) {
                    $municipiosMacrorregionIds = $dataStore->macrorregionIds;
                } else {
                    $municipiosMacrorregionIds = Municipio::whereHas('microrregion', function($q) use ($macrorregionId) {
                        $q->where('macrorregion_id', $macrorregionId);
                    })->pluck('id');
                }
            }

            if ($municipiosMacrorregionIds->isNotEmpty()) {
                $variableId = $variableIds->first();
                $variableObj = $dataStore ? $dataStore->allVariables->get($variableId) : Variable::find($variableId);
                $unidadMedida = $variableObj ? $variableObj->unidad_medida : '';

                // Obtener datos de la macrorregión
                if ($dataStore) {
                    $datosMacrorregion = $dataStore->globalData
                        ->where('variable_id', $variableId)
                        ->where('anio', $anioMax)
                        ->whereIn('municipio_id', $municipiosMacrorregionIds);
                } else {
                    $datosMacrorregion = DatoHistorico::where('variable_id', $variableId)
                        ->where('anio', $anioMax)
                        ->whereIn('municipio_id', $municipiosMacrorregionIds)
                        ->get();
                }

                // Obtener nombres de municipios
                $municipios = Municipio::whereIn('id', $municipiosMacrorregionIds)->get()->keyBy('id');
                
                $listaOrdenada = $datosMacrorregion->map(function($d) use ($municipios) {
                    $mun = $municipios->get($d->municipio_id);
                    return [
                        'municipio_id' => $d->municipio_id,
                        'nombre' => $mun ? $mun->nombre : 'Desconocido',
                        'valor' => (float)$d->valor
                    ];
                })
                ->sortByDesc('valor')
                ->values();

                $indexActual = $listaOrdenada->search(fn($item) => $item['municipio_id'] == $municipio->id);

                if ($indexActual !== false) {
                    $total = $listaOrdenada->count();
                    $countToTake = min(5, $total);
                    $startIndex = $indexActual - 2;

                    if ($startIndex < 0) {
                        $startIndex = 0;
                    }
                    if ($startIndex + $countToTake > $total) {
                        $startIndex = max(0, $total - $countToTake);
                    }

                    $vecinos = $listaOrdenada->slice($startIndex, $countToTake)->values();

                    $dataSerie = [];
                    $categoriasY = [];

                    foreach ($vecinos as $v) {
                        $posRank = $listaOrdenada->search(fn($item) => $item['municipio_id'] == $v['municipio_id']) + 1;
                        $categoriasY[] = $v['nombre'] . " ({$posRank}°)";

                        if ($v['municipio_id'] == $municipio->id) {
                            $dataSerie[] = [
                                'value' => $v['valor'],
                                'itemStyle' => [
                                    'color' => '#861e34',
                                    'borderColor' => '#c79b66',
                                    'borderWidth' => 2
                                ]
                            ];
                        } else {
                            $dataSerie[] = [
                                'value' => $v['valor'],
                                'itemStyle' => [
                                    'color' => '#d1d5db'
                                ]
                            ];
                        }
                    }

                    $res['echarts'] = [
                        'type' => 'bar-horizontal',
                        'eje_y' => [
                            'categorias' => $categoriasY
                        ],
                        'series' => [
                            [
                                'name' => $variableObj ? $variableObj->nombre_amigable : 'Valor',
                                'data' => $dataSerie
                            ]
                        ],
                        'unidad' => $unidadMedida
                    ];
                    
                    return $res;
                }
            }
        }

        $res['echarts'] = $this->formatearDatosParaECharts(
            $res['variables'],
            $config->tipo_visualizacion,
            $variableIds,
            $anioMax,
            $tendencia,
            $tendenciaEstado,
            $tendenciaMacrorregion
        );

        return $res;
    }

    /**
     * Formatea el conjunto de variables y sus valores en la estructura JSON requerida
     * por la biblioteca de gráficos Apache ECharts.
     * Soporta diferentes tipos de series (línea, barra, dona, etc.), incluyendo la inyección
     * de líneas de tendencias para municipio, estado y macrorregión.
     *
     * @param  array  $variablesArray Variables procesadas con sus respectivos datos históricos.
     * @param  string  $tipo_visualizacion Tipo de visualización configurado (ej: barras, lineas, dona).
     * @param  mixed  $variableIds Colección o array de identificadores de variables involucradas.
     * @param  int|null  $anio Año máximo de análisis.
     * @param  array|null  $tendencia Historial del municipio.
     * @param  array|null  $tendenciaEstado Historial estatal.
     * @param  array|null  $tendenciaMacrorregion Historial macrorregional.
     * @return array Estructura con la configuración de series, ejes y opciones para ECharts.
     */
    private function formatearDatosParaECharts(array $variablesArray, string $tipo_visualizacion, $variableIds = null, $anio = null, $tendencia = null, $tendenciaEstado = null, $tendenciaMacrorregion = null)
    {
        $tipoLower = strtolower($tipo_visualizacion);
        $tipoNorm = $tipoLower;
        if ($tipoLower === 'barras') {
            $tipoNorm = 'bar';
        } elseif ($tipoLower === 'lineas' || $tipoLower === 'líneas') {
            $tipoNorm = 'line';
        }

        if (($tipoNorm === 'line') && $tendencia) {
            $series = [
                [
                    'name' => 'Municipio',
                    'type' => 'line',
                    'data' => collect($tendencia)->pluck('valor')->toArray(),
                    'smooth' => true,
                    'symbol' => 'circle',
                    'symbolSize' => 8,
                ]
            ];

            if ($tendenciaMacrorregion) {
                $series[] = [
                    'name' => 'Promedio Macrorregional',
                    'type' => 'line',
                    'data' => collect($tendencia)->map(fn($t) => $tendenciaMacrorregion[$t['anio']] ?? null)->toArray(),
                    'smooth' => true,
                    'lineStyle' => ['type' => 'dotted', 'width' => 2, 'opacity' => 0.7],
                    'itemStyle' => ['opacity' => 0.7]
                ];
            }

            if ($tendenciaEstado) {
                $series[] = [
                    'name' => 'Promedio Estatal',
                    'type' => 'line',
                    'data' => collect($tendencia)->map(fn($t) => $tendenciaEstado[$t['anio']] ?? null)->toArray(),
                    'smooth' => true,
                    'lineStyle' => ['type' => 'dashed', 'width' => 2, 'opacity' => 0.6],
                    'itemStyle' => ['opacity' => 0.6]
                ];
            }

            return [
                'type' => 'line',
                'eje_x' => [
                    'categorias' => collect($tendencia)->pluck('anio')->toArray()
                ],
                'series' => $series
            ];
        }

        if ($tipo_visualizacion === 'scatter' && $variableIds && count($variableIds) >= 2 && $anio) {
            $var1Id = $variableIds[0];
            $var2Id = $variableIds[1];

            $datosVar1 = DatoHistorico::where('variable_id', $var1Id)->where('anio', $anio)->get()->keyBy('municipio_id');
            $datosVar2 = DatoHistorico::where('variable_id', $var2Id)->where('anio', $anio)->get()->keyBy('municipio_id');

            $municipiosIds = $datosVar1->keys()->intersect($datosVar2->keys());
            $municipios = Municipio::whereIn('id', $municipiosIds)->get()->keyBy('id');

            $data = [];
            foreach ($municipiosIds as $mId) {
                $mNombre = $municipios->get($mId)->nombre ?? 'Desconocido';
                $val1 = (float)$datosVar1->get($mId)->valor;
                $val2 = (float)$datosVar2->get($mId)->valor;
                // Formato que perfil.js espera: [xVal, yVal, munName, municipio_id]
                $data[] = [$val1, $val2, $mNombre, $mId];
            }

            return [
                'type' => 'scatter',
                'series' => [
                    [
                        'data' => $data,
                        'symbolSize' => 12,
                        'itemStyle' => [
                            // En ECharts, la función de color puede recibir params en frontend, pero 
                            // como pasamos JSON no podemos pasar funciones directas.
                            // Dejamos que el frontend lo maneje si queremos, o definimos color estándar aquí.
                            'color' => '#861e34'
                        ]
                    ]
                ],
                'eje_x' => ['titulo' => Variable::find($var1Id)->nombre_amigable ?? 'Variable X'],
                'eje_y' => ['titulo' => Variable::find($var2Id)->nombre_amigable ?? 'Variable Y']
            ];
        }

        if ($tipo_visualizacion === 'mapa' && $variableIds && $anio) {
            $statsMunicipios = DatoHistorico::select('municipio_id', DB::raw('SUM(valor) as value'))
                ->whereIn('variable_id', $variableIds)
                ->where('anio', $anio)
                ->groupBy('municipio_id')
                ->get();

            $municipios = Municipio::whereIn('id', $statsMunicipios->pluck('municipio_id'))->get()->keyBy('id');

            $data = $statsMunicipios->map(function ($m) use ($municipios) {
                $mun = $municipios->get($m->municipio_id);
                return [
                    'name' => $mun ? $mun->nombre : 'Desconocido',
                    'value' => (float)$m->value
                ];
            });

            return [
                'type' => 'map',
                'data' => $data,
                'min' => $data->min('value'),
                'max' => $data->max('value')
            ];
        }

        if ($tipo_visualizacion === 'treemap' || $tipo_visualizacion === 'pie' || $tipo_visualizacion === 'donut') {
            $data = [];
            foreach ($variablesArray as $v) {
                $data[] = [
                    'name' => $v['nombre'],
                    'value' => (float) $v['valor']
                ];
            }
            return [
                'type' => $tipo_visualizacion,
                'series' => [['type' => $tipo_visualizacion, 'data' => $data]],
                'eje_x' => null
            ];
        }

        if ($tipoNorm === 'bar' || $tipoNorm === 'line' || $tipoNorm === 'area') {
            $categorias = [];
            $valores = [];
            $valoresMacrorregion = [];
            $valoresEstado = [];
            foreach ($variablesArray as $v) {
                $categorias[] = $v['nombre'];
                $valores[] = (float) $v['valor'];
                if (isset($v['promedio_macrorregional']) && $v['promedio_macrorregional'] !== null) {
                    $valoresMacrorregion[] = (float) $v['promedio_macrorregional'];
                }
                if (isset($v['promedio_estatal']) && $v['promedio_estatal'] !== null) {
                    $valoresEstado[] = (float) $v['promedio_estatal'];
                }
            }

            $series = [['name' => 'Municipio', 'type' => $tipoNorm, 'data' => $valores]];
            if (!empty($valoresMacrorregion)) {
                $series[] = [
                    'name' => 'Promedio Macrorregional',
                    'type' => $tipoNorm,
                    'data' => $valoresMacrorregion,
                    'itemStyle' => ['opacity' => 0.7],
                    'barGap' => '10%'
                ];
            }
            if (!empty($valoresEstado)) {
                $series[] = [
                    'name' => 'Promedio Estatal',
                    'type' => $tipoNorm,
                    'data' => $valoresEstado,
                    'itemStyle' => ['opacity' => 0.4],
                    'barGap' => '10%'
                ];
            }

            return [
                'type' => $tipoNorm,
                'series' => $series,
                'eje_x' => ['categorias' => $categorias]
            ];
        }

        return null;
    }

    /**
     * Calcula la posición (ranking) del municipio en comparación con todos los demás municipios
     * para un grupo específico de variables en un año determinado (usando base de datos).
     *
     * @param  array|\Illuminate\Support\Collection  $variableIds Identificadores de las variables.
     * @param  int|string  $municipio_id Identificador del municipio a evaluar.
     * @param  int  $anio Año de comparación.
     * @return array{posicion: int|string, total_municipios: int} Posición en el ranking y total de municipios con datos.
     */
    private function getMunicipalityRanking($variableIds, $municipio_id, $anio)
    {

        // Obtenemos los totales por municipio para este indicador y año
        $rankings = DatoHistorico::select('municipio_id', DB::raw('SUM(valor) as total'))
            ->whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->groupBy('municipio_id')
            ->orderBy('total', 'desc')
            ->get();

        $posicion = $rankings->search(fn($r) => $r->municipio_id == $municipio_id);

        return [
            'posicion' => $posicion !== false ? $posicion + 1 : 'N/D',
            'total_municipios' => $rankings->count()
        ];
    }

    /**
     * Obtiene el promedio o suma total estatal para un conjunto de variables en un año específico (usando base de datos).
     *
     * @param  array|\Illuminate\Support\Collection  $variableIds Identificadores de las variables.
     * @param  int  $anio Año a evaluar.
     * @param  string  $method Método de agregación ('avg' o 'sum').
     * @return float|int Valor agregado resultante.
     */
    private function getStateAverage($variableIds, $anio, $method = 'avg')
    {
        $method = in_array($method, ['avg', 'sum']) ? $method : 'avg';
        return DatoHistorico::whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->$method('valor') ?? 0;
    }

    /**
     * Obtiene el promedio o suma total para la macrorregión a la que pertenece el municipio
     * para un conjunto de variables en un año específico (usando base de datos).
     *
     * @param  array|\Illuminate\Support\Collection  $variableIds Identificadores de las variables.
     * @param  \App\Models\Municipio  $municipio Municipio base para determinar la macrorregión.
     * @param  int  $anio Año a evaluar.
     * @param  string  $method Método de agregación ('avg' o 'sum').
     * @return float|int Valor agregado regional resultante.
     */
    private function getMacrorregionalAverage($variableIds, $municipio, $anio, $method = 'avg')
    {
        $macrorregionId = $municipio->microrregion->macrorregion_id ?? null;
        if (!$macrorregionId) return 0;

        $municipiosIds = Municipio::whereHas('microrregion', function($q) use ($macrorregionId) {
            $q->where('macrorregion_id', $macrorregionId);
        })->pluck('id');

        $method = in_array($method, ['avg', 'sum']) ? $method : 'avg';
        return DatoHistorico::whereIn('variable_id', $variableIds)
            ->whereIn('municipio_id', $municipiosIds)
            ->where('anio', $anio)
            ->$method('valor') ?? 0;
    }

    /**
     * Calcula la posición (ranking) del municipio en comparación con todos los demás municipios
     * para un grupo específico de variables en un año determinado utilizando una colección cargada en memoria.
     * Esto optimiza significativamente el rendimiento al no realizar consultas sql repetidas.
     *
     * @param  \App\Services\FichaDataStore  $dataStore Almacén de datos en memoria.
     * @param  array|\Illuminate\Support\Collection  $variableIds Identificadores de las variables.
     * @param  int|string  $municipio_id Identificador del municipio a evaluar.
     * @param  int  $anio Año de comparación.
     * @return array{posicion: int|string, total_municipios: int} Posición en el ranking y total de municipios.
     */
    private function getMunicipalityRankingInMemory($dataStore, $variableIds, $municipio_id, $anio)
    {
        $rankings = $dataStore->globalData
            ->whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->groupBy('municipio_id')
            ->map(fn($group) => $group->sum('valor'))
            ->sortDesc()
            ->keys();

        $posicion = $rankings->search($municipio_id);

        return [
            'posicion' => $posicion !== false ? $posicion + 1 : 'N/D',
            'total_municipios' => $rankings->count()
        ];
    }

    /**
     * Obtiene el promedio o suma total estatal para un conjunto de variables en un año específico
     * utilizando una colección cargada en memoria.
     *
     * @param  \App\Services\FichaDataStore  $dataStore Almacén de datos en memoria.
     * @param  array|\Illuminate\Support\Collection  $variableIds Identificadores de las variables.
     * @param  int  $anio Año a evaluar.
     * @param  string  $method Método de agregación ('avg' o 'sum').
     * @return float|int Valor estatal resultante.
     */
    private function getStateAverageInMemory($dataStore, $variableIds, $anio, $method = 'avg')
    {
        $filtered = $dataStore->globalData
            ->whereIn('variable_id', $variableIds)
            ->where('anio', $anio);
        
        return $method === 'sum' ? $filtered->sum('valor') : $filtered->avg('valor');
    }

    /**
     * Obtiene el promedio o suma total para la macrorregión
     * para un conjunto de variables en un año específico utilizando una colección cargada en memoria.
     *
     * @param  \App\Services\FichaDataStore  $dataStore Almacén de datos en memoria.
     * @param  array|\Illuminate\Support\Collection  $variableIds Identificadores de las variables.
     * @param  \Illuminate\Support\Collection  $municipiosIds Colección de IDs de municipios de la macrorregión.
     * @param  int  $anio Año a evaluar.
     * @param  string  $method Método de agregación ('avg' o 'sum').
     * @return float|int Valor regional resultante.
     */
    private function getMacrorregionalAverageInMemory($dataStore, $variableIds, $municipiosIds, $anio, $method = 'avg')
    {
        if ($municipiosIds->isEmpty()) return 0;
        
        $filtered = $dataStore->globalData
            ->whereIn('variable_id', $variableIds)
            ->whereIn('municipio_id', $municipiosIds)
            ->where('anio', $anio);
        
        return $method === 'sum' ? $filtered->sum('valor') : $filtered->avg('valor');
    }

    /**
     * Procesa la plantilla de narrativa utilizando el servicio FichaNarratorService.
     * Reemplaza los tokens de datos e información dinámica del municipio en el texto descriptivo.
     *
     * @param  string  $plantilla Texto de la plantilla con tokens.
     * @param  \App\Models\Municipio  $municipio Municipio del cual obtener contexto.
     * @param  array  $datos Datos del indicador asociados.
     * @return string Narrativa final en lenguaje natural procesada.
     */
    private function procesarNarrativa($plantilla, $municipio, $datos)
    {
        return FichaNarratorService::procesar($plantilla, $municipio, $datos);
    }

    /**
     * Obtiene un resumen breve de Wikipedia para el municipio.
     * Implementa caché por 7 días y lógica de fallback para nombres comunes.
     */
    private function getWikipediaSummary(string $nombre): ?array
    {
        $cacheKey = 'wiki_summary_' . Str::slug($nombre);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $slug = str_replace(' ', '_', $nombre);

        $intentos = [
            "Municipio_de_{$slug}_(Puebla)",
            "{$slug}_(Puebla)",
            "Municipio_de_{$slug}",
            "{$slug},_Puebla",
        ];
        $resultado = null;

        foreach ($intentos as $titulo) {

            try {
                $response = $this->wikipediaClient()->get(
                    "https://es.wikipedia.org/api/rest_v1/page/summary/{$titulo}"
                );

                Log::info("Wikipedia [{$titulo}]: status={$response->status()}, type=" . $response->json('type'));

                if ($response->successful() && $response->json('type') !== 'disambiguation') {
                    $resultado = $response->json();
                    break;
                }
            } catch (\Exception $e) {
                Log::warning("Wikipedia timeout para '{$titulo}': " . $e->getMessage());
            }
        }

        // ponytail: habilitar caché para no re-consultar el API externa de Wikipedia constantemente
        $ttl = $resultado ? now()->addDays(7) : now()->addHours(6);
        Cache::put($cacheKey, $resultado, $ttl);

        return $resultado;
    }

    /**
     * Instancia y configura el cliente HTTP para interactuar con la API REST de Wikipedia.
     * Define un tiempo de espera límite (timeout) y el encabezado User-Agent.
     *
     * @return \Illuminate\Http\Client\PendingRequest Cliente HTTP configurado.
     */
    private function wikipediaClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(5)->withHeaders([
            'User-Agent' => 'PortalMunicipalPuebla/1.0 (nery.pozos@puebla.gob.mx)'
        ]);
    }

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

    /**
     * Muestra la vista comparativa entre dos municipios.
     * Carga las estadísticas del Hero de ambos municipios y cruza toda la configuración
     * de fichas activas utilizando almacenes de datos en memoria para optimizar la carga.
     *
     * @param  string  $slug1 Slug identificador del primer municipio.
     * @param  string  $slug2 Slug identificador del segundo municipio.
     * @return \Illuminate\View\View Vista comparativa municipal.
     */
    public function compararMunicipal($slug1, $slug2)
    {
        $municipio1 = Municipio::where('slug', $slug1)->firstOrFail();
        $municipio2 = Municipio::where('slug', $slug2)->firstOrFail();

        $municipio1->load('microrregion.macrorregion');
        $municipio2->load('microrregion.macrorregion');

        $hero1 = $this->getHeroStats($municipio1);
        $hero2 = $this->getHeroStats($municipio2);

        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->where('activo', true)
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get();

        $allVariableIds = FichaDataStore::extractVariableIds($configuraciones);
        $globalData = \Illuminate\Support\Facades\DB::table('dato_historicos')
            ->whereIn('variable_id', $allVariableIds)
            ->select('municipio_id', 'variable_id', 'anio', 'valor')
            ->get();

        $dataStore1 = new FichaDataStore($municipio1, $allVariableIds, $globalData);
        $dataStore2 = new FichaDataStore($municipio2, $allVariableIds, $globalData);

        $comparativa = [];

        foreach ($configuraciones as $config) {
            $datos1 = $this->obtenerDatosParaConfig($config, $municipio1, $dataStore1);
            $datos2 = $this->obtenerDatosParaConfig($config, $municipio2, $dataStore2);

            $combinadoECharts = $this->combinarDatosParaECharts($config, $datos1, $datos2, $municipio1, $municipio2);

            $dimension = $config->indicador->tematica->dimension->nombre ?? 'Sin Dimensión';
            $dimensionKey = str_replace(' ', '_', strtolower($dimension));

            $comparativa[$dimensionKey][] = [
                'config' => $config,
                'datos1' => $datos1,
                'datos2' => $datos2,
                'echarts_combinado' => $combinadoECharts
            ];
        }

        $todosMunicipios = Municipio::orderBy('nombre', 'asc')->get();

        return view('municipios.comparar', compact(
            'municipio1', 'municipio2',
            'hero1', 'hero2',
            'comparativa',
            'todosMunicipios'
        ));
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
    private function combinarDatosParaECharts($config, $datos1, $datos2, $municipio1, $municipio2)
    {
        if (!$datos1 || !$datos2) return null;

        $tipo_visualizacion = $config->tipo_visualizacion;
        $tipoLower = strtolower($tipo_visualizacion);
        $tipoNorm = $tipoLower;
        if ($tipoLower === 'barras') {
            $tipoNorm = 'bar';
        } elseif ($tipoLower === 'lineas' || $tipoLower === 'líneas') {
            $tipoNorm = 'line';
        }

        if (($tipoNorm === 'line') && isset($datos1['tendencia']) && !empty($datos1['tendencia'])) {
            $tendencia1 = $datos1['tendencia'];
            $tendencia2 = $datos2['tendencia'];

            $anios = collect(array_merge($tendencia1, $tendencia2))->pluck('anio')->unique()->sort()->values()->toArray();

            $valores1 = [];
            $valores2 = [];
            foreach ($anios as $anio) {
                $item1 = collect($tendencia1)->firstWhere('anio', $anio);
                $item2 = collect($tendencia2)->firstWhere('anio', $anio);
                $valores1[] = $item1 ? (float)$item1['valor'] : null;
                $valores2[] = $item2 ? (float)$item2['valor'] : null;
            }

            return [
                'type' => 'line',
                'eje_x' => ['categorias' => $anios],
                'series' => [
                    [
                        'name' => $municipio1->nombre,
                        'type' => 'line',
                        'data' => $valores1,
                        'smooth' => true,
                        'symbol' => 'circle',
                        'symbolSize' => 8,
                    ],
                    [
                        'name' => $municipio2->nombre,
                        'type' => 'line',
                        'data' => $valores2,
                        'smooth' => true,
                        'symbol' => 'circle',
                        'symbolSize' => 8,
                    ]
                ]
            ];
        }

        if ($tipoNorm === 'bar' || $tipoNorm === 'line' || $tipoNorm === 'area') {
            $vars1 = $datos1['variables'] ?? [];
            $vars2 = $datos2['variables'] ?? [];

            $categorias = [];
            $valores1 = [];
            $valores2 = [];

            foreach ($vars1 as $v1) {
                $categorias[] = $v1['nombre'];
                $valores1[] = (float)$v1['valor'];

                $v2 = collect($vars2)->firstWhere('nombre', $v1['nombre']);
                $valores2[] = $v2 ? (float)$v2['valor'] : 0;
            }

            return [
                'type' => $tipoNorm,
                'eje_x' => ['categorias' => $categorias],
                'series' => [
                    [
                        'name' => $municipio1->nombre,
                        'type' => $tipoNorm,
                        'data' => $valores1,
                        'barGap' => '10%'
                    ],
                    [
                        'name' => $municipio2->nombre,
                        'type' => $tipoNorm,
                        'data' => $valores2,
                        'barGap' => '10%'
                    ]
                ]
            ];
        }

        return null;
    }

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
        $municipio1 = Municipio::where('slug', $slug1)->firstOrFail();
        $municipio2 = Municipio::where('slug', $slug2)->firstOrFail();

        $municipio1->load('microrregion.macrorregion');
        $municipio2->load('microrregion.macrorregion');

        $hero1 = $this->getHeroStats($municipio1);
        $hero2 = $this->getHeroStats($municipio2);

        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->where('activo', true)
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get();

        $allVariableIds = FichaDataStore::extractVariableIds($configuraciones);
        $globalData = \Illuminate\Support\Facades\DB::table('dato_historicos')
            ->whereIn('variable_id', $allVariableIds)
            ->select('municipio_id', 'variable_id', 'anio', 'valor')
            ->get();

        $dataStore1 = new FichaDataStore($municipio1, $allVariableIds, $globalData);
        $dataStore2 = new FichaDataStore($municipio2, $allVariableIds, $globalData);

        $comparativa = [];

        foreach ($configuraciones as $config) {
            $datos1 = $this->obtenerDatosParaConfig($config, $municipio1, $dataStore1);
            $datos2 = $this->obtenerDatosParaConfig($config, $municipio2, $dataStore2);

            $dimension = $config->indicador->tematica->dimension->nombre ?? 'Sin Dimensión';
            $dimensionKey = str_replace(' ', '_', strtolower($dimension));

            $comparativa[$dimensionKey][] = [
                'config' => $config,
                'datos1' => $datos1,
                'datos2' => $datos2
            ];
        }

        $pdf = PDF::loadView('municipios.comparar_pdf', compact(
            'municipio1', 'municipio2',
            'hero1', 'hero2',
            'comparativa',
            'configuraciones'
        ));

        $fileName = 'comparativa-' . Str::slug($municipio1->nombre) . '-vs-' . Str::slug($municipio2->nombre) . '.pdf';
        return $pdf->download($fileName);
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
        $config = ConfiguracionFicha::with(['indicador.variables', 'variables'])->find($configId);
        if (!$config) {
            return response()->json(['success' => false, 'error' => 'Configuración no encontrada'], 404);
        }

        // Obtener datos específicos forzando el año
        $datos = $this->obtenerDatosParaConfig($config, $municipio, null, (int)$anio);

        if (!$datos) {
            return response()->json(['success' => false, 'error' => 'No hay datos para el año especificado'], 404);
        }

        // Procesar la plantilla de narrativa descriptiva con los nuevos datos obtenidos
        $narrativa = $this->procesarNarrativa($config->plantilla_narrativa, $municipio, $datos);

        return response()->json([
            'success' => true,
            'datos' => $datos,
            'narrativa' => $narrativa
        ]);
    }
}
