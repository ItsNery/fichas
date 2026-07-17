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
                ->with('info', 'La información para esta microrregión no se puede desagregar porque pertenece a un municipio particionado. Te hemos redirigido a la ficha de su municipio correspondiente.');
        } elseif ($municipiosCount === 0) {
            if ($microrregion->macrorregion) {
                return redirect()->route('regiones.macrorregion', $microrregion->macrorregion->slug)
                    ->with('warning', 'Esta microrregión no tiene municipios asignados actualmente.');
            }
            return redirect()->route('inicio')->with('error', 'Microrregión sin datos.');
        }

        $municipiosIds = $microrregion->municipios->pluck('id')->toArray();
        $municipios = $microrregion->municipios()->orderBy('nombre')->get();

        $datos = $this->obtenerDatosPerfil('Microrregión', $microrregion, $municipios, $municipiosIds);
        return view('regiones.perfil', $datos);
    }

    /**
     * Obtiene los datos del perfil regional agregando los datos
     */
    private function obtenerDatosPerfil($tipoRegion, $region, $municipios, $municipiosIds)
    {
        // 1. Obtener configuraciones activas (igual que en perfil municipal)
        $configuraciones = ConfiguracionFicha::with(['indicador.tematica.dimension', 'variables', 'indicador.variables'])
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $variablesNecesarias = [];
        foreach ($configuraciones as $config) {
            $vars = $config->variables->isNotEmpty() 
                ? $config->variables 
                : ($config->indicador ? $config->indicador->variables : collect());
            foreach ($vars as $var) {
                $variablesNecesarias[] = $var->id;
            }
        }
        $variablesNecesarias = array_unique($variablesNecesarias);

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
            $variables = $config->variables->isNotEmpty() 
                ? $config->variables
                : $indicador->variables;
                
            $datosIndicador = $datosRecientes->whereIn('variable_id', $variables->pluck('id'));

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

            // Determinar si sumar o promediar basándose en la unidad
            $primeraVar = $variables->first();
            $unidad = strtolower($primeraVar->unidad_medida ?? '');
            
            $esPorcentaje = str_contains($unidad, '%') || str_contains($unidad, 'porcentaje') || str_contains($unidad, 'índice') || str_contains($unidad, 'grado');
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
            $anioMasComun = $datosIndicador->mode('anio')[0] ?? null;

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
                    $suma = $datosIndicador->sum('valor');
                    if ($esPorcentaje) {
                        $datosValidos = $datosIndicador->filter(fn($d) => is_numeric($d->valor) && $d->valor > 0);
                        $valorRegional = $datosValidos->count() > 0 ? $datosValidos->sum('valor') / $datosValidos->count() : 0;
                    } else {
                        $valorRegional = $suma;
                    }

                    if ($esMoneda) {
                        $valorDisplay = '$' . number_format($valorRegional, 2);
                    } elseif ($esPorcentaje) {
                        $valorDisplay = number_format($valorRegional, 2) . '%';
                    } else {
                        $valorDisplay = number_format($valorRegional);
                    }
                }
            }

            // Preparar datos de ranking por municipios (apilados)
            $municipiosRanking = [];
            foreach ($municipios as $muni) {
                $datosMuni = $datosIndicador->where('municipio_id', $muni->id);
                if ($datosMuni->isNotEmpty() && !$esCategorica) {
                    // Para ordenar: si es porcentaje usamos la primera variable, si es absoluto la suma.
                    $totalParaOrdenar = $esPorcentaje 
                        ? $datosMuni->where('variable_id', $primeraVar->id)->sum('valor')
                        : $datosMuni->sum('valor');
                        
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

            $categoriasEjeY = array_column($municipiosRanking, 'name');

            // Armar series para ECharts
            $seriesEcharts = [];
            $colores = ['#861e34', '#c79b66', '#0a192f', '#444444', '#7a7a7a'];
            $idxColor = 0;

            foreach ($variables as $v) {
                $datosSerie = [];
                foreach ($municipiosRanking as $mRank) {
                    $val = $datosIndicador->where('municipio_id', $mRank['id'])->where('variable_id', $v->id)->first();
                    $datosSerie[] = $val ? (float) $val->valor : 0;
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

            $perfil[$dimensionKey][] = [
                'config' => $config,
                'datos' => [
                    'valor_actual' => $valorDisplay,
                    'total' => $valorRegional,
                    'anio' => $anioMasComun,
                    'unidad' => $primeraVar->unidad_medida ?? '',
                    'fuente' => $indicador->fuente ?? 'N/D',
                    'metodo_calculo' => $indicador->metodo_calculo ?? 'N/D',
                    'variables' => [['unidad' => $primeraVar->unidad_medida ?? '']],
                    'echarts' => [
                        'type' => 'bar-horizontal',
                        'unidad' => $primeraVar->unidad_medida ?? '',
                        'eje_y' => [
                            'categorias' => $categoriasEjeY
                        ],
                        'series' => $seriesEcharts
                    ]
                ],
                'narrativa' => $this->generarNarrativaRegional($config, $region, $valorDisplay, $municipiosRanking, $esPorcentaje, $esCategorica)
            ];
        }

        return [
            'tipoRegion' => $tipoRegion,
            'region' => $region,
            'municipios' => $municipios,
            'poblacionTotal' => $poblacionTotal,
            'superficieTotal' => $superficieKm2,
            'perfil' => $perfil
        ];
    }

    /**
     * Genera un pequeño texto narrativo dinámico para la región
     */
    private function generarNarrativaRegional($config, $region, $valorDisplay, $municipiosRanking, $esPorcentaje, $esCategorica)
    {
        if ($esCategorica || empty($municipiosRanking)) {
            return "El indicador de <strong>{$config->indicador->nombre_amigable}</strong> para esta región es predominantemente: <strong>{$valorDisplay}</strong>.";
        }

        $primero = $municipiosRanking[0];
        $ultimo = $municipiosRanking[count($municipiosRanking) - 1];
        
        $tipo = $esPorcentaje ? 'la mayor aportación' : 'el total acumulado';

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
                ->with('info', 'La información para esta microrregión no se puede desagregar. Te hemos redirigido a la ficha de su municipio.');
        } elseif ($municipiosCount === 0) {
            return redirect()->back()->with('error', 'Microrregión sin datos.');
        }

        $municipiosIds = $microrregion->municipios->pluck('id')->toArray();
        $municipios = $microrregion->municipios()->orderBy('nombre')->get();

        $datos = $this->obtenerDatosPerfil('Microrregión', $microrregion, $municipios, $municipiosIds);
        
        $pdf = \PDF::loadView('regiones.resumen_pdf', $datos);
        return $pdf->download('Resumen_Microrregional_' . str_replace(' ', '_', $microrregion->nombre) . '.pdf');
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
                ->with('info', 'La información para esta microrregión no se puede desagregar. Te hemos redirigido a la ficha de su municipio.');
        } elseif ($municipiosCount === 0) {
            return redirect()->back()->with('error', 'Microrregión sin datos.');
        }

        $municipiosIds = $microrregion->municipios->pluck('id')->toArray();
        $municipios = $microrregion->municipios()->orderBy('nombre')->get();

        $datos = $this->obtenerDatosPerfil('Microrregión', $microrregion, $municipios, $municipiosIds);
        
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RegionExport($datos), 'Resumen_Microrregional_' . str_replace(' ', '_', $microrregion->nombre) . '.xlsx');
    }
}
