<table>
    <thead>
        <tr>
            <th colspan="3" style="font-weight: bold; font-size: 14px; text-align: center;">{{ ucwords(str_replace('_', ' ', $seccion)) }} - {{ $region->nombre }}</th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f3f3f3; width: 300px;">Indicador</th>
            <th style="font-weight: bold; background-color: #f3f3f3; width: 150px;">{{ $tipoRegion === 'Estatal' ? 'Valor Estatal' : 'Valor Regional' }}</th>
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
                if (!empty($item['datos']['ranking'])) {
                    $top5 = array_slice($item['datos']['ranking'], 0, 5);
                    $esPorcentaje = str_contains(strtolower($unidad), '%') || str_contains(strtolower($unidad), 'porcentaje');
                    
                    $rankingLines = [];
                    foreach($top5 as $idx => $rankItem) {
                        $valFormateado = $rankItem['orderValue'];
                        if($esPorcentaje) {
                            $valFormateado = number_format($valFormateado, 2) . '%';
                        } elseif(str_contains(strtolower($unidad), '$') || str_contains(strtolower($unidad), 'pesos')) {
                            $valFormateado = '$' . number_format($valFormateado, 2);
                        } else {
                            $valFormateado = is_numeric($valFormateado) && floor($valFormateado) != $valFormateado ? number_format($valFormateado, 2) : number_format($valFormateado);
                            $valFormateado .= ' ' . $unidad;
                        }
                        $rankingLines[] = ($idx + 1) . ". " . $rankItem['name'] . " (" . $valFormateado . ")";
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
