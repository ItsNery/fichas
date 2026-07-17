<?php

namespace App\Services;

use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\DatoIndicadorComplejo;
use App\Models\Municipio;
use App\Models\Variable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FichaComposerService
{
    public function obtenerDatosParaConfig($config, Municipio $municipio, FichaDataStore $dataStore = null, $anioForzado = null)
    {
        $indicador = $config->indicador;
        $variablesConfig = $config->variables->where('visible_en_ficha', true);

        $variableIds = $variablesConfig->isNotEmpty()
            ? $variablesConfig->pluck('id')
            : $indicador->variables->where('visible_en_ficha', true)->pluck('id');

        if ($indicador->es_complejo) {
            $anioMax = $anioForzado ?? DatoIndicadorComplejo::where('indicador_id', $indicador->id)
                ->where('municipio_id', $municipio->id)
                ->max('anio');
            if (!$anioMax) {
                return null;
            }

            $registro = DatoIndicadorComplejo::where('indicador_id', $indicador->id)
                ->where('municipio_id', $municipio->id)
                ->where('anio', $anioMax)
                ->first();

            return $registro?->datos;
        }

        if ($dataStore) {
            $anioMax = $anioForzado ?? $dataStore->muniData->whereIn('variable_id', $variableIds)->max('anio');
        } else {
            $anioMax = $anioForzado ?? DatoHistorico::whereIn('variable_id', $variableIds)
                ->where('municipio_id', $municipio->id)
                ->max('anio');
        }

        if (!$anioMax) return null;

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

                if (!$varX || !$varY) return null;

                if ($dataStore) {
                    $datosGlobalesX = $dataStore->globalData->where('variable_id', $varXId);
                    $datosGlobalesY = $dataStore->globalData->where('variable_id', $varYId);
                    $anioX = $anioForzado && $datosGlobalesX->where('anio', $anioForzado)->isNotEmpty()
                        ? $anioForzado : $datosGlobalesX->max('anio');
                    $anioY = $anioForzado && $datosGlobalesY->where('anio', $anioForzado)->isNotEmpty()
                        ? $anioForzado : $datosGlobalesY->max('anio');
                } else {
                    $anioX = $anioForzado && DatoHistorico::where('variable_id', $varXId)->where('anio', $anioForzado)->exists()
                        ? $anioForzado : DatoHistorico::where('variable_id', $varXId)->max('anio');
                    $anioY = $anioForzado && DatoHistorico::where('variable_id', $varYId)->where('anio', $anioForzado)->exists()
                        ? $anioForzado : DatoHistorico::where('variable_id', $varYId)->max('anio');
                }

                if (!$anioX || !$anioY) return null;

                if ($dataStore) {
                    $datosX = $dataStore->globalData->where('variable_id', $varXId)->where('anio', $anioX)->keyBy('municipio_id');
                    $datosY = $dataStore->globalData->where('variable_id', $varYId)->where('anio', $anioY)->keyBy('municipio_id');
                } else {
                    $datosX = DatoHistorico::where('variable_id', $varXId)->where('anio', $anioX)->get()->keyBy('municipio_id');
                    $datosY = DatoHistorico::where('variable_id', $varYId)->where('anio', $anioY)->get()->keyBy('municipio_id');
                }

                $varPob = Variable::where('nombre_amigable', 'Población total')->first();
                $poblaciones = collect();
                $anioPoblacion = null;
                if ($varPob) {
                    if ($dataStore) {
                        $datosPoblacion = $dataStore->globalData->where('variable_id', $varPob->id);
                        $anioPoblacion = $datosPoblacion->max('anio');
                        if ($anioPoblacion) {
                            $poblaciones = $datosPoblacion->where('anio', $anioPoblacion)->keyBy('municipio_id');
                        }
                    }
                    if ($poblaciones->isEmpty()) {
                        $anioPoblacion = DatoHistorico::where('variable_id', $varPob->id)->max('anio');
                        $poblaciones = DatoHistorico::where('variable_id', $varPob->id)
                            ->where('anio', $anioPoblacion)
                            ->get()
                            ->keyBy('municipio_id');
                    }
                }

                $ajustes = $config->ajustes_visuales ?? [];
                $unidadX = (string) $varX->unidad_medida;
                $esMonetaria = str_contains(mb_strtolower($unidadX, 'UTF-8'), 'pesos');
                $normalizacionSolicitada = (bool) ($ajustes['normalizar_x_per_capita'] ?? false);
                $normalizarPorHabitante = ($esMonetaria || $normalizacionSolicitada) && $poblaciones->isNotEmpty();

                $municipios = Municipio::all()->keyBy('id');

                $seriesNormal = [];
                $seriesHighlight = [];

                foreach ($municipios as $munId => $mun) {
                    $datoX = $datosX->get($munId);
                    $datoY = $datosY->get($munId);
                    $pob = $poblaciones->get($munId);

                    if ($datoX && $datoY && (!$normalizarPorHabitante || ($pob && $pob->valor > 0))) {
                        $xVal = (float)$datoX->valor;

                        if ($normalizarPorHabitante) {
                            $factor = (float) ($ajustes['factor_x'] ?? (str_contains(mb_strtolower($unidadX, 'UTF-8'), 'miles') ? 1000 : 1));
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
                $unidadXPresentada = $ajustes['unidad_x'] ?? ($normalizarPorHabitante
                    ? ($esMonetaria ? '$ por habitante' : trim($unidadX) . ' por habitante')
                    : $unidadX);
                $tituloX = $ajustes['eje_x_titulo'] ?? ($varX->nombre_amigable
                    . ($normalizarPorHabitante ? ($esMonetaria ? ' per cápita ($/hab)' : ' per cápita') : " ({$unidadXPresentada})"));
                $tituloY = $ajustes['eje_y_titulo'] ?? $varY->nombre_amigable;
                $fuentes = collect([$varX->indicador?->fuente, $varY->indicador?->fuente])
                    ->filter()
                    ->unique()
                    ->join(' / ');
                $metodoCalculo = $normalizarPorHabitante
                    ? "{$varX->nombre_amigable} per cápita = valor reportado / población total de {$anioPoblacion}."
                    : 'Los valores se presentan en su unidad de medida original.';
                $notaTemporal = $anioX == $anioY
                    ? "Ambos indicadores corresponden a {$anioX}."
                    : "Se usa el último dato disponible de cada indicador: {$anioX} para el eje X y {$anioY} para el eje Y.";
                $correlacion = $this->calcularCorrelacion(array_merge($seriesNormal, $seriesHighlight));
                $lecturaCorrelacion = $this->describirCorrelacion($correlacion);

        return [
                    'anio'             => $anioY,
                    'total'            => null,
                    'variables'        => [
                        [
                            'nombre' => $varX->nombre_amigable,
                            'unidad' => $unidadXPresentada,
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
                            'titulo' => $tituloX
                        ],
                        'eje_y' => [
                            'titulo' => $tituloY
                        ]
                    ],
                    'correlacion'     => $correlacion,
                    'correlacion_lectura' => $lecturaCorrelacion,
                    'descripcion'    => "Este gráfico compara {$varX->nombre_amigable} con {$varY->nombre_amigable} para los municipios de Puebla. {$notaTemporal} {$lecturaCorrelacion} Esto no implica causalidad. El punto resaltado representa a {$municipio->nombre}.",
                    'fuente'         => $fuentes ?: 'Fuentes de los indicadores seleccionados',
                    'metodo_calculo' => $metodoCalculo,
        ];
    }

        }

        if ($config->tipo_visualizacion === 'piramide') {
            return app(IndicadorQueryService::class)->handlePiramideChart($indicador, ['ids' => [$municipio->id], 'titulo' => $municipio->nombre], $variableIds->toArray());
        }

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
                ? app(RankingService::class)->getMunicipalityRankingInMemory($dataStore, $variableIds, $municipio->id, $anioMax)
                : app(RankingService::class)->getMunicipalityRanking($variableIds, $municipio->id, $anioMax),
            'promedio_estatal'        => $config->mostrar_comparativa
                ? ($dataStore ? app(RankingService::class)->getStateAverageInMemory($dataStore, $variableIds, $anioMax, $method) : app(RankingService::class)->getStateAverage($variableIds, $anioMax, $method))
                : null,
            'promedio_macrorregional' => ($config->mostrar_comparativa && $macrorregionId)
                ? ($dataStore ? app(RankingService::class)->getMacrorregionalAverageInMemory($dataStore, $variableIds, $municipiosMacrorregionIds, $anioMax, $method) : app(RankingService::class)->getMacrorregionalAverage($variableIds, $municipio, $anioMax, $method))
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

            if (isset($ajustes['icons'][$valorOriginal])) {
                $res['icono_actual'] = $ajustes['icons'][$valorOriginal];
            }
        }

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

    public function obtenerScatterRegional(ConfiguracionFicha $config, $region, $municipios): ?array
    {
        $variablesConfig = $config->variables->where('visible_en_ficha', true);
        $variableIds = $variablesConfig->isNotEmpty()
            ? $variablesConfig->pluck('id')
            : $config->indicador->variables->where('visible_en_ficha', true)->pluck('id');
        $varX = Variable::with('indicador')->find($variableIds->first());
        $varY = Variable::with('indicador')->find($variableIds->skip(1)->first());

        if (!$varX || !$varY) return null;

        $anioX = DatoHistorico::where('variable_id', $varX->id)->max('anio');
        $anioY = DatoHistorico::where('variable_id', $varY->id)->max('anio');
        if (!$anioX || !$anioY) return null;

        $datosX = DatoHistorico::where('variable_id', $varX->id)->where('anio', $anioX)->get()->keyBy('municipio_id');
        $datosY = DatoHistorico::where('variable_id', $varY->id)->where('anio', $anioY)->get()->keyBy('municipio_id');
        $varPoblacion = Variable::where('nombre_amigable', 'Población total')->first();
        $poblaciones = collect();
        $anioPoblacion = null;
        if ($varPoblacion) {
            $anioPoblacion = DatoHistorico::where('variable_id', $varPoblacion->id)->max('anio');
            $poblaciones = DatoHistorico::where('variable_id', $varPoblacion->id)
                ->where('anio', $anioPoblacion)
                ->get()
                ->keyBy('municipio_id');
        }

        $ajustes = $config->ajustes_visuales ?? [];
        $unidadX = (string) $varX->unidad_medida;
        $esMonetaria = str_contains(mb_strtolower($unidadX, 'UTF-8'), 'pesos');
        $normalizacionSolicitada = (bool) ($ajustes['normalizar_x_per_capita'] ?? false);
        $normalizarPorHabitante = ($esMonetaria || $normalizacionSolicitada) && $poblaciones->isNotEmpty();
        $factor = (float) ($ajustes['factor_x'] ?? (str_contains(mb_strtolower($unidadX, 'UTF-8'), 'miles') ? 1000 : 1));
        $unidadXPresentada = $ajustes['unidad_x'] ?? ($normalizarPorHabitante
            ? ($esMonetaria ? '$ por habitante' : trim($unidadX) . ' por habitante')
            : $unidadX);
        $tituloX = $ajustes['eje_x_titulo'] ?? ($varX->nombre_amigable
            . ($normalizarPorHabitante ? ($esMonetaria ? ' per cápita ($/hab)' : ' per cápita') : " ({$unidadXPresentada})"));
        $tituloY = $ajustes['eje_y_titulo'] ?? $varY->nombre_amigable;
        $regionIds = $municipios->pluck('id')->map(fn($id) => (int) $id)->flip();
        $puntosEstado = [];
        $puntosRegion = [];

        foreach (Municipio::select('id', 'nombre', 'slug')->get() as $municipio) {
            $datoX = $datosX->get($municipio->id);
            $datoY = $datosY->get($municipio->id);
            $poblacion = $poblaciones->get($municipio->id);
            if (!$datoX || !$datoY || ($normalizarPorHabitante && (!$poblacion || $poblacion->valor <= 0))) continue;

            $valorX = (float) $datoX->valor;
            if ($normalizarPorHabitante) {
                $valorX = ($valorX * $factor) / $poblacion->valor;
            }
            $punto = [round($valorX, 2), round((float) $datoY->valor, 2), $municipio->nombre, $municipio->slug];
            if ($regionIds->has($municipio->id)) {
                $puntosRegion[] = $punto;
            } else {
                $puntosEstado[] = $punto;
            }
        }

        if (!$puntosRegion) return null;

        $medianaX = $this->calcularMediana(array_column($puntosRegion, 0));
        $medianaY = $this->calcularMediana(array_column($puntosRegion, 1));
        $correlacion = count($puntosRegion) >= 5 ? $this->calcularCorrelacion($puntosRegion) : null;
        $lecturaCorrelacion = count($puntosRegion) >= 5
            ? $this->describirCorrelacion($correlacion)
            : 'La región tiene menos de cinco municipios con datos; no se estima correlación por el tamaño reducido de la muestra.';
        $nombreRegion = $region->nombre;
        $fuentes = collect([$varX->indicador?->fuente, $varY->indicador?->fuente])->filter()->unique()->join(' / ');
        $metodoCalculo = $normalizarPorHabitante
            ? "{$varX->nombre_amigable} per cápita = valor reportado / población total de {$anioPoblacion}. Las medianas regionales se incluyen en la información metodológica."
            : 'Cada punto representa un municipio. Las medianas regionales se incluyen en la información metodológica.';
        $notaTemporal = $anioX == $anioY
            ? "Ambos indicadores corresponden a {$anioX}."
            : "El eje X corresponde a {$anioX} y el eje Y a {$anioY}.";

        return [
            'anio' => $anioX == $anioY ? $anioX : "{$anioX} / {$anioY}",
            'valor_actual' => count($puntosRegion),
            'total' => count($puntosRegion),
            'unidad' => 'Municipios',
            'variables' => [
                ['nombre' => $varX->nombre_amigable, 'unidad' => $unidadXPresentada, 'valor' => $medianaX],
                ['nombre' => $varY->nombre_amigable, 'unidad' => $varY->unidad_medida, 'valor' => $medianaY],
            ],
            'echarts' => [
                'type' => 'scatter',
                'eje_x' => ['titulo' => $tituloX],
                'eje_y' => ['titulo' => $tituloY],
                'series' => [
                    [
                        'name' => 'Otros municipios de Puebla',
                        'type' => 'scatter',
                        'data' => $puntosEstado,
                        'symbolSize' => 7,
                        'itemStyle' => ['color' => 'rgba(122, 122, 122, 0.28)'],
                    ],
                    [
                        'name' => $nombreRegion,
                        'type' => 'scatter',
                        'data' => $puntosRegion,
                        'symbolSize' => 11,
                        'itemStyle' => ['color' => $ajustes['municipio_color'] ?? '#861e34'],
                    ],
                ],
            ],
            'correlacion' => $correlacion,
            'correlacion_lectura' => $lecturaCorrelacion,
            'descripcion' => "Se comparan " . count($puntosRegion) . " municipios de {$nombreRegion} dentro del contexto estatal. {$notaTemporal} {$lecturaCorrelacion} Esto no implica causalidad.",
            'fuente' => $fuentes ?: 'Fuentes de los indicadores seleccionados',
            'metodo_calculo' => $metodoCalculo,
        ];
    }

    public function formatearDatosParaECharts(array $variablesArray, string $tipo_visualizacion, $variableIds = null, $anio = null, $tendencia = null, $tendenciaEstado = null, $tendenciaMacrorregion = null)
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
                $data[] = [$val1, $val2, $mNombre, $mId];
            }

            return [
                'type' => 'scatter',
                'series' => [
                    [
                        'data' => $data,
                        'symbolSize' => 12,
                        'itemStyle' => [
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

    public function combinarDatosParaECharts($config, $datos1, $datos2, $municipio1, $municipio2)
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

    public function getWikipediaSummary(string $nombre): ?array
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

        $ttl = $resultado ? now()->addDays(7) : now()->addHours(6);
        Cache::put($cacheKey, $resultado, $ttl);

        return $resultado;
    }

    private function wikipediaClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(5)->withHeaders([
            'User-Agent' => 'PortalMunicipalPuebla/1.0 (nery.pozos@puebla.gob.mx)'
        ]);
    }

    private function calcularCorrelacion(array $puntos): ?float
    {
        if (count($puntos) < 2) return null;

        $promedioX = collect($puntos)->avg(fn($punto) => $punto[0]);
        $promedioY = collect($puntos)->avg(fn($punto) => $punto[1]);
        $numerador = 0;
        $sumaX = 0;
        $sumaY = 0;

        foreach ($puntos as $punto) {
            $diferenciaX = $punto[0] - $promedioX;
            $diferenciaY = $punto[1] - $promedioY;
            $numerador += $diferenciaX * $diferenciaY;
            $sumaX += $diferenciaX ** 2;
            $sumaY += $diferenciaY ** 2;
        }

        $denominador = sqrt($sumaX * $sumaY);
        return $denominador > 0 ? round($numerador / $denominador, 3) : null;
    }

    private function calcularMediana(array $valores): float
    {
        sort($valores, SORT_NUMERIC);
        $cantidad = count($valores);
        $mitad = intdiv($cantidad, 2);

        return $cantidad % 2
            ? round((float) $valores[$mitad], 2)
            : round(((float) $valores[$mitad - 1] + (float) $valores[$mitad]) / 2, 2);
    }

    private function describirCorrelacion(?float $correlacion): string
    {
        if ($correlacion === null) return 'No hay variación suficiente para estimar una asociación lineal.';

        $magnitud = abs($correlacion);
        $intensidad = $magnitud >= 0.7 ? 'fuerte' : ($magnitud >= 0.4 ? 'moderada' : 'débil');
        $direccion = $correlacion >= 0 ? 'positiva' : 'inversa';

        return "La asociación lineal observada es {$intensidad} y {$direccion} (r = " . number_format($correlacion, 2) . ').';
    }
}
