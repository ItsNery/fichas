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

    // public function getChartData(array $validated)
    // {
    //     $indicador = Indicador::with('variables')->find($validated['indicador_id']);
    //     $nivel     = $validated['nivel_de_agregacion'];

    //     $selection = $this->prepareGeographicSelection($nivel, $validated);

    //     // 3. Dirige la petición al método correspondiente
    //     if (
    //         $indicador->id == 2 &&
    //         (($nivel === 'municipio' && count($validated['municipio_ids'] ?? []) === 1) || in_array($nivel, ['microrregion', 'macrorregion']))
    //     ) {
    //         $chartData = $this->handlePiramideChart($indicador, $selection);
    //     } elseif ($nivel === 'municipio' && count($selection['ids']) > 1) {
    //         $chartData = $this->handleComparativeView($validated, $indicador, $selection);
    //     } else {
    //         $chartData = $this->handleAggregatedView($nivel, $validated, $indicador, $selection);
    //     }

    //     // Devuelve el array de datos del gráfico
    //     return $chartData;
    // }

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
        foreach ($mapaPiramide as $grupo) {
            // Hombres
            $varHom = $variables->get($grupo['hom']);
            if ($varHom && (!$variableIds || in_array($varHom->id, $variableIds))) {
                $query = DatoHistorico::where('variable_id', $varHom->id)->where('anio', $anioConsulta);
                if (!in_array('estatal', $municipioIds)) {
                    $query->whereIn('municipio_id', $municipioIds);
                }
                $hombresData[] = -$query->sum('valor');
            } else {
                $hombresData[] = 0;
            }

            // Mujeres
            $varMuj = $variables->get($grupo['muj']);
            if ($varMuj && (!$variableIds || in_array($varMuj->id, $variableIds))) {
                $query = DatoHistorico::where('variable_id', $varMuj->id)->where('anio', $anioConsulta);
                if (!in_array('estatal', $municipioIds)) {
                    $query->whereIn('municipio_id', $municipioIds);
                }
                $mujeresData[] = (float) $query->sum('valor');
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
                // $seriesParaGrafico[] = ['name' => $nombresMunicipios[$munId], 'data' => $dataPoints];
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
                // 'eje_x'           => ['categorias' => array_values($nombresMunicipios)],
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
        // if ($nivel !== 'municipio' || in_array('estatal', $selection['ids'])) {
        //     $variableTotal = $indicador->variables->first(function ($variable) {
        //         return str_contains(mb_strtolower($variable->nombre_amigable, 'UTF-8'), 'total');
        //     });

        //     if ($variableTotal) {
        //         $variablesParaProcesar = collect([$variableTotal]);
        //     }
        // }

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

    public function exportarResumenPDFOLD(Municipio $municipio)
    {
        // Reutilizamos la misma lógica que en 'resumenMunicipal' para obtener los datos
        $variablesKPI   = Variable::with('indicador.tematica.dimension')->where('es_kpi', true)->get();
        $datosAgrupados = [];
        foreach ($variablesKPI as $variable) {
            $dato = DatoHistorico::where('variable_id', $variable->id)
                ->where('municipio_id', $municipio->id)
                ->orderBy('anio', 'desc')->first();

            $dimensionNombre = $variable->indicador->tematica->dimension->nombre;
            $tematicaNombre  = $variable->indicador->tematica->nombre;

            $datosAgrupados[$dimensionNombre][$tematicaNombre][] = [
                'nombre'        => $variable->nombre_amigable,
                'valor_display' => $dato ? $dato->valor_display : 'N/D',
                'anio'          => $dato->anio ?? 'N/D',
                'unidad'        => $variable->unidad_medida,
            ];
        }

        // Cargamos la vista del PDF con los datos
        $pdf = PDF::loadView('municipios.resumen_pdf', compact('municipio', 'datosAgrupados'));

        // Generamos un nombre de archivo dinámico y lo ofrecemos para descarga
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
        // $instrumentos   = $municipio->instrumentos()->orderBy('anio', 'desc')->get();
        // --- FIN DE LA NUEVA LÓGICA DE AGRUPACIÓN ---

        return view('municipios.resumen', [
            'municipio'      => $municipio,
            'datosAgrupados' => $datosAgrupados,
        ]);
    }

    public function resumenMunicipalV3(Municipio $municipio)
    {
        // 1. Datos del Hero (Población, Marginación, etc.)
        $poblacionTotal = 0;
        $varPob = Variable::where('nombre_amigable', 'Población total')
            ->whereHas('indicador', fn($q) => $q->where('nombre_amigable', 'Población total según sexo'))
            ->first();
        if ($varPob) {
            $datoPob = DatoHistorico::where('variable_id', $varPob->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            $poblacionTotal = $datoPob->valor ?? 0;
        }

        $gradoMarginacion = 'N/D';
        $varMarg = Variable::where('nombre_amigable', 'Grado de Marginación')->first();
        if ($varMarg) {
            $datoMarg = DatoHistorico::where('variable_id', $varMarg->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            $gradoMarginacion = $datoMarg->valor_display ?? 'N/D';
        }

        $superficieKm2 = 0;
        $varSup = Variable::where('nombre_amigable', 'Superficie territorial (Hectáreas)')->first();
        if ($varSup) {
            $datoSup = DatoHistorico::where('variable_id', $varSup->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            if ($datoSup && $datoSup->valor > 0) {
                $superficieKm2 = $datoSup->valor / 100;
            }
        }

        $presupuestoTotal = 0;
        $anioPresupuesto = 'N/D';
        $indicadorPresupuesto = Indicador::where('nombre_amigable', 'Recursos federales transferidos al municipio (FORTAMUN y FAISMUN) en miles de pesos')->first();
        if ($indicadorPresupuesto) {
            $variablesPresupuesto = Variable::where('indicador_id', $indicadorPresupuesto->id)
                ->whereIn('nombre_amigable', ['Faismun aprobado', 'Fortamun aprobado'])
                ->get();
            foreach ($variablesPresupuesto as $v) {
                $ultimoDato = DatoHistorico::where('variable_id', $v->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
                if ($ultimoDato) {
                    $presupuestoTotal += $ultimoDato->valor;
                    $anioPresupuesto = $ultimoDato->anio;
                }
            }
        }

        // 2. Carga de Wikipedia
        $wikiSummary = $this->getWikipediaSummary($municipio);

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
                'valor_display'    => is_array($datos) ? (isset($datos['total']) ? number_format($datos['total']) : 'N/D') : ($datos ?? 'N/D'),
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

    public function resumenMunicipalTest(Municipio $municipio)
    {
        // --- CÁLCULO DE DATOS PARA EL HERO ---

        // 1. Población Total
        $poblacionTotal = 0;
        $varPob = Variable::where('nombre_amigable', 'Población total')
            ->whereHas('indicador', fn($q) => $q->where('nombre_amigable', 'Población total según sexo'))
            ->first();
        if ($varPob) {
            $datoPob = DatoHistorico::where('variable_id', $varPob->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            $poblacionTotal = $datoPob->valor ?? 0;
        }

        // 2. Grado de Marginación
        $gradoMarginacion = 'N/D';
        $varMarg = Variable::where('nombre_amigable', 'Grado de Marginación')->first();
        if ($varMarg) {
            $datoMarg = DatoHistorico::where('variable_id', $varMarg->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            $gradoMarginacion = $datoMarg->valor_display ?? 'N/D';
        }

        // 3. Presupuesto
        $indicadorPresupuesto = Indicador::where('nombre_amigable', 'Recursos federales transferidos al municipio (FORTAMUN y FAISMUN) en miles de pesos')->first();
        $presupuestoTotal = 0;
        $anioPresupuesto = 'N/D';

        if ($indicadorPresupuesto) {
            $variablesPresupuesto = Variable::where('indicador_id', $indicadorPresupuesto->id)
                ->whereIn('nombre_amigable', ['Faismun aprobado', 'Fortamun aprobado'])
                ->get();

            foreach ($variablesPresupuesto as $v) {
                $ultimoDato = DatoHistorico::where('variable_id', $v->id)
                    ->where('municipio_id', $municipio->id)
                    ->orderBy('anio', 'desc')
                    ->first();
                if ($ultimoDato) {
                    $presupuestoTotal += $ultimoDato->valor;
                    $anioPresupuesto = $ultimoDato->anio;
                }
            }
        }

        // 4. Superficie Territorial
        $superficieKm2 = 0;
        $varSup = Variable::where('nombre_amigable', 'Superficie territorial (Hectáreas)')->first();
        if ($varSup) {
            $datoSup = DatoHistorico::where('variable_id', $varSup->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            if ($datoSup && $datoSup->valor > 0) {
                $superficieKm2 = $datoSup->valor / 100; // Convertimos hectáreas a km²
            }
        }

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

    public function directorioVisual()
    {
        $macrorregiones = Macrorregion::with(['microrregiones.municipios' => function ($q) {
            $q->orderBy('nombre', 'asc');
        }])->orderBy('id', 'asc')->get();

        return view('municipios.directorio', compact('macrorregiones'));
    }

    /**
     * Processes complex indicator data for charting, handling two main scenarios:
     * 1) Comparison between two selected municipalities (Bar Chart for a single year).
     * 2) Aggregated view for a single area (Line Chart for historical trend or Bar Chart for single-year breakdown).
     *
     * @param  array  $validated  The validated input parameters (anios, nivel_de_agregacion, etc.).
     * @param  \App\Models\Indicador  $indicador // The complex Indicador model instance.
     * @param  array{ids: array<int|string>, titulo: string}  $selection // The prepared geographic selection (Municipio IDs and title).
     * @return array
     */
    private function handleComplexIndicatorView(array $validated, Indicador $indicador, array $selection)
    {
        $selectedYears = $validated['anios'] ?? [];
        $selectionIds  = $selection['ids'];
        $nivel         = $validated['nivel_de_agregacion'];

        // --- CASO A: COMPARACIÓN DE DOS MUNICIPIOS ---
        if ($nivel === 'municipio' && count($selectionIds) === 2) {
            $nombresMunicipios = Municipio::whereIn('id', $selectionIds)->pluck('nombre', 'id');
            $availableYears    = $this->getAvailableYearsForComplex($indicador, $selectionIds, 2);
            $anio              = ! empty($selectedYears) ? $selectedYears[0] : $availableYears->first();

            if (! $anio) {
                return ['titulo' => "{$indicador->nombre_amigable} (Sin años en común para comparar)", 'series' => []];
            }

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

            return [
                'titulo' => "{$indicador->nombre_amigable} - Comparación (Año: {$anio})",
                'tipo_grafico' => 'bar',
                'series' => [['name' => $nombresMunicipios[$selectionIds[0]], 'data' => $serieA_valores], ['name' => $nombresMunicipios[$selectionIds[1]], 'data' => $serieB_valores]],
                'eje_x' => ['categorias' => $todosLosCultivos],
                'eje_y' => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
                'available_years' => $availableYears,
                'selected_years' => [$anio],
                'descripcion'                                          => $indicador->descripcion,
                'fuente' => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
            ];
        }
        // --- CASO B: VISTA ÚNICA (MUNICIPIO, REGIÓN O ESTATAL) ---
        else {
            $availableYears = $this->getAvailableYearsForComplex($indicador, $selectionIds);
            $yearsToUse     = ! empty($selectedYears) ? $selectedYears : $availableYears->all();

            if (empty($yearsToUse)) {
                return ['titulo' => "{$indicador->nombre_amigable} - {$selection['titulo']} (Sin Datos)", 'series' => []];
            }

            if (count($yearsToUse) > 1) { // Gráfico de Líneas
                $query = DatoIndicadorComplejo::where('indicador_id', $indicador->id)->whereIn('anio', $yearsToUse);
                if (! in_array('estatal', $selectionIds)) {
                    $query->whereIn('municipio_id', $selectionIds);
                }

                $datosMultiAnio = $query->orderBy('anio', 'asc')->get();

                $seriesData = [];
                foreach ($datosMultiAnio as $registro) {
                    $datosArray = is_array($registro->datos) ? $registro->datos : json_decode($registro->datos, true);
                    $anioActual = (int) $registro->anio;
                    foreach ($datosArray as $cultivo => $valor) {
                        if (! isset($seriesData[$cultivo])) {
                            $seriesData[$cultivo] = [];
                        }

                        if (! isset($seriesData[$cultivo][$anioActual])) {
                            $seriesData[$cultivo][$anioActual] = 0;
                        }

                        $seriesData[$cultivo][$anioActual] += (float) $valor;
                    }
                }

                $seriesFinales = [];
                foreach ($seriesData as $cultivo => $datosAnuales) {
                    $dataPoints = [];
                    ksort($datosAnuales);
                    foreach ($datosAnuales as $anio => $valorTotal) {
                        $dataPoints[] = [$anio, $valorTotal];
                    }

                    $seriesFinales[] = ['name' => $cultivo, 'data' => $dataPoints];
                }
                return ['titulo' => "{$indicador->nombre_amigable} - {$selection['titulo']} (Histórico)", 'tipo_grafico' => 'line', 'series' => $seriesFinales, 'eje_x' => ['type' => 'numeric', 'titulo' => 'Año'], 'eje_y' => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'], 'available_years' => $availableYears, 'selected_years' => $yearsToUse, 'descripcion' => $indicador->descripcion, 'fuente' => $indicador->fuente, 'metodo_calculo' => $indicador->metodo_calculo];
            } else { // Gráfico de Barras
                $anio       = $yearsToUse[0];
                $queryDatos = DatoIndicadorComplejo::where('indicador_id', $indicador->id)->where('anio', $anio);
                if (! in_array('estatal', $selectionIds)) {
                    $queryDatos->whereIn('municipio_id', $selectionIds);
                }

                $datosComplejos = $queryDatos->get();

                $datosAgregados = [];
                foreach ($datosComplejos as $registro) {
                    $datosArray = is_array($registro->datos) ? $registro->datos : json_decode($registro->datos, true);
                    foreach ($datosArray as $cultivo => $valor) {
                        if (! isset($datosAgregados[$cultivo])) {
                            $datosAgregados[$cultivo] = 0;
                        }

                        $datosAgregados[$cultivo] += $valor;
                    }
                }
                arsort($datosAgregados);

                return ['titulo' => "{$indicador->nombre_amigable} - {$selection['titulo']} (Año: {$anio})", 'tipo_grafico' => 'bar', 'series' => [['name' => 'Producción', 'data' => array_values($datosAgregados)]], 'eje_x' => ['categorias' => array_keys($datosAgregados)], 'eje_y' => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'], 'available_years' => $availableYears, 'selected_years' => [$anio], 'descripcion' => $indicador->descripcion, 'fuente' => $indicador->fuente, 'metodo_calculo' => $indicador->metodo_calculo];
            }
        }
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

    public function perfilMunicipal(Municipio $municipio)
    {
        $municipio->load('microrregion.macrorregion');

        // --- CÁLCULO DE DATOS PARA EL HERO (Igual que en resumen-test) ---
        $poblacionTotal = 0;
        $varPob = Variable::where('nombre_amigable', 'Población total')
            ->whereHas('indicador', fn($q) => $q->where('nombre_amigable', 'Población total según sexo'))
            ->first();
        if ($varPob) {
            $datoPob = DatoHistorico::where('variable_id', $varPob->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            $poblacionTotal = $datoPob->valor ?? 0;
        }

        $gradoMarginacion = 'N/D';
        $varMarg = Variable::where('nombre_amigable', 'Grado de Marginación')->first();
        if ($varMarg) {
            $datoMarg = DatoHistorico::where('variable_id', $varMarg->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            $gradoMarginacion = $datoMarg->valor_display ?? 'N/D';
        }

        $superficieKm2 = 0;
        $varSup = Variable::where('nombre_amigable', 'Superficie territorial (Hectáreas)')->first();
        if ($varSup) {
            $datoSup = DatoHistorico::where('variable_id', $varSup->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            if ($datoSup && $datoSup->valor > 0) {
                $superficieKm2 = $datoSup->valor / 100;
            }
        }
        $varsPresupuestoIds = Variable::whereIn('nombre_amigable', ['FORTAMUN APROBADO', 'FAISMUN APROBADO'])
            ->pluck('id');

        $ultimoAnioPres = DatoHistorico::whereIn('variable_id', $varsPresupuestoIds)->where('municipio_id', $municipio->id)->max('anio');

        $presupuesto = 0;

        if ($ultimoAnioPres) {
            $presupuesto = DatoHistorico::whereIn('variable_id', $varsPresupuestoIds)
                ->where('municipio_id', $municipio->id)
                ->where('anio', $ultimoAnioPres)
                ->sum('valor');
        }

        // --- Nuevas variables para Hero estilo Data USA ---
        $porcentajePobreza = 'N/D';
        $varPobreza = Variable::where('nombre_amigable', 'Porcentaje de población en situación de pobreza')->first();
        if ($varPobreza) {
            $datoPobreza = DatoHistorico::where('variable_id', $varPobreza->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            if ($datoPobreza && $datoPobreza->valor) {
                $porcentajePobreza = number_format($datoPobreza->valor, 1) . '%';
            }
        }

        $pea = 0;
        $varPea = Variable::where('nombre_amigable', 'Población Económicamente Activa (PEA)')->first();
        if ($varPea) {
            $datoPea = DatoHistorico::where('variable_id', $varPea->id)->where('municipio_id', $municipio->id)->orderBy('anio', 'desc')->first();
            $pea = $datoPea->valor ?? 0;
        }

        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->where('activo', true)
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get();

        $perfil = [];

        foreach ($configuraciones as $config) {
            $datos = $this->obtenerDatosParaConfig($config, $municipio);

            $narrativa = $this->procesarNarrativa($config->plantilla_narrativa, $municipio, $datos);

            $perfil[$config->seccion][] = [
                'config' => $config,
                'datos' => $datos,
                'narrativa' => $narrativa
            ];
        }

        return view('municipios.perfil', compact('municipio', 'perfil', 'poblacionTotal', 'gradoMarginacion', 'superficieKm2', 'presupuesto', 'ultimoAnioPres', 'porcentajePobreza', 'pea'));
    }

    private function obtenerDatosParaConfig($config, $municipio)
    {
        $indicador = $config->indicador;
        $variablesConfig = $config->variables;

        $variableIds = $variablesConfig->isNotEmpty()
            ? $variablesConfig->pluck('id')
            : $indicador->variables->pluck('id');

        $anioMax = DatoHistorico::whereIn('variable_id', $variableIds)
            ->where('municipio_id', $municipio->id)
            ->max('anio');

        if (!$anioMax) return null;

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
        $datos = DatoHistorico::with('variable')
            ->whereIn('variable_id', $variableIds)
            ->where('municipio_id', $municipio->id)
            ->where('anio', $anioMax)
            ->get();

        $valorTotal = $datos->sum('valor');

        // --- Sparkline Data (Tendencia últimos años) ---
        $aniosLimite = $config->anios_historial ?? 5;
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

        // --- Benchmarking (Promedio Estatal) ---
        $esAbsoluto = true;
        foreach ($datos as $d) {
            $u = mb_strtolower($d->variable->unidad_medida ?? '', 'UTF-8');
            if (str_contains($u, '%') || str_contains($u, 'porcentaje') || str_contains($u, 'tasa') || str_contains($u, 'proporción')) {
                $esAbsoluto = false;
                break;
            }
        }

        $promediosEstado = [];
        $tendenciaEstado = [];

        if ($esAbsoluto) {
            foreach ($datos as $d) {
                $promediosEstado[$d->variable_id] = DatoHistorico::where('variable_id', $d->variable_id)
                    ->where('anio', $anioMax)
                    ->avg('valor');
            }

            if (!empty($tendencia)) {
                $anios = collect($tendencia)->pluck('anio')->toArray();
                $tendenciaEstado = DatoHistorico::whereIn('variable_id', $variableIds)
                    ->select('anio', DB::raw('AVG(valor) as total'))
                    ->whereIn('anio', $anios)
                    ->groupBy('anio')
                    ->get()
                    ->keyBy('anio')
                    ->map(fn($t) => (float)$t->total)
                    ->toArray();
            }
        }

        $res = [
            'anio'             => $anioMax,
            'total'            => $valorTotal,
            'valor_actual'     => number_format($valorTotal),
            'ranking'          => $this->getMunicipalityRanking($variableIds, $municipio->id, $anioMax),
            'promedio_estatal' => $this->getStateAverage($variableIds, $anioMax),
            'variables'        => $datos->map(fn($d) => [
                'nombre'           => $d->variable->nombre_amigable,
                'valor'            => $d->valor,
                'unidad'           => $d->variable->unidad_medida,
                'mapeo'            => $d->variable->mapeo_valores,
                'promedio_estatal' => $promediosEstado[$d->variable_id] ?? null
            ])->toArray(),
            'polaridad'      => $indicador->polaridad,
            'descripcion'    => $indicador->descripcion,
            'fuente'         => $indicador->fuente,
            'metodo_calculo' => $indicador->metodo_calculo,
            'tendencia'      => $tendencia,
            'tendencia_estado' => $tendenciaEstado,
        ];

        // --- Aplicar Mapeo Categórico (Prioridad: JSON Config > Variable DB) ---
        $ajustes = $config->ajustes_visuales;
        $mapeo = null;

        if (isset($ajustes['mapping']) && is_array($ajustes['mapping'])) {
            $mapeo = $ajustes['mapping'];
        } elseif (count($res['variables']) === 1 && !empty($res['variables'][0]['mapeo'])) {
            $mapeo = $res['variables'][0]['mapeo'];
        }

        if ($mapeo) {
            $valorOriginal = (string)$res['total'];
            if (isset($mapeo[$valorOriginal])) {
                $res['valor_actual'] = $mapeo[$valorOriginal];
            }

            // Soporte opcional para cambio de icono vía mapeo
            if (isset($ajustes['icons'][$valorOriginal])) {
                $res['icono_actual'] = $ajustes['icons'][$valorOriginal];
            }
        }

        $res['echarts'] = $this->formatearDatosParaECharts(
            $res['variables'],
            $config->tipo_visualizacion,
            $variableIds,
            $anioMax,
            $tendencia,
            $tendenciaEstado
        );

        return $res;
    }

    private function formatearDatosParaECharts(array $variablesArray, string $tipo_visualizacion, $variableIds = null, $anio = null, $tendencia = null, $tendenciaEstado = null)
    {
        $tipoLower = strtolower($tipo_visualizacion);
        if (($tipoLower === 'line' || $tipoLower === 'lineas' || $tipoLower === 'líneas') && $tendencia) {
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

        if ($tipo_visualizacion === 'mapa' && $variableIds && $anio) {
            $statsMunicipios = DatoHistorico::select('municipio_id', DB::raw('SUM(valor) as value'))
                ->whereIn('variable_id', $variableIds)
                ->where('anio', $anio)
                ->groupBy('municipio_id')
                ->get();

            $municipios = \App\Models\Municipio::whereIn('id', $statsMunicipios->pluck('municipio_id'))->get()->keyBy('id');

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

        if ($tipo_visualizacion === 'bar' || $tipo_visualizacion === 'line' || $tipo_visualizacion === 'area') {
            $categorias = [];
            $valores = [];
            $valoresEstado = [];
            foreach ($variablesArray as $v) {
                $categorias[] = $v['nombre'];
                $valores[] = (float) $v['valor'];
                if (isset($v['promedio_estatal']) && $v['promedio_estatal'] !== null) {
                    $valoresEstado[] = (float) $v['promedio_estatal'];
                }
            }

            $series = [['name' => 'Municipio', 'type' => $tipo_visualizacion, 'data' => $valores]];
            if (!empty($valoresEstado)) {
                $series[] = [
                    'name' => 'Promedio Estatal',
                    'type' => $tipo_visualizacion,
                    'data' => $valoresEstado,
                    'itemStyle' => ['opacity' => 0.5],
                    'barGap' => '10%'
                ];
            }

            return [
                'type' => $tipo_visualizacion,
                'series' => $series,
                'eje_x' => ['categorias' => $categorias]
            ];
        }

        return null;
    }

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

    private function getStateAverage($variableIds, $anio)
    {
        return DatoHistorico::whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->avg('valor') ?? 0;
    }

    private function procesarNarrativa($plantilla, $municipio, $datos)
    {
        if (!$plantilla || !$datos) return "";

        $rankingText = isset($datos['ranking'])
            ? "ocupa el lugar <strong>{$datos['ranking']['posicion']}</strong> de {$datos['ranking']['total_municipios']} a nivel estatal"
            : "";

        $promedioText = isset($datos['promedio_estatal'])
            ? "comparado con un promedio estatal de <strong>" . number_format($datos['promedio_estatal']) . "</strong>"
            : "";

        $reemplazos = [
            '{municipio}'        => $municipio->nombre,
            '{anio}'             => $datos['anio'] ?? '',
            '{valor}'            => is_numeric($datos['total'] ?? null) ? "<strong>" . number_format($datos['total']) . "</strong>" : '',
            '{unidad}'           => $datos['variables'][0]['unidad'] ?? '',
            '{ranking}'          => $rankingText,
            '{promedio_estatal}' => $promedioText,
        ];

        // Tags dinámicos por variable
        if (isset($datos['variables']) && is_array($datos['variables'])) {
            foreach ($datos['variables'] as $var) {
                $slug = Str::slug($var['nombre'], '_');
                $reemplazos["{{$slug}_valor}"] = "<strong>" . number_format($var['valor']) . "</strong>";
                $reemplazos["{{$slug}_nombre}"] = $var['nombre'];
            }
        }

        return str_replace(array_keys($reemplazos), array_values($reemplazos), $plantilla);
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

        // $ttl = $resultado ? now()->addDays(7) : now()->addHours(6);
        // Cache::put($cacheKey, $resultado, $ttl);

        return $resultado;
    }

    private function wikipediaClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(5)->withHeaders([
            'User-Agent' => 'PortalMunicipalPuebla/1.0 (nery.pozos@puebla.gob.mx)'
        ]);
    }
}
