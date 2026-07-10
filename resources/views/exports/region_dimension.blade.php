<table>
    <thead>
        <tr>
            <th colspan="3" style="font-weight: bold; font-size: 14px; text-align: center;">{{ ucwords(str_replace('_', ' ', $seccion)) }} - {{ $region->nombre }}</th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f3f3f3; width: 300px;">Indicador</th>
            <th style="font-weight: bold; background-color: #f3f3f3; width: 150px;">Valor Regional</th>
            <th style="font-weight: bold; background-color: #f3f3f3; width: 400px;">Ranking Municipal (Top 5)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            @php
                $indicadorNombre = $item['config']->titulo_reporte ?? $item['config']->indicador->nombre_amigable;
                $anio = $item['datos']['anio'] ?? '';
                $valorRegional = $item['datos']['valor_actual'] ?? $item['datos']['total'] ?? 0;
                $unidad = $item['datos']['unidad'] ?? '';
                
                $rankingStr = "";
                if(isset($item['datos']['echarts']['series']) && count($item['datos']['echarts']['series']) > 0) {
                    $serie = $item['datos']['echarts']['series'][0];
                    $categorias = $item['datos']['echarts']['eje_y']['categorias'] ?? [];
                    $datosOrdenados = [];
                    
                    foreach($categorias as $idx => $muniNombre) {
                        $datosOrdenados[] = [
                            'nombre' => $muniNombre,
                            'valor' => $serie['data'][$idx] ?? 0
                        ];
                    }
                    
                    // Ordenar de mayor a menor
                    usort($datosOrdenados, function($a, $b) {
                        return $b['valor'] <=> $a['valor'];
                    });
                    
                    // Tomar el top 5
                    $top5 = array_slice($datosOrdenados, 0, 5);
                    $esPorcentaje = str_contains(strtolower($unidad), '%') || str_contains(strtolower($unidad), 'porcentaje');
                    
                    $rankingLines = [];
                    foreach($top5 as $idx => $rankItem) {
                        $valFormateado = $rankItem['valor'];
                        if($esPorcentaje) {
                            $valFormateado = number_format($valFormateado, 2) . '%';
                        } elseif(str_contains(strtolower($unidad), '$') || str_contains(strtolower($unidad), 'pesos')) {
                            $valFormateado = '$' . number_format($valFormateado, 2);
                        } else {
                            $valFormateado = is_numeric($valFormateado) && floor($valFormateado) != $valFormateado ? number_format($valFormateado, 2) : number_format($valFormateado);
                            $valFormateado .= ' ' . $unidad;
                        }
                        $rankingLines[] = ($idx + 1) . ". " . $rankItem['nombre'] . " (" . $valFormateado . ")";
                    }
                    $rankingStr = implode(" | ", $rankingLines);
                }
            @endphp
            <tr>
                <td>{{ $indicadorNombre }} {{ $anio ? "($anio)" : "" }}</td>
                <td>{{ is_numeric($valorRegional) ? number_format((float)$valorRegional, 2) : $valorRegional }} {{ $unidad }}</td>
                <td>{{ $rankingStr }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
