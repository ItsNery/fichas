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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'tematicas.indicadores' => function ($query) {
                $query->where('solo_resumen', false);
            },
            'tematicas.indicadores.variables',
        ])->get();

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
    private function handlePiramideChart(Indicador $indicador, array $selection)
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
        $queryAnio = DatoHistorico::whereIn('variable_id', $indicador->variables->pluck('id'));
        if (!in_array('estatal', $municipioIds)) {
            $queryAnio->whereIn('municipio_id', $municipioIds);
        }
        $anioConsulta = $queryAnio->max('anio');

        // 2. Buscamos TODOS los años disponibles para esta selección (para el selector)
        $availableYearsQuery = DatoHistorico::whereIn('variable_id', $indicador->variables->pluck('id'));
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
            if ($varHom) {
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
            if ($varMuj) {
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
                foreach ($indicador->variables as $variable) {
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

            foreach ($indicador->variables->sortBy('nombre_amigable') as $variable) {
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
            return response()->json(['series' => [], 'titulo' => 'Selecciona una opción para continuar.']);
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

        foreach ($variablesParaProcesar->sortBy('id') as $variable) {
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
}
