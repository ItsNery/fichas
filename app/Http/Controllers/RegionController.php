<?php

namespace App\Http\Controllers;

use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Services\IndicadorQueryService;
use App\Services\FichaComposerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegionController extends Controller
{
    /**
     * Muestra el perfil de una macrorregión
     */
    public function perfilMacrorregion(Macrorregion $macrorregion)
    {
        $macrorregion->load('microrregiones.municipios');
        
        $municipiosIds = [];
        $municipios = collect();
        foreach ($macrorregion->microrregiones as $micro) {
            foreach ($micro->municipios as $muni) {
                $municipiosIds[] = $muni->id;
                $municipios->push($muni);
            }
        }
        $municipios = $municipios->sortBy('nombre');

        $datos = $this->obtenerDatosPerfil('Macrorregión', $macrorregion, $municipios, $municipiosIds);
        return view('regiones.perfil', $datos);
    }

    /**
     * Muestra el perfil de una microrregión
     */
    public function perfilMicrorregion(Microrregion $microrregion)
    {
        $microrregion->load('macrorregion', 'municipios');
        
        $municipiosCount = $microrregion->municipios->count();
        if ($municipiosCount === 1) {
            $municipio = $microrregion->municipios->first();
            return redirect()->route('ficha-municipal.perfil', $municipio->slug)
                ->with('info', "Esta microrregión no tiene información municipal desagregable en el sistema. Te hemos redirigido a la ficha de {$municipio->nombre}, donde se encuentra la información disponible.");
        } elseif ($municipiosCount === 0) {
            if ($microrregion->macrorregion) {
                    return redirect()->route('regiones.macro.perfil', $microrregion->macrorregion->slug)
                    ->with('warning', 'Esta microrregión no tiene municipios asignados actualmente.');
            }
            return redirect()->route('inicio')->with('error', 'Microrregión sin datos.');
        }

        $municipiosIds = $microrregion->municipios->pluck('id')->toArray();
        $municipios = $microrregion->municipios()->orderBy('nombre')->get();

        $datos = $this->obtenerDatosPerfil('Microrregión', $microrregion, $municipios, $municipiosIds);
        return view('regiones.perfil', $datos);
    }

    public function perfilEstatal()
    {
        $municipios = Municipio::with('microrregion.macrorregion')->orderBy('nombre')->get();
        $municipiosIds = $municipios->pluck('id')->all();
        $estado = (object) ['id' => null, 'slug' => 'estatal', 'nombre' => 'Estado de Puebla'];

        return view('regiones.perfil', $this->obtenerDatosPerfil('Estatal', $estado, $municipios, $municipiosIds));
    }

    /**
     * Obtiene los datos del perfil regional agregando los datos
     */
    private function obtenerDatosPerfil($tipoRegion, $region, $municipios, $municipiosIds)
    {
        $municipios = $municipios->unique('id')->values();
        $municipiosIds = collect($municipiosIds)->unique()->values()->all();
        $aggregation = app(\App\Services\GeographicAggregationService::class);
        $esEstatal = $tipoRegion === 'Estatal';
        // 1. Obtener configuraciones activas (igual que en perfil municipal)
        $configuraciones = ConfiguracionFicha::with(['indicador.tematica.dimension', 'variables', 'indicador.variables'])
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $variablesNecesarias = [];
        foreach ($configuraciones as $config) {
            $vars = $config->variables->where('visible_en_ficha', true)->isNotEmpty()
                ? $config->variables->where('visible_en_ficha', true)
                : ($config->indicador ? $config->indicador->variables->where('visible_en_ficha', true) : collect());
            foreach ($vars as $var) {
                $variablesNecesarias[] = $var->id;
            }
        }
        $variablesNecesarias = array_unique($variablesNecesarias);
        $sexoVariables = \App\Models\Variable::whereHas('indicador', fn ($query) => $query->where('nombre_amigable', 'Población total según sexo'))
            ->whereIn('nombre_amigable', ['Población hombres', 'Población mujeres'])
            ->pluck('id', 'nombre_amigable');
        $hombresId = (int) ($sexoVariables['Población hombres'] ?? 0);
        $mujeresId = (int) ($sexoVariables['Población mujeres'] ?? 0);
        $variablesNecesarias = array_unique(array_merge($variablesNecesarias, [$hombresId, $mujeresId]));

        // 2. Obtener todos los datos históricos más recientes de los municipios en la región
        // Buscamos explícitamente la variable de población.
        $varPobId = \App\Models\Variable::where('nombre_amigable', 'Población total')
            ->whereHas('indicador', fn($q) => $q->where('nombre_amigable', 'Población total según sexo'))
            ->value('id');
        if ($varPobId) $variablesNecesarias[] = $varPobId;
        $variablesNecesarias = array_unique($variablesNecesarias);

        $datosBrutos = DatoHistorico::with('variable.indicador')
            ->whereIn('municipio_id', $municipiosIds)
            ->whereIn('variable_id', $variablesNecesarias)
            ->get();

        // Agrupar por variable y municipio para sacar el año más reciente de cada uno
        $datosRecientes = collect();
        $agrupados = $datosBrutos->groupBy(fn($d) => $d->variable_id . '-' . $d->municipio_id);
        
        foreach ($agrupados as $grupo) {
            $datosRecientes->push($grupo->sortByDesc('anio')->first());
        }

        // 3. Estructurar el Perfil
        $perfil = ['general' => []];

        // Datos básicos para el Hero
        $poblacionTotal = $varPobId ? $datosRecientes->where('variable_id', $varPobId)->sum('valor') : 0;
        $poblacionRows = $varPobId ? $datosRecientes->where('variable_id', $varPobId) : collect();
        $poblacionAnio = $poblacionRows->mode('anio')[0] ?? $poblacionRows->max('anio');
        $poblacionCobertura = [
            'con_dato' => $poblacionRows->pluck('municipio_id')->unique()->count(),
            'total' => count($municipiosIds),
        ];
        $superficieKm2 = $municipios->sum(fn($municipio) => (float) ($municipio->superficie ?? 0));

        foreach ($configuraciones as $config) {
            $indicador = $config->indicador;
            if (!$indicador) continue;

            $dimension = $indicador->tematica->dimension->nombre ?? 'Sin Dimensión';
            $dimensionKey = str_replace(' ', '_', strtolower($dimension));

            if (!isset($perfil[$dimensionKey])) {
                $perfil[$dimensionKey] = [];
            }

            // Filtrar datos para este indicador
            $variables = $config->variables->where('visible_en_ficha', true)->isNotEmpty()
                ? $config->variables->where('visible_en_ficha', true)
                : $indicador->variables->where('visible_en_ficha', true);
                
            $variableIds = $variables->pluck('id')->all();
            $anioConfig = $aggregation->commonLatestYear($datosBrutos, $municipiosIds, $variableIds)
                ?? $aggregation->latestYear($datosBrutos, $variableIds);
            $datosIndicador = $anioConfig
                ? $datosBrutos->where('anio', $anioConfig)->whereIn('variable_id', $variableIds)
                : $datosRecientes->whereIn('variable_id', $variables->pluck('id'));

            if ($datosIndicador->isEmpty()) continue;

            if ($config->tipo_visualizacion === 'scatter') {
                $datosScatter = app(FichaComposerService::class)
                    ->obtenerScatterRegional($config, $region, $municipios);
                if (!$datosScatter) continue;

                $perfil[$dimensionKey][] = [
                    'config' => $config,
                    'datos' => $datosScatter,
                    'narrativa' => $datosScatter['descripcion'],
                ];
                continue;
            }

            if ($config->tipo_visualizacion === 'piramide') {
                $datosPiramide = app(IndicadorQueryService::class)->handlePiramideChart(
                    $indicador,
                    [
                        'ids' => $municipiosIds,
                        'titulo' => $region->nombre,
                        'nombres_municipios' => $municipios->pluck('nombre')->all(),
                    ],
                    $variables->pluck('id')->all()
                );
                $totalHombres = abs(array_sum($datosPiramide['series'][0]['data'] ?? []));
                $totalMujeres = array_sum($datosPiramide['series'][1]['data'] ?? []);
                $totalPoblacion = $totalHombres + $totalMujeres;
                $datosPiramide['valor_actual'] = number_format($totalPoblacion);
                $datosPiramide['total'] = $totalPoblacion;
                $datosPiramide['unidad'] = 'Habitantes';
                $datosPiramide['variables'] = [['unidad' => 'Habitantes']];

                $perfil[$dimensionKey][] = [
                    'config' => $config,
                    'datos' => $datosPiramide,
                    'narrativa' => "La pirámide agrega la estructura por edad y sexo de los "
                        . count($municipiosIds) . " municipios que integran {$region->nombre}.",
                ];
                continue;
            }

            $esComposicionSexo = $config->tipo_visualizacion === 'treemap'
                && $indicador->nombre_amigable === 'Población total según sexo'
                && $hombresId && $mujeresId;
            if ($esComposicionSexo) {
                $sexoRows = $datosBrutos
                    ->where('anio', $anioConfig)
                    ->whereIn('variable_id', [$hombresId, $mujeresId]);
                $hombres = (float) $sexoRows->where('variable_id', $hombresId)->sum('valor');
                $mujeres = (float) $sexoRows->where('variable_id', $mujeresId)->sum('valor');
                $totalSexo = $hombres + $mujeres;
                $sexoData = [
                    ['name' => 'Hombres', 'value' => $hombres, 'percent' => $totalSexo > 0 ? round($hombres / $totalSexo * 100, 2) : 0],
                    ['name' => 'Mujeres', 'value' => $mujeres, 'percent' => $totalSexo > 0 ? round($mujeres / $totalSexo * 100, 2) : 0],
                ];

                $perfil[$dimensionKey][] = [
                    'config' => $config,
                    'datos' => [
                        'valor_actual' => number_format($totalSexo),
                        'total' => $totalSexo,
                        'anio' => $anioConfig,
                        'aggregation_method' => 'sum',
                        'coverage' => $aggregation->coverage($sexoRows, $municipiosIds),
                        'unidad' => 'Habitantes',
                        'fuente' => $indicador->fuente ?? 'N/D',
                        'metodo_calculo' => $indicador->metodo_calculo ?? 'N/D',
                        'variables' => [
                            ['nombre' => 'Hombres', 'valor' => $hombres, 'unidad' => 'Habitantes'],
                            ['nombre' => 'Mujeres', 'valor' => $mujeres, 'unidad' => 'Habitantes'],
                        ],
                        'echarts' => [
                            'type' => 'treemap',
                            'unidad' => 'Habitantes',
                            'series' => [[
                                'type' => 'treemap',
                                'data' => $sexoData,
                            ]],
                        ],
                    ],
                    'narrativa' => "La población de {$region->nombre} se compone de "
                        . number_format($hombres) . " hombres (" . number_format($sexoData[0]['percent'], 2) . "%) y "
                        . number_format($mujeres) . " mujeres (" . number_format($sexoData[1]['percent'], 2) . "%).",
                ];
                continue;
            }

            // Determinar si sumar o promediar basándose en la unidad
            $primeraVar = $variables->first();
            $unidad = strtolower($primeraVar->unidad_medida ?? '');
            $aggregationMethod = $aggregation->method($config, $variables);
            $esRelacionSexo = str_contains($unidad, 'hombres por cada cien mujeres');
            if ($esRelacionSexo && $hombresId && $mujeresId) {
                $aggregationMethod = 'ratio';
            }
            $esPorcentaje = str_contains($unidad, '%') || str_contains($unidad, 'porcentaje');
            $esPromedio = in_array($aggregationMethod, ['average', 'ratio'], true) && !$esPorcentaje;
            $esMoneda = str_contains($unidad, '$') || str_contains($unidad, 'pesos');
            $esCategorica = false;
            
            // Comprobación de valor categórico (si algún valor no es numérico)
            foreach ($datosIndicador as $d) {
                if (!is_numeric(str_replace([',', '$', '%', ' '], '', $d->valor_display ?? $d->valor))) {
                    $esCategorica = true;
                    break;
                }
            }

            $valorRegional = 0;
            $valorDisplay = "";
            $anioMasComun = $anioConfig ?? $datosIndicador->mode('anio')[0] ?? null;

            if ($esCategorica) {
                // Para categóricos, tomamos el más frecuente
                $valoresTextos = $datosIndicador->pluck('valor_display')->toArray();
                $counts = array_count_values($valoresTextos);
                arsort($counts);
                $valorDisplay = key($counts); // El valor más común
                $valorRegional = $valorDisplay;
            } else {
                if ($esPorcentaje && $variables->count() > 1) {
                    // Es distribución porcentual. Determinar la categoría predominante.
                    $sumaPorVariable = [];
                    foreach($variables as $v) {
                        $sumaPorVariable[$v->nombre_amigable] = $datosIndicador->where('variable_id', $v->id)->sum('valor');
                    }
                    arsort($sumaPorVariable);
                    $varMayor = key($sumaPorVariable);
                    $promedioVarMayor = $datosIndicador->where('variable_id', $variables->where('nombre_amigable', $varMayor)->first()->id)->avg('valor');
                    
                    $valorDisplay = $varMayor . ' (' . number_format($promedioVarMayor, 2) . '%)';
                    $valorRegional = $promedioVarMayor;
                } else {
                    $valorRegional = $esRelacionSexo
                        ? ($aggregation->ratio(
                            $datosBrutos->where('anio', $anioMasComun),
                            $hombresId,
                            $mujeresId
                        ) ?? 0)
                        : ($aggregation->aggregate($datosIndicador, $aggregationMethod) ?? 0);

                    if ($esMoneda) {
                        $valorDisplay = '$' . number_format($valorRegional, 2);
                    } elseif ($esPorcentaje) {
                        $valorDisplay = number_format($valorRegional, 2) . '%';
                    } else {
                        $valorDisplay = number_format($valorRegional, $esPromedio ? 2 : 0);
                    }
                }
            }

            // Preparar datos de ranking por municipios (apilados)
            $municipiosRanking = [];
            foreach ($municipios as $muni) {
                $datosMuni = $datosIndicador->where('municipio_id', $muni->id);
                if ($datosMuni->isNotEmpty() && !$esCategorica) {
                    // Para ordenar: si es porcentaje usamos la primera variable, si es absoluto la suma.
                    $totalParaOrdenar = $aggregation->aggregate($datosMuni, $aggregationMethod);
                    if ($totalParaOrdenar === null) continue;
                        
                    $municipiosRanking[] = [
                        'id' => $muni->id,
                        'name' => $muni->nombre,
                        'orderValue' => (float) $totalParaOrdenar
                    ];
                }
            }

            // Ordenar ranking de mayor a menor
            usort($municipiosRanking, function($a, $b) {
                return $b['orderValue'] <=> $a['orderValue'];
            });

            $rankingCompleto = $municipiosRanking;
            $rankingVista = $esEstatal
                ? collect(array_merge(array_slice($municipiosRanking, 0, 5), array_slice($municipiosRanking, -5)))
                    ->unique('id')->values()->all()
                : $municipiosRanking;
            $categoriasEjeY = array_column($rankingVista, 'name');

            // Armar series para ECharts
            $seriesEcharts = [];
            $colores = ['#861e34', '#c79b66', '#0a192f', '#444444', '#7a7a7a'];
            $idxColor = 0;

            foreach ($variables as $v) {
                $datosSerie = [];
                foreach ($rankingVista as $mRank) {
                    $val = $datosIndicador->where('municipio_id', $mRank['id'])->where('variable_id', $v->id)->first();
                    $datosSerie[] = $val ? (float) $val->valor : null;
                }
                
                // Mostrar solo si tiene datos reales
                if (array_sum($datosSerie) > 0) {
                    $seriesEcharts[] = [
                        'name' => $v->nombre_amigable,
                        'data' => $datosSerie,
                        'type' => 'bar',
                        'stack' => 'total',
                        'itemStyle' => ['color' => $colores[$idxColor % count($colores)]]
                    ];
                    $idxColor++;
                }
            }

            $tipoVisualizacion = strtolower((string) $config->tipo_visualizacion);
            $tipoEcharts = 'bar-horizontal';
            $ejeEcharts = ['categorias' => $categoriasEjeY];
            $seriesEchartsFinales = $seriesEcharts;

            if (in_array($tipoVisualizacion, ['pie', 'pastel'], true)) {
                $tipoEcharts = 'pie';
                $seriesEchartsFinales = [[
                    'name' => $indicador->nombre_amigable,
                    'type' => 'pie',
                    'radius' => ['40%', '70%'],
                    'data' => array_map(fn ($item) => [
                        'name' => $item['name'],
                        'value' => $item['orderValue'],
                    ], $rankingVista),
                ]];
                $ejeEcharts = [];
            } elseif ($tipoVisualizacion === 'treemap') {
                $tipoEcharts = 'treemap';
                $treemapRanking = $esEstatal ? $rankingCompleto : $rankingVista;
                $seriesEchartsFinales = [[
                    'type' => 'treemap',
                    'roam' => false,
                    'nodeClick' => 'zoomToNode',
                    'data' => array_map(fn ($item) => [
                        'name' => $item['name'],
                        'value' => $item['orderValue'],
                    ], $treemapRanking),
                ]];
                $ejeEcharts = [];
            } elseif ($tipoVisualizacion === 'mapa') {
                $tipoEcharts = 'map';
                $seriesEchartsFinales = [[
                    'name' => $indicador->nombre_amigable,
                    'type' => 'map',
                    'map' => 'puebla',
                    'roam' => true,
                    'emphasis' => ['label' => ['show' => false]],
                    'data' => array_map(fn ($item) => [
                        'name' => mb_strtoupper($item['name'], 'UTF-8'),
                        'value' => $item['orderValue'],
                    ], $rankingCompleto),
                ]];
                $ejeEcharts = [];
            } elseif (in_array($tipoVisualizacion, ['lineas', 'line'], true)) {
                $tipoEcharts = 'line';
                $tendenciaRows = $esRelacionSexo
                    ? $datosBrutos->whereIn('variable_id', [$hombresId, $mujeresId])
                    : $datosBrutos->whereIn('variable_id', $variableIds);
                $tendenciaRegional = $tendenciaRows
                    ->groupBy('anio')
                    ->map(fn ($rows, $anio) => [
                        'anio' => (int) $anio,
                        'valor' => (float) ($esRelacionSexo
                            ? ($aggregation->ratio($rows, $hombresId, $mujeresId) ?? 0)
                            : ($aggregation->aggregateAcrossMunicipalities($rows, $municipiosIds, $aggregationMethod) ?? 0)),
                    ])
                    ->sortBy('anio')
                    ->values();
                $seriesEchartsFinales = [[
                    'name' => $region->nombre,
                    'type' => 'line',
                    'smooth' => true,
                    'data' => $tendenciaRegional->pluck('valor')->all(),
                ]];
                $ejeEcharts = ['categorias' => $tendenciaRegional->pluck('anio')->all(), 'titulo' => 'Año'];
            }

            $perfil[$dimensionKey][] = [
                'config' => $config,
                'datos' => [
                    'valor_actual' => $valorDisplay,
                    'total' => $valorRegional,
                    'anio' => $anioMasComun,
                    'aggregation_method' => $aggregationMethod,
                    'coverage' => $aggregation->coverage($datosIndicador, $municipiosIds),
                    'ranking' => $rankingCompleto,
                    'ranking_display' => $rankingVista,
                    'ranking_limited' => $esEstatal && count($rankingCompleto) > count($rankingVista),
                    'ranking_total' => count($rankingCompleto),
                    'es_estatal' => $esEstatal,
                    'unidad' => $primeraVar->unidad_medida ?? '',
                    'fuente' => $indicador->fuente ?? 'N/D',
                    'metodo_calculo' => $indicador->metodo_calculo ?? 'N/D',
                    'variables' => [['unidad' => $primeraVar->unidad_medida ?? '']],
                    'echarts' => [
                        'type' => $tipoEcharts,
                        'unidad' => $primeraVar->unidad_medida ?? '',
                        'aggregation_method' => $aggregationMethod,
                        'data' => $tipoEcharts === 'map' ? ($seriesEchartsFinales[0]['data'] ?? []) : [],
                        'min' => $tipoEcharts === 'map' ? min(array_map(fn ($item) => (float) ($item['value'] ?? 0), $seriesEchartsFinales[0]['data'] ?? [["value" => 0]])) : null,
                        'max' => $tipoEcharts === 'map' ? max(array_map(fn ($item) => (float) ($item['value'] ?? 0), $seriesEchartsFinales[0]['data'] ?? [["value" => 0]])) : null,
                        'eje_x' => $tipoEcharts === 'line' ? $ejeEcharts : [],
                        'eje_y' => $ejeEcharts,
                        'series' => $seriesEchartsFinales,
                        'requested_visualization' => $tipoVisualizacion,
                    ]
                ],
                'narrativa' => $this->generarNarrativaRegional($config, $region, $valorDisplay, $rankingCompleto, $esPorcentaje, $esPromedio, $esCategorica)
            ];
        }

        $resumenTerritorial = [];
        if ($esEstatal) {
            $poblacionPorMunicipio = $varPobId
                ? $datosRecientes->where('variable_id', $varPobId)->keyBy('municipio_id')
                : collect();

            $resumenTerritorial = $municipios
                ->groupBy(fn ($municipio) => $municipio->microrregion?->macrorregion?->id ?? 0)
                ->map(function ($grupo) use ($poblacionPorMunicipio) {
                    $macro = $grupo->first()->microrregion?->macrorregion;
                    return [
                        'id' => $macro?->id,
                        'nombre' => $macro?->nombre ?? 'Sin macrorregión',
                        'slug' => $macro?->slug,
                        'municipios' => $grupo->count(),
                        'microrregiones_representadas' => $grupo->pluck('microrregion_id')->filter()->unique()->count(),
                        'poblacion' => (float) $grupo->sum(fn ($municipio) => (float) ($poblacionPorMunicipio->get($municipio->id)?->valor ?? 0)),
                    ];
                })
                ->sortByDesc('poblacion')
                ->values()
                ->all();
        }

        $microrregionesRepresentadas = $municipios->pluck('microrregion_id')->filter()->unique()->count();
        $macrorregionesRepresentadas = $esEstatal
            ? collect($resumenTerritorial)->whereNotNull('id')->count()
            : ($municipios->isNotEmpty() ? 1 : 0);
        $alcanceTerritorial = [
            'municipios_con_informacion' => $municipios->count(),
            'macrorregiones_oficiales' => config('regionalizacion.macrorregiones'),
            'microrregiones_oficiales' => config('regionalizacion.microrregiones'),
            'macrorregiones_representadas' => $macrorregionesRepresentadas,
            'microrregiones_representadas' => $microrregionesRepresentadas,
            'fuente_url' => config('regionalizacion.url'),
        ];

        return [
            'tipoRegion' => $tipoRegion,
            'esEstatal' => $esEstatal,
            'region' => $region,
            'municipios' => $municipios,
            'poblacionTotal' => $poblacionTotal,
            'poblacionAnio' => $poblacionAnio,
            'poblacionCobertura' => $poblacionCobertura,
            'superficieTotal' => $superficieKm2,
            'resumenTerritorial' => $resumenTerritorial,
            'alcanceTerritorial' => $alcanceTerritorial,
            'perfil' => $perfil
        ];
    }

    /**
     * Genera un pequeño texto narrativo dinámico para la región
     */
    private function generarNarrativaRegional($config, $region, $valorDisplay, $municipiosRanking, $esPorcentaje, $esPromedio, $esCategorica)
    {
        if ($esCategorica || empty($municipiosRanking)) {
            return "El indicador de <strong>{$config->indicador->nombre_amigable}</strong> para esta región es predominantemente: <strong>{$valorDisplay}</strong>.";
        }

        $primero = $municipiosRanking[0];
        $ultimo = $municipiosRanking[count($municipiosRanking) - 1];
        
        $tipo = $esPorcentaje ? 'la mayor aportación' : ($esPromedio ? 'el valor más alto' : 'el total acumulado');

        return "Para la región <strong>{$region->nombre}</strong>, el valor regional es <strong>{$valorDisplay}</strong>. " .
               "El municipio que encabeza la métrica es <strong>{$primero['name']}</strong> con " . number_format($primero['orderValue'], 2) . ", " .
               "mientras que <strong>{$ultimo['name']}</strong> registra " . number_format($ultimo['orderValue'], 2) . ".";
    }

    public function exportarMacrorregionPDF(Macrorregion $macrorregion)
    {
        $macrorregion->load('microrregiones.municipios');
        
        $municipiosIds = [];
        $municipios = collect();
        foreach ($macrorregion->microrregiones as $micro) {
            foreach ($micro->municipios as $muni) {
                $municipiosIds[] = $muni->id;
                $municipios->push($muni);
            }
        }
        $municipios = $municipios->sortBy('nombre');

        $datos = $this->obtenerDatosPerfil('Macrorregión', $macrorregion, $municipios, $municipiosIds);
        
        $pdf = \PDF::loadView('regiones.resumen_pdf', $datos);
        return $pdf->download('Resumen_Macrorregional_' . str_replace(' ', '_', $macrorregion->nombre) . '.pdf');
    }

    public function exportarMicrorregionPDF(Microrregion $microrregion)
    {
        $microrregion->load('macrorregion', 'municipios');
        $municipiosCount = $microrregion->municipios->count();
        if ($municipiosCount === 1) {
            $municipio = $microrregion->municipios->first();
            return redirect()->route('ficha-municipal.pdf', $municipio->slug)
                ->with('info', "Esta microrregión no tiene información municipal desagregable en el sistema. Te hemos redirigido a la ficha de {$municipio->nombre}.");
        } elseif ($municipiosCount === 0) {
            return redirect()->back()->with('error', 'Microrregión sin datos.');
        }

        $municipiosIds = $microrregion->municipios->pluck('id')->toArray();
        $municipios = $microrregion->municipios()->orderBy('nombre')->get();

        $datos = $this->obtenerDatosPerfil('Microrregión', $microrregion, $municipios, $municipiosIds);
        
        $pdf = \PDF::loadView('regiones.resumen_pdf', $datos);
        return $pdf->download('Resumen_Microrregional_' . str_replace(' ', '_', $microrregion->nombre) . '.pdf');
    }

    public function exportarEstatalPDF()
    {
        $datos = $this->datosEstatales();
        $pdf = \PDF::loadView('regiones.resumen_pdf', $datos);
        return $pdf->download('Resumen_Estatal_Puebla.pdf');
    }

    public function exportarMacrorregionExcel(Macrorregion $macrorregion)
    {
        $macrorregion->load('microrregiones.municipios');
        
        $municipiosIds = [];
        $municipios = collect();
        foreach ($macrorregion->microrregiones as $micro) {
            foreach ($micro->municipios as $muni) {
                $municipiosIds[] = $muni->id;
                $municipios->push($muni);
            }
        }
        $municipios = $municipios->sortBy('nombre');

        $datos = $this->obtenerDatosPerfil('Macrorregión', $macrorregion, $municipios, $municipiosIds);
        
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RegionExport($datos), 'Resumen_Macrorregional_' . str_replace(' ', '_', $macrorregion->nombre) . '.xlsx');
    }

    public function exportarMicrorregionExcel(Microrregion $microrregion)
    {
        $microrregion->load('macrorregion', 'municipios');
        $municipiosCount = $microrregion->municipios->count();
        if ($municipiosCount === 1) {
            $municipio = $microrregion->municipios->first();
            return redirect()->route('ficha-municipal.perfil', $municipio->slug)
                ->with('info', "Esta microrregión no tiene información municipal desagregable en el sistema. Te hemos redirigido a la ficha de {$municipio->nombre}.");
        } elseif ($municipiosCount === 0) {
            return redirect()->back()->with('error', 'Microrregión sin datos.');
        }

        $municipiosIds = $microrregion->municipios->pluck('id')->toArray();
        $municipios = $microrregion->municipios()->orderBy('nombre')->get();

        $datos = $this->obtenerDatosPerfil('Microrregión', $microrregion, $municipios, $municipiosIds);
        
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RegionExport($datos), 'Resumen_Microrregional_' . str_replace(' ', '_', $microrregion->nombre) . '.xlsx');
    }

    public function exportarEstatalExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RegionExport($this->datosEstatales()),
            'Resumen_Estatal_Puebla.xlsx'
        );
    }

    private function datosEstatales(): array
    {
        $municipios = Municipio::with('microrregion.macrorregion')->orderBy('nombre')->get();
        $estado = (object) ['id' => null, 'slug' => 'estatal', 'nombre' => 'Estado de Puebla'];
        return $this->obtenerDatosPerfil('Estatal', $estado, $municipios, $municipios->pluck('id')->all());
    }
}
