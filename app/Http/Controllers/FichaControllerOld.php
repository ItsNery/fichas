public function getData1(Request $request)
    {
        // --- SECCIÓN A (MODIFICADA) ---
        // La validación ahora incluye el nivel de agregación y el ID de la región.
        $validated = $request->validate([
            'indicador_id'        => 'required|integer|exists:indicadors,id',
            'nivel_de_agregacion' => 'required|string|in:municipio,microrregion,macrorregion',
            'municipio_ids'       => 'nullable|array',
            'municipio_ids.*'     => 'string',
            'region_id'           => 'nullable|integer',
            'anios'               => 'nullable|array',
            'anios.*'             => 'integer',
        ]);

        $indicador = Indicador::with('variables')->find($validated['indicador_id']);
        $nivel     = $validated['nivel_de_agregacion'];
        // --- FIN DE SECCIÓN A ---

        // --- NUEVO BLOQUE DE PREPARACIÓN ---
        // Este bloque determina la lista de municipios a consultar y el título principal.
        $municipioIdsParaConsulta = [];
        $tituloSeleccion          = '';

        if ($nivel === 'municipio') {
            $municipioIdsParaConsulta = $validated['municipio_ids'] ?? [];
            if (count($municipioIdsParaConsulta) === 1 && $municipioIdsParaConsulta[0] !== 'estatal') {
                $tituloSeleccion = Municipio::find($municipioIdsParaConsulta[0])->nombre;
            } elseif (in_array('estatal', $municipioIdsParaConsulta)) {
                $tituloSeleccion = 'Total Estatal';
            }
        } else { // Nivel es 'microrregion' o 'macrorregion'
            if ($validated['region_id']) {
                if ($nivel === 'microrregion') {
                    $region = Microrregion::with('municipios')->find($validated['region_id']);
                    if ($region) {
                        $tituloSeleccion          = $region->nombre;
                        $municipioIdsParaConsulta = $region->municipios->pluck('id')->all();
                    }
                } elseif ($nivel === 'macrorregion') {
                    $region = Macrorregion::with('microrregiones.municipios')->find($validated['region_id']);
                    if ($region) {
                        $tituloSeleccion          = $region->nombre;
                        $municipioIdsParaConsulta = $region->microrregiones->flatMap(fn($micro) => $micro->municipios)->pluck('id')->all();
                    }
                }
            }
        }
        // --- FIN DEL NUEVO BLOQUE ---

        // --- AQUÍ VA LA SECCIÓN B (SIN CAMBIOS) ---
        // Pega tu bloque de código para el caso especial de la Pirámide Poblacional aquí.
        // Solo ajusta la condición inicial para que use las nuevas variables.
        if ($indicador->id == 2 && $nivel === 'municipio' && count($validated['municipio_ids'] ?? []) === 1) {
            $municipioId  = $validated['municipio_ids'][0];
            $anioConsulta = null;

            $mapaPiramide = [
                '100 o más años' => ['hom' => 'H_de_100_o_mas_años', 'muj' => 'M_de_100_o_mas_años'],
                '95 a 99 años'   => ['hom' => 'H_de_95_a_99_años', 'muj' => 'M_de_95_a_99_años'],
                '90 a 94 años'   => ['hom' => 'H_de_90_a_94_años', 'muj' => 'M_de_90_a_94_años'],
                '85 a 89 años'   => ['hom' => 'H_de_85_a_89_años', 'muj' => 'M_de_85_a_89_años'],
                '80 a 84 años'   => ['hom' => 'H_de_80_a_84_años', 'muj' => 'M_de_80_a_84_años'],
                '75 a 79 años'   => ['hom' => 'H_de_75_a_79_años', 'muj' => 'M_de_75_a_79_años'],
                '70 a 74 años'   => ['hom' => 'H_de_70_a_74_años', 'muj' => 'M_de_70_a_74_años'],
                '65 a 69 años'   => ['hom' => 'H_de_65_a_69_años', 'muj' => 'M_de_65_a_69_años'],
                '60 a 64 años'   => ['hom' => 'H_de_60_a_64_años', 'muj' => 'M_de_60_a_64_años'],
                '55 a 59 años'   => ['hom' => 'H_de_55_a_59_años', 'muj' => 'M_de_55_a_59_años'],
                '50 a 54 años'   => ['hom' => 'H_de_50_a_54_años', 'muj' => 'M_de_50_a_54_años'],
                '45 a 49 años'   => ['hom' => 'H_de_45_a_49_años', 'muj' => 'M_de_45_a_49_años'],
                '40 a 44 años'   => ['hom' => 'H_de_40_a_44_años', 'muj' => 'M_de_40_a_44_años'],
                '35 a 39 años'   => ['hom' => 'H_de_35_a_39_años', 'muj' => 'M_de_35_a_39_años'],
                '30 a 34 años'   => ['hom' => 'H_de_30_a_34_años', 'muj' => 'M_de_30_a_34_años'],
                '25 a 29 años'   => ['hom' => 'H_de_25_a_29_años', 'muj' => 'M_de_25_a_29_años'],
                '20 a 24 años'   => ['hom' => 'H_de_20_a_24_años', 'muj' => 'M_de_20_a_24_años'],
                '15 a 19 años'   => ['hom' => 'H_de_15_a_19_años', 'muj' => 'M_de_15_a_19_años'],
                '10 a 14 años'   => ['hom' => 'H_de_10_a_14_años', 'muj' => 'M_de_10_a_14_años'],
                '5 a 9 años'     => ['hom' => 'H_de_5_a_9_años', 'muj' => 'M_de_5_a_9_años'],
                '0 a 4 años'     => ['hom' => 'H_de_0_a_4_años', 'muj' => 'M_de_0_a_4_años'],
                'No especificó'  => ['hom' => 'H_NE', 'muj' => 'M_NE'],
            ];

            $nombresTecnicos = collect($mapaPiramide)->flatten()->unique()->all();
            $variables       = Variable::whereIn('nombre_tecnico', $nombresTecnicos)->get()->keyBy('nombre_tecnico');
            $hombresData     = [];
            $mujeresData     = [];
            $categorias      = array_keys($mapaPiramide);

            foreach ($mapaPiramide as $grupo) {
                // --- LÓGICA PARA HOMBRES ---
                $varHom = $variables[$grupo['hom']] ?? null;
                if ($varHom) {
                    $queryHom = DatoHistorico::where('variable_id', $varHom->id);
                    if ($municipioId === 'estatal') {
                        $latestYear = DatoHistorico::where('variable_id', $varHom->id)->max('anio');
                        $valor      = $latestYear ? $queryHom->where('anio', $latestYear)->sum('valor') : 0;
                        if ($latestYear && ! $anioConsulta) {
                            $anioConsulta = $latestYear;
                        }
                    } else {
                        $dato  = $queryHom->where('municipio_id', $municipioId)->orderBy('anio', 'desc')->first();
                        $valor = $dato->valor ?? 0;
                        if ($dato && ! $anioConsulta) {
                            $anioConsulta = $dato->anio;
                        }
                    }
                    $hombresData[] = -$valor;
                } else {
                    $hombresData[] = 0;
                }

                // --- LÓGICA PARA MUJERES ---
                $varMuj = $variables[$grupo['muj']] ?? null;
                if ($varMuj) {
                    $queryMuj = DatoHistorico::where('variable_id', $varMuj->id);
                    if ($municipioId === 'estatal') {
                        $latestYear = DatoHistorico::where('variable_id', $varMuj->id)->max('anio');
                        $valor      = $latestYear ? $queryMuj->where('anio', $latestYear)->sum('valor') : 0;
                    } else {
                        $dato  = $queryMuj->where('municipio_id', $municipioId)->orderBy('anio', 'desc')->first();
                        $valor = $dato->valor ?? 0;
                    }
                    $mujeresData[] = (float) $valor;
                } else {
                    $mujeresData[] = 0;
                }
            }

            $tituloSeleccion = ($municipioId === 'estatal') ? 'Total Estatal' : Municipio::find($municipioId)->nombre;
            return response()->json([
                'titulo'         => $indicador->nombre_amigable . " - " . $tituloSeleccion . " (" . ($anioConsulta ?: 'N/D') . ")",
                'descripcion'    => $indicador->descripcion,
                'fuente'         => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
                'tipo_grafico'   => 'piramide',
                'series'         => [['name' => 'Hombres', 'data' => $hombresData], ['name' => 'Mujeres', 'data' => $mujeresData]],
                'eje_x'          => ['categorias' => $categorias],
                'eje_y'          => ['titulo' => 'Habitantes'],
            ]);
        }

        // --- ENRUTAMIENTO LÓGICO PRINCIPAL ---

        // CASO A: Comparación de 2 municipios.
        elseif ($nivel === 'municipio' && count($municipioIdsParaConsulta) > 1) {
            $nombresMunicipios = Municipio::whereIn('id', $municipioIdsParaConsulta)->pluck('nombre', 'id')->all();
            $variableIds       = $indicador->variables->pluck('id');
            $selectedYears     = $validated['anios'] ?? [];

            // Sub-caso 2.1: Comparación en MÚLTIPLES años -> Gráfico de Líneas
            if (count($selectedYears) > 1) {
                // CASO 1: COMPARACIÓN DE MÚLTIPLES MUNICIPIOS EN MÚLTIPLES AÑOS -> GRÁFICO DE LÍNEAS

                // --- INICIO DE LA CORRECCIÓN ---
                // 1. Identificamos la variable "Total" o principal para la comparación.
                $variablePrincipal = $indicador->variables->first(function ($variable) {
                    // Buscamos una variable cuyo nombre contenga "total".
                    return str_contains(strtolower($variable->nombre_amigable), 'total');
                });

                // 2. Si no encontramos una variable "Total", usamos la primera como un fallback lógico.
                if (! $variablePrincipal) {
                    $variablePrincipal = $indicador->variables->first();
                }

                // El ID de la única variable que vamos a graficar.
                $variableIdToUse = $variablePrincipal->id;
                // --- FIN DE LA CORRECCIÓN ---

                $yearsToUse = $selectedYears;
                sort($yearsToUse);

                $seriesParaGrafico = [];
                foreach ($municipioIdsParaConsulta as $munId) {
                    $dataPoints = [];
                    foreach ($yearsToUse as $year) {
                        // 3. Modificamos la consulta para usar SOLO el ID de la variable principal.
                        // Usamos ->value() porque esperamos un único resultado.
                        $valor = DatoHistorico::where('variable_id', $variableIdToUse)
                            ->where('municipio_id', $munId)
                            ->where('anio', $year)
                            ->value('valor');

                        $dataPoints[] = $valor !== null ? (float) $valor : 0;
                    }
                    $seriesParaGrafico[] = ['name' => $nombresMunicipios[$munId], 'data' => $dataPoints];
                }

                $availableYears = DatoHistorico::whereIn('variable_id', $variableIds)->whereIn('municipio_id', $municipioIdsParaConsulta)
                    ->select('anio')->groupBy('anio')->havingRaw('COUNT(DISTINCT municipio_id) >= ?', [count($municipioIdsParaConsulta)])
                    ->orderBy('anio', 'desc')->pluck('anio');

                return response()->json([
                    // En el título, aclaramos que se muestra la variable principal.
                    'titulo'           => $indicador->nombre_amigable . " (" . $variablePrincipal->nombre_amigable . " - Tendencia Comparativa)",
                    'tipo_grafico'     => 'line',
                    'series'           => $seriesParaGrafico,
                    'available_years'  => $availableYears,
                    'selected_years'   => $yearsToUse,
                    'eje_x'            => ['type' => 'category', 'categorias' => $yearsToUse, 'titulo' => 'Año'],
                    'eje_y'            => ['titulo' => $variablePrincipal->unidad_medida ?? 'Valor'],
                    'descripcion'      => $indicador->descripcion,
                    'fuente'           => $indicador->fuente,
                    'nota_explicativa' => 'Nota: Para la comparación de tendencias entre municipios, se utiliza la variable principal del indicador (' . $variablePrincipal->nombre_amigable . ').',
                ]);
            }

            // Sub-caso 2.2: Comparación en UN SOLO año (o carga inicial) -> Gráfico de Barras
            else {
                $availableYears = DatoHistorico::whereIn('variable_id', $variableIds)->whereIn('municipio_id', $municipioIdsParaConsulta)
                    ->select('anio')->groupBy('anio')->havingRaw('COUNT(DISTINCT municipio_id) = ?', [count($municipioIdsParaConsulta)])
                    ->orderBy('anio', 'desc')->pluck('anio');

                $yearToQuery       = ! empty($selectedYears) ? $selectedYears[0] : $availableYears->first();
                $seriesParaGrafico = []; // Se inicializa para evitar errores

                if ($yearToQuery) {
                    foreach ($indicador->variables as $variable) {
                        $valores = [];
                        foreach ($municipioIdsParaConsulta as $munId) {
                            $dato      = DatoHistorico::where('variable_id', $variable->id)->where('municipio_id', $munId)->where('anio', $yearToQuery)->first();
                            $valores[] = $dato ? (float) $dato->valor : 0;
                        }
                        $seriesParaGrafico[] = ['name' => $variable->nombre_amigable, 'data' => $valores];
                    }
                }

                return response()->json([
                    'titulo'          => $indicador->nombre_amigable . ($yearToQuery ? " (Comparación Año: $yearToQuery)" : " (Sin años en común para comparar)"),
                    'tipo_grafico'    => 'bar',
                    'series'          => $seriesParaGrafico,
                    'available_years' => $availableYears,
                    'selected_years'  => $yearToQuery ? [$yearToQuery] : [],
                    'eje_x'           => ['categorias' => array_values($nombresMunicipios)],
                    'eje_y'           => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
                    'descripcion'     => $indicador->descripcion,
                    'fuente'          => $indicador->fuente,
                ]);
            }
        }

        // CASO B: Vista Única o Regional (lógica de agregación).
        else {
            if (empty($municipioIdsParaConsulta) && ! in_array('estatal', $validated['municipio_ids'] ?? [])) {
                return response()->json(['series' => [], 'titulo' => 'Selecciona una opción para continuar.']);
            }

            $selectedYears   = $validated['anios'] ?? [];
            $seriesCompletas = [];

            foreach ($indicador->variables as $variable) {
                $query = DatoHistorico::where('variable_id', $variable->id);

                // CORRECCIÓN: La consulta ahora distingue entre mostrar datos de un solo municipio (sin SUM)
                // y agregar datos para 'estatal' o una región (con SUM).
                if ($nivel === 'municipio' && ! in_array('estatal', $municipioIdsParaConsulta)) {
                    $query->where('municipio_id', $municipioIdsParaConsulta[0]);
                    $datosHistoricos = $query->orderBy('anio', 'asc')->get();
                } else {
                    if (! in_array('estatal', $municipioIdsParaConsulta)) {
                        $query->whereIn('municipio_id', $municipioIdsParaConsulta);
                    }
                    $datosHistoricos = $query->selectRaw('anio, SUM(valor) as valor')
                        ->groupBy('anio')
                        ->orderBy('anio', 'asc')
                        ->get();
                }

                $dataPoints        = $datosHistoricos->map(fn($dato) => [(int) $dato->anio, (float) $dato->valor]);
                $seriesCompletas[] = ['name' => $variable->nombre_amigable, 'data' => $dataPoints];
            }

            // El resto de tu lógica para decidir el gráfico y formatear las series es correcta.
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
                if (strtolower($indicador->tipo_grafico_default) === 'barras') {
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

            return response()->json([
                'titulo'          => $indicador->nombre_amigable . " - " . $tituloSeleccion . " ($chartTitleYear)",
                'descripcion'     => $indicador->descripcion,
                'fuente'          => $indicador->fuente,
                'tipo_grafico'    => $tipoGraficoFinal,
                'series'          => $seriesFinales,
                'eje_x'           => $ejeXFinal,
                'eje_y'           => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
                'available_years' => $availableYears,
                'selected_years'  => $yearsToUse,
            ]);
        }
    }

    public function getData2(Request $request)
    {
        // SECCIÓN A
        // =================================================================
        $validated = $request->validate([
            'indicador_id'    => 'required|integer|exists:indicadors,id',
            'municipio_ids'   => 'required|array|min:1',
            'municipio_ids.*' => 'string',
            'anios'           => 'nullable|array',
            'anios.*'         => 'integer',
        ]);

        $indicador    = Indicador::with('variables')->find($validated['indicador_id']);
        $municipioIds = $validated['municipio_ids'];
        // =================================================================
        // SECCIÓN B
        // =================================================================
        $esComparativo = count($municipioIds) > 1;

        // --- CASO 1: PIRÁMIDE POBLACIONAL (Excepción auto-contenida) ---
        if ($indicador->id == 2 && ! $esComparativo) {
            $municipioId  = $municipioIds[0];
            $anioConsulta = null;

            $mapaPiramide = [
                '100 o más años' => ['hom' => 'H_de_100_o_mas_años', 'muj' => 'M_de_100_o_mas_años'],
                '95 a 99 años'   => ['hom' => 'H_de_95_a_99_años', 'muj' => 'M_de_95_a_99_años'],
                '90 a 94 años'   => ['hom' => 'H_de_90_a_94_años', 'muj' => 'M_de_90_a_94_años'],
                '85 a 89 años'   => ['hom' => 'H_de_85_a_89_años', 'muj' => 'M_de_85_a_89_años'],
                '80 a 84 años'   => ['hom' => 'H_de_80_a_84_años', 'muj' => 'M_de_80_a_84_años'],
                '75 a 79 años'   => ['hom' => 'H_de_75_a_79_años', 'muj' => 'M_de_75_a_79_años'],
                '70 a 74 años'   => ['hom' => 'H_de_70_a_74_años', 'muj' => 'M_de_70_a_74_años'],
                '65 a 69 años'   => ['hom' => 'H_de_65_a_69_años', 'muj' => 'M_de_65_a_69_años'],
                '60 a 64 años'   => ['hom' => 'H_de_60_a_64_años', 'muj' => 'M_de_60_a_64_años'],
                '55 a 59 años'   => ['hom' => 'H_de_55_a_59_años', 'muj' => 'M_de_55_a_59_años'],
                '50 a 54 años'   => ['hom' => 'H_de_50_a_54_años', 'muj' => 'M_de_50_a_54_años'],
                '45 a 49 años'   => ['hom' => 'H_de_45_a_49_años', 'muj' => 'M_de_45_a_49_años'],
                '40 a 44 años'   => ['hom' => 'H_de_40_a_44_años', 'muj' => 'M_de_40_a_44_años'],
                '35 a 39 años'   => ['hom' => 'H_de_35_a_39_años', 'muj' => 'M_de_35_a_39_años'],
                '30 a 34 años'   => ['hom' => 'H_de_30_a_34_años', 'muj' => 'M_de_30_a_34_años'],
                '25 a 29 años'   => ['hom' => 'H_de_25_a_29_años', 'muj' => 'M_de_25_a_29_años'],
                '20 a 24 años'   => ['hom' => 'H_de_20_a_24_años', 'muj' => 'M_de_20_a_24_años'],
                '15 a 19 años'   => ['hom' => 'H_de_15_a_19_años', 'muj' => 'M_de_15_a_19_años'],
                '10 a 14 años'   => ['hom' => 'H_de_10_a_14_años', 'muj' => 'M_de_10_a_14_años'],
                '5 a 9 años'     => ['hom' => 'H_de_5_a_9_años', 'muj' => 'M_de_5_a_9_años'],
                '0 a 4 años'     => ['hom' => 'H_de_0_a_4_años', 'muj' => 'M_de_0_a_4_años'],
                'No especificó'  => ['hom' => 'H_NE', 'muj' => 'M_NE'],
            ];

            $nombresTecnicos = collect($mapaPiramide)->flatten()->unique()->all();
            $variables       = Variable::whereIn('nombre_tecnico', $nombresTecnicos)->get()->keyBy('nombre_tecnico');
            $hombresData     = [];
            $mujeresData     = [];
            $categorias      = array_keys($mapaPiramide);

            foreach ($mapaPiramide as $grupo) {
                // --- LÓGICA PARA HOMBRES ---
                $varHom = $variables[$grupo['hom']] ?? null;
                if ($varHom) {
                    $queryHom = DatoHistorico::where('variable_id', $varHom->id);
                    if ($municipioId === 'estatal') {
                        $latestYear = DatoHistorico::where('variable_id', $varHom->id)->max('anio');
                        $valor      = $latestYear ? $queryHom->where('anio', $latestYear)->sum('valor') : 0;
                        if ($latestYear && ! $anioConsulta) {
                            $anioConsulta = $latestYear;
                        }
                    } else {
                        $dato  = $queryHom->where('municipio_id', $municipioId)->orderBy('anio', 'desc')->first();
                        $valor = $dato->valor ?? 0;
                        if ($dato && ! $anioConsulta) {
                            $anioConsulta = $dato->anio;
                        }
                    }
                    $hombresData[] = -$valor;
                } else {
                    $hombresData[] = 0;
                }

                // --- LÓGICA PARA MUJERES ---
                $varMuj = $variables[$grupo['muj']] ?? null;
                if ($varMuj) {
                    $queryMuj = DatoHistorico::where('variable_id', $varMuj->id);
                    if ($municipioId === 'estatal') {
                        $latestYear = DatoHistorico::where('variable_id', $varMuj->id)->max('anio');
                        $valor      = $latestYear ? $queryMuj->where('anio', $latestYear)->sum('valor') : 0;
                    } else {
                        $dato  = $queryMuj->where('municipio_id', $municipioId)->orderBy('anio', 'desc')->first();
                        $valor = $dato->valor ?? 0;
                    }
                    $mujeresData[] = (float) $valor;
                } else {
                    $mujeresData[] = 0;
                }
            }

            $tituloSeleccion = ($municipioId === 'estatal') ? 'Total Estatal' : Municipio::find($municipioId)->nombre;
            return response()->json([
                'titulo'         => $indicador->nombre_amigable . " - " . $tituloSeleccion . " (" . ($anioConsulta ?: 'N/D') . ")",
                'descripcion'    => $indicador->descripcion,
                'fuente'         => $indicador->fuente,
                'metodo_calculo' => $indicador->metodo_calculo,
                'tipo_grafico'   => 'piramide',
                'series'         => [['name' => 'Hombres', 'data' => $hombresData], ['name' => 'Mujeres', 'data' => $mujeresData]],
                'eje_x'          => ['categorias' => $categorias],
                'eje_y'          => ['titulo' => 'Habitantes'],
            ]);
        }
        // =================================================================
        // SECCIÓN C
        // =================================================================
        elseif ($esComparativo) {
            $nombresMunicipios = Municipio::whereIn('id', $municipioIds)->pluck('nombre', 'id')->all();
            $variableIds       = $indicador->variables->pluck('id');
            $selectedYears     = $validated['anios'] ?? [];

            // Sub-caso 2.1: Comparación en MÚLTIPLES años -> Gráfico de Líneas
            if (count($selectedYears) > 1) {
                // CASO 1: COMPARACIÓN DE MÚLTIPLES MUNICIPIOS EN MÚLTIPLES AÑOS -> GRÁFICO DE LÍNEAS

                // --- INICIO DE LA CORRECCIÓN ---
                // 1. Identificamos la variable "Total" o principal para la comparación.
                $variablePrincipal = $indicador->variables->first(function ($variable) {
                    // Buscamos una variable cuyo nombre contenga "total".
                    return str_contains(strtolower($variable->nombre_amigable), 'total');
                });

                // 2. Si no encontramos una variable "Total", usamos la primera como un fallback lógico.
                if (! $variablePrincipal) {
                    $variablePrincipal = $indicador->variables->first();
                }

                // El ID de la única variable que vamos a graficar.
                $variableIdToUse = $variablePrincipal->id;
                // --- FIN DE LA CORRECCIÓN ---

                $yearsToUse = $selectedYears;
                sort($yearsToUse);

                $seriesParaGrafico = [];
                foreach ($municipioIds as $munId) {
                    $dataPoints = [];
                    foreach ($yearsToUse as $year) {
                        // 3. Modificamos la consulta para usar SOLO el ID de la variable principal.
                        // Usamos ->value() porque esperamos un único resultado.
                        $valor = DatoHistorico::where('variable_id', $variableIdToUse)
                            ->where('municipio_id', $munId)
                            ->where('anio', $year)
                            ->value('valor');

                        $dataPoints[] = $valor !== null ? (float) $valor : 0;
                    }
                    $seriesParaGrafico[] = ['name' => $nombresMunicipios[$munId], 'data' => $dataPoints];
                }

                $availableYears = DatoHistorico::whereIn('variable_id', $variableIds)->whereIn('municipio_id', $municipioIds)
                    ->select('anio')->groupBy('anio')->havingRaw('COUNT(DISTINCT municipio_id) >= ?', [count($municipioIds)])
                    ->orderBy('anio', 'desc')->pluck('anio');

                return response()->json([
                    // En el título, aclaramos que se muestra la variable principal.
                    'titulo'           => $indicador->nombre_amigable . " (" . $variablePrincipal->nombre_amigable . " - Tendencia Comparativa)",
                    'tipo_grafico'     => 'line',
                    'series'           => $seriesParaGrafico,
                    'available_years'  => $availableYears,
                    'selected_years'   => $yearsToUse,
                    'eje_x'            => ['type' => 'category', 'categorias' => $yearsToUse, 'titulo' => 'Año'],
                    'eje_y'            => ['titulo' => $variablePrincipal->unidad_medida ?? 'Valor'],
                    'descripcion'      => $indicador->descripcion,
                    'fuente'           => $indicador->fuente,
                    'nota_explicativa' => 'Nota: Para la comparación de tendencias entre municipios, se utiliza la variable principal del indicador (' . $variablePrincipal->nombre_amigable . ').',
                ]);
            }

            // Sub-caso 2.2: Comparación en UN SOLO año (o carga inicial) -> Gráfico de Barras
            else {
                $availableYears = DatoHistorico::whereIn('variable_id', $variableIds)->whereIn('municipio_id', $municipioIds)
                    ->select('anio')->groupBy('anio')->havingRaw('COUNT(DISTINCT municipio_id) = ?', [count($municipioIds)])
                    ->orderBy('anio', 'desc')->pluck('anio');

                $yearToQuery       = ! empty($selectedYears) ? $selectedYears[0] : $availableYears->first();
                $seriesParaGrafico = []; // Se inicializa para evitar errores

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

                return response()->json([
                    'titulo'          => $indicador->nombre_amigable . ($yearToQuery ? " (Comparación Año: $yearToQuery)" : " (Sin años en común para comparar)"),
                    'tipo_grafico'    => 'bar',
                    'series'          => $seriesParaGrafico,
                    'available_years' => $availableYears,
                    'selected_years'  => $yearToQuery ? [$yearToQuery] : [],
                    'eje_x'           => ['categorias' => array_values($nombresMunicipios)],
                    'eje_y'           => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
                    'descripcion'     => $indicador->descripcion,
                    'fuente'          => $indicador->fuente,
                ]);
            }
        }
        // =================================================================
        // SECCIÓN D
        // =================================================================
        else {
            $municipioId     = $municipioIds[0];
            $selectedYears   = $validated['anios'] ?? [];
            $tituloSeleccion = ($municipioId === 'estatal') ? 'Total Estatal' : Municipio::find($municipioId)->nombre;

            $seriesCompletas = [];
            foreach ($indicador->variables as $variable) {
                $query = DatoHistorico::where('variable_id', $variable->id);
                if ($municipioId === 'estatal') {
                    $query->selectRaw('anio, SUM(valor) as valor')->groupBy('anio');
                } else {
                    $query->where('municipio_id', $municipioId);
                }
                $datosHistoricos   = $query->orderBy('anio', 'asc')->get();
                $dataPoints        = $datosHistoricos->map(fn($dato) => [(int) $dato->anio, (float) $dato->valor]);
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
                if (strtolower($indicador->tipo_grafico_default) === 'barras') {
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

            return response()->json([
                'titulo'          => $indicador->nombre_amigable . " - " . $tituloSeleccion . " ($chartTitleYear)",
                'descripcion'     => $indicador->descripcion,
                'fuente'          => $indicador->fuente,
                'tipo_grafico'    => $tipoGraficoFinal,
                'series'          => $seriesFinales,
                'eje_x'           => $ejeXFinal,
                'eje_y'           => ['titulo' => $indicador->variables->first()->unidad_medida ?? 'Valor'],
                'available_years' => $availableYears,
                'selected_years'  => $yearsToUse,
            ]);
        }
        // =================================================================
    }