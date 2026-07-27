<?php

namespace App\Services;

use App\Models\DatoHistorico;
use App\Models\DatoIndicadorComplejo;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Variable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IndicadorQueryService
{
    public function getChartData(array $validated): array
    {
        $indicador = Indicador::visiblePublicamente()
            ->with('variables')
            ->find($validated['indicador_id']);
        if (!$indicador) {
            abort(404, 'Indicador no publicado.');
        }
        $this->usarVariablesPublicas($indicador);
        $nivel     = $validated['nivel_de_agregacion'];
        $selection = $this->prepareGeographicSelection($nivel, $validated);
        $esPiramidePoblacional = str_contains(
            mb_strtolower($indicador->nombre_amigable ?? '', 'UTF-8'),
            'población por grupos de edad'
        );

        if ($indicador->es_complejo) {
            $chartData     = null;
            $selectedYears = $validated['anios'] ?? [];
            $selectionIds  = $selection['ids'];
            $nivel         = $validated['nivel_de_agregacion'];

            if ($nivel === 'municipio' && count($selectionIds) === 2) {
                $nombresMunicipios = Municipio::whereIn('id', $selectionIds)->pluck('nombre', 'id');

                if (count($selectedYears) > 1) {
                    $seriesFinales = [];
                    foreach ($selectionIds as $municipioId) {
                        $datos = DatoIndicadorComplejo::where('indicador_id', $indicador->id)
                            ->where('municipio_id', $municipioId)
                            ->whereIn('anio', $selectedYears)
                            ->orderBy('anio', 'asc')
                            ->get();

                        $dataPoints = $datos->map(function ($registro) {
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
                } else {
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
            } else {
                $availableYears = $this->getAvailableYearsForComplex($indicador, $selectionIds);
                $yearsToUse     = !empty($selectedYears) ? $selectedYears : $availableYears->all();

                if (empty($yearsToUse)) {
                    $chartData = ['titulo' => "{$indicador->nombre_amigable} - {$selection['titulo']} (Sin Datos)", 'series' => []];
                } else {
                    if (count($yearsToUse) > 1) {
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
                    } else {
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

            $chartData['eje_y']          = ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'];
            $chartData['descripcion']    = $indicador->descripcion;
            $chartData['fuente']         = $indicador->fuente;
            $chartData['metodo_calculo'] = $indicador->metodo_calculo;
        } elseif (
            $esPiramidePoblacional &&
            (($nivel === 'municipio' && count($validated['municipio_ids'] ?? []) === 1) || in_array($nivel, ['microrregion', 'macrorregion', 'estatal']))
        ) {
            $chartData = $this->handlePiramideChart($indicador, $selection);
        } elseif ($nivel === 'municipio' && count($selection['ids']) > 1) {
            $chartData = $this->handleComparativeView($validated, $indicador, $selection);
        } else {
            $chartData = $this->handleAggregatedView($nivel, $validated, $indicador, $selection);
        }

        return $chartData;
    }

    public function getIndicatorYears(Indicador $indicador)
    {
        $variableIds = $indicador->variablesPublicas()->pluck('id');

        return DatoHistorico::whereIn('variable_id', $variableIds)
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');
    }

    public function getAniosPorDimension(Dimension $dimension)
    {
        return DatoHistorico::whereHas('variable.indicador.tematica.dimension', function ($query) use ($dimension) {
            $query->where('id', $dimension->id)
                ->where('visible_en_ficha', true);
        })->whereHas('variable', function ($query) {
            $query->where('visible_en_ficha', true)
                ->whereHas('indicador', fn ($indicador) => $indicador->visiblePublicamente());
        })
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');
    }

    public function getAniosPorIndicadorComplejo(Indicador $indicador)
    {
        $this->usarVariablesPublicas($indicador);
        if (!$indicador->es_complejo) {
            return collect();
        }

        return DatoIndicadorComplejo::where('indicador_id', $indicador->id)
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');
    }

    public function prepareGeographicSelection(string $nivel, array $validated): array
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
        } elseif ($nivel === 'estatal') {
            $municipios = Municipio::orderBy('nombre')->get();
            $titulo = 'Estado de Puebla';
            $municipioIds = $municipios->pluck('id')->all();
            $nombresMunicipios = $municipios->pluck('nombre')->all();
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

    public function handlePiramideChart(Indicador $indicador, array $selection, array $variableIds = null): array
    {
        $this->usarVariablesPublicas($indicador);
        $municipioIds    = $selection['ids'];
        $tituloSeleccion = $selection['titulo'];
        $anioConsulta    = null;

        $mapaPiramide = [
            '100 o más años' => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_100_anos_y_mas', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_100_anos_y_mas'],
            '95 a 99 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_95_a_99_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_95_a_99_anos'],
            '90 a 94 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_90_a_94_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_90_a_94_anos'],
            '85 a 89 años'   => ['hom' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_85_a_89_anos', 'muj' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_85_a_89_anos'],
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
        $variables       = Variable::whereIn('nombre_tecnico', $nombresTecnicos)
            ->where('visible_en_ficha', true)
            ->get()
            ->keyBy('nombre_tecnico');
        $hombresData     = [];
        $mujeresData     = [];
        $categorias      = array_keys($mapaPiramide);

        $idsToQuery = $variableIds ?: $indicador->variables->pluck('id')->toArray();

        $queryAnio = DatoHistorico::whereIn('variable_id', $idsToQuery);
        if (!in_array('estatal', $municipioIds)) {
            $queryAnio->whereIn('municipio_id', $municipioIds);
        }
        $anioConsulta = $queryAnio->max('anio');

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
                'available_years' => $availableYears,
                'selected_years'  => [],
            ];
        }

        $varIds = collect();
        foreach ($mapaPiramide as $grupo) {
            $varHom = $variables->get($grupo['hom']);
            $varMuj = $variables->get($grupo['muj']);
            if ($varHom) $varIds->push($varHom->id);
            if ($varMuj) $varIds->push($varMuj->id);
        }

        if ($varIds->isEmpty()) {
            $genericVariables = $indicador->variables
                ->whereIn('id', $idsToQuery)
                ->sortBy(['orden', 'nombre_amigable'])
                ->values();
            $genericYearsQuery = DatoHistorico::whereIn('variable_id', $genericVariables->pluck('id'));
            if (!in_array('estatal', $municipioIds)) {
                $genericYearsQuery->whereIn('municipio_id', $municipioIds);
            }
            $genericYears = $genericYearsQuery->distinct()->orderBy('anio', 'desc')->pluck('anio');
            $genericYear = $genericYears->first();
            $genericDataQuery = DatoHistorico::whereIn('variable_id', $genericVariables->pluck('id'))
                ->where('anio', $genericYear);
            if (!in_array('estatal', $municipioIds)) {
                $genericDataQuery->whereIn('municipio_id', $municipioIds);
            }
            $genericValues = $genericDataQuery->select('variable_id', DB::raw('SUM(valor) as valor'))
                ->groupBy('variable_id')
                ->pluck('valor', 'variable_id');
            $split = (int) ceil($genericVariables->count() / 2);

            return [
                'titulo' => $indicador->nombre_amigable . " - " . $tituloSeleccion . " (" . ($genericYear ?: 'N/D') . ")",
                'descripcion' => $indicador->descripcion,
                'fuente' => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
                'tipo_grafico' => 'piramide',
                'series' => [
                    [
                        'name' => 'Grupo A',
                        'data' => $genericVariables->map(fn($variable, $index) => $index < $split ? -(float) ($genericValues[$variable->id] ?? 0) : 0)->all(),
                    ],
                    [
                        'name' => 'Grupo B',
                        'data' => $genericVariables->map(fn($variable, $index) => $index >= $split ? (float) ($genericValues[$variable->id] ?? 0) : 0)->all(),
                    ],
                ],
                'eje_x' => ['categorias' => $genericVariables->pluck('nombre_amigable')->all()],
                'eje_y' => ['titulo' => $genericVariables->first()->unidad_medida ?? 'Valor'],
                'available_years' => $genericYears,
                'selected_years' => $genericYear ? [$genericYear] : [],
                'anio' => $genericYear,
                'polaridad' => $indicador->polaridad,
            ];
        }

        $queryDatos = DatoHistorico::whereIn('variable_id', $varIds)->where('anio', $anioConsulta);
        if (!in_array('estatal', $municipioIds)) {
            $queryDatos->whereIn('municipio_id', $municipioIds);
        }
        $datosAgregados = $queryDatos->get()->groupBy('variable_id');

        foreach ($mapaPiramide as $grupo) {
            $varHom = $variables->get($grupo['hom']);
            if ($varHom && (!$variableIds || in_array($varHom->id, $variableIds))) {
                $hombresData[] = -(float) $datosAgregados->get($varHom->id, collect())->sum('valor');
            } else {
                $hombresData[] = 0;
            }

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

    public function handleComparativeView(array $validated, Indicador $indicador, array $selection): array
    {
        $this->usarVariablesPublicas($indicador);
        $municipioIds      = $selection['ids'];
        $nombresArray      = Municipio::whereIn('id', $municipioIds)->pluck('nombre', 'id');
        $nombresMunicipios = collect($municipioIds)->map(fn($id) => $nombresArray[$id] ?? 'N/A')->all();
        $variableIds       = $indicador->variables->pluck('id');
        $selectedYears     = $validated['anios'] ?? [];

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

            return [
                'titulo'           => $indicador->nombre_amigable . " (" . $variablePrincipal->nombre_amigable . " - Tendencia Comparativa)",
                'tipo_grafico'     => 'line',
                'series'           => $seriesParaGrafico,
                'available_years'  => $availableYears,
                'selected_years'   => $yearsToUse,
                'eje_x'            => ['type' => 'category', 'categorias' => $yearsToUse, 'titulo' => 'Año'],
                'eje_y'            => ['titulo' => $variablePrincipal->unidad_medida ?? 'Valor'],
                'descripcion'      => $indicador->descripcion,
                'metodo_calculo'   => $indicador->metodo_calculo,
                'fuente'           => $indicador->fuente,
                'nota_explicativa' => 'Nota: Para la comparación de tendencias entre municipios, se utiliza la variable principal del indicador (' . $variablePrincipal->nombre_amigable . ').',
            ];
        } else {
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

    public function handleAggregatedView(string $nivel, array $validated, Indicador $indicador, array $selection): array
    {
        $this->usarVariablesPublicas($indicador);
        $aggregation = app(GeographicAggregationService::class);
        $selectedYears = $validated['anios'] ?? [];

        $esPiramidePoblacional = str_contains(
            mb_strtolower($indicador->nombre_amigable ?? '', 'UTF-8'),
            'población por grupos de edad'
        );
        if ($esPiramidePoblacional && ($nivel !== 'municipio' || in_array('estatal', $selection['ids'])) && count($selectedYears) <= 1) {

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

            return [
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
        }

        if (empty($selection['ids']) && ! in_array('estatal', $validated['municipio_ids'] ?? [])) {
            return [
                'series' => [],
                'titulo' => 'Selecciona una ubicación para consultar ' . $indicador->nombre_amigable,
                'descripcion' => $indicador->descripcion,
                'fuente' => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
                'available_years' => []
            ];
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
                $variablesParaProcesar = collect([$variableTotal]);
            }
        }

        foreach ($variablesParaProcesar->sortBy(['orden', 'nombre_amigable']) as $variable) {
            $query = DatoHistorico::where('variable_id', $variable->id);

            if ($nivel === 'municipio' && ! in_array('estatal', $selection['ids'])) {
                $query->where('municipio_id', $selection['ids'][0]);

                $datosHistoricos = $query->with('motivoSinDato')->orderBy('anio', 'asc')->get();
                $dataPoints = [];
                foreach ($datosHistoricos as $dato) {
                    $valor = $dato->valor !== null ? (float) $dato->valor : null;
                    $dataPoints[] = [(int) $dato->anio, $valor];

                    if ($dato->valor === null && $dato->motivo_sin_dato_id) {
                        $razon = $dato->motivoSinDato->nombre ?? 'Sin información';
                        $notasExplicativas[$dato->anio] = $razon;
                    }
                }
            } else {
                $esRelacionSexo = str_contains(mb_strtolower((string) $variable->unidad_medida, 'UTF-8'), 'hombres por cada cien mujeres');
                if ($esRelacionSexo) {
                    $sexoIds = Variable::whereHas('indicador', fn ($q) => $q->where('nombre_amigable', 'Población total según sexo'))
                        ->whereIn('nombre_amigable', ['Población hombres', 'Población mujeres'])
                        ->pluck('id', 'nombre_amigable');
                    $ratioQuery = DatoHistorico::whereIn('variable_id', $sexoIds->values());
                    if (! in_array('estatal', $selection['ids'])) {
                        $ratioQuery->whereIn('municipio_id', $selection['ids']);
                    }
                    $hombresId = (int) ($sexoIds['Población hombres'] ?? 0);
                    $mujeresId = (int) ($sexoIds['Población mujeres'] ?? 0);
                    $dataPoints = $ratioQuery->get(['municipio_id', 'anio', 'variable_id', 'valor'])
                        ->groupBy('anio')
                        ->map(fn($rows, $anio) => [(int) $anio, (float) ($aggregation->ratio($rows, $hombresId, $mujeresId) ?? 0)])
                        ->sortBy(fn($point) => $point[0])
                        ->values();
                } else {
                    if (! in_array('estatal', $selection['ids'])) {
                        $query->whereIn('municipio_id', $selection['ids']);
                    }
                    $datosHistoricos = $query->get(['municipio_id', 'anio', 'valor']);
                    $aggregationMethod = $aggregation->method(null, collect([$variable]));
                    $dataPoints = $datosHistoricos
                        ->groupBy('anio')
                        ->map(fn($rows, $anio) => [(int) $anio, (float) ($aggregation->aggregate($rows, $aggregationMethod) ?? 0)])
                        ->sortBy(fn($point) => $point[0])
                        ->values();
                }
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

        if (!empty($selection['nombres_municipios'])) {
            $responseData['municipios_incluidos'] = $selection['nombres_municipios'];
        }

        return $responseData;
    }

    public function getAvailableYearsForComplex(Indicador $indicador, array $selectionIds, int $countRequired = 1)
    {
        $this->usarVariablesPublicas($indicador);
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

    private function usarVariablesPublicas(Indicador $indicador): void
    {
        $indicador->setRelation(
            'variables',
            $indicador->variables->where('visible_en_ficha', true)->values(),
        );
    }
}
