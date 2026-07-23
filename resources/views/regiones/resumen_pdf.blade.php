<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen Regional - {{ $region->nombre }}</title>
    <style>
        /* --- Configuración General --- */
        @page {
            margin: 100px 50px;
        }

        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.3;
            font-size: 9pt;
        }

        /* --- Encabezado y Pie de Página --- */
        .header,
        .footer {
            width: 100%;
            position: fixed;
            left: 0;
            right: 0;
        }

        .header {
            top: -100px;
            padding-bottom: 5px;
        }

        .footer {
            bottom: -80px;
            font-size: 0.8em;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .footer .page-info {
            text-align: right;
        }

        .footer .page-number-simple:before {
            content: counter(page);
        }

        /* --- Títulos --- */
        .municipio {
            border-bottom: 2px solid #5f1b2d;
        }

        h1 {
            color: #5f1b2d;
            border-bottom: 2px solid #5f1b2d;
            padding-bottom: 10px;
            font-size: 1.8em;
            margin-bottom: 15px;
            page-break-after: avoid;
            font-weight: 700;
        }

        h2 {
            color: #861e34;
            margin-top: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            font-size: 1.1em;
            page-break-after: avoid;
            font-weight: 700;
        }

        h3 {
            color: #246257;
            font-size: 1em;
            page-break-after: avoid;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .meta-info {
            font-size: 1em;
            color: #555;
            margin-bottom: 25px;
        }

        .scope-note {
            border: 1px solid #ead8bd;
            border-left: 4px solid #c79b66;
            background: #fffaf4;
            padding: 8px 10px;
            margin-bottom: 18px;
            font-size: 0.9em;
        }

        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .kpi-grid td {
            width: 50%;
            vertical-align: top;
            padding: 6px;
        }

        .kpi-item {
            border: 0.5px solid #0c312d;
            padding: 0.8rem;
            border-radius: 10px;
            background-color: #fcfcfc;
        }

        .kpi-item .label {
            font-size: 0.9em;
            color: #666;
            margin: 0 0 4px 0;
            font-weight: bold;
        }

        .kpi-item .value-container {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #ccc;
        }

        .kpi-item .value {
            font-size: 1.5em;
            font-weight: 700;
            margin: 0;
            color: #5f1b2d;
        }

        .kpi-item .unit {
            font-size: 0.8em;
            font-weight: 400;
            margin-left: 3px;
            color: #666;
        }

        .kpi-item .ranking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .kpi-item .ranking-table th {
            text-align: left;
            font-size: 0.8em;
            color: #888;
            border-bottom: 1px solid #eee;
            padding-bottom: 2px;
        }

        .kpi-item .ranking-table td {
            font-size: 0.85em;
            color: #333;
            padding: 3px 0;
            vertical-align: top;
        }

        .kpi-item .ranking-table .val-col {
            text-align: right;
            font-weight: bold;
            color: #0c312d;
        }

        /* --- Utilidades --- */
        .page-break {
            page-break-after: always;
        }

        .section-wrapper {
            page-break-inside: avoid;
            margin-bottom: 15px;
        }

        .report-title {
            position: relative;
            top: -30px;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="https://sei.puebla.gob.mx/betafichas/img/Cintillo-SEI.png" alt="Logos" style="width: 100%">
        <p class="report-title"><strong>Ficha de Resumen Regional</strong></p>
    </div>

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td class="page-info">
                    Página <span class="page-number-simple"></span>
                </td>
            </tr>
        </table>
    </div>
    
    <main>
        <h1 class="municipio">{{ $region->nombre }}</h1>
        <p class="meta-info">
            <strong>Tipo de Región:</strong> {{ $tipoRegion }}<br>
            <strong>Población Total:</strong> {{ number_format($poblacionTotal) }} hab.<br>
            <strong>Superficie:</strong> {{ number_format($superficieTotal, 2) }} km²<br>
            <strong>Municipios que la conforman:</strong> {{ $municipios->count() }}
        </p>

        @if($tipoRegion === 'Estatal')
            <div class="scope-note">
                <strong>Alcance territorial:</strong>
                {{ $alcanceTerritorial['macrorregiones_oficiales'] }} macrorregiones oficiales y
                {{ $alcanceTerritorial['microrregiones_oficiales'] }} microrregiones oficiales.
                La información estadística se integra a nivel municipal; los municipios que intersectan varias microrregiones no se dividen ni se duplican.
                Fuente: {{ $alcanceTerritorial['fuente_url'] }}
            </div>
        @endif

        @foreach ($perfil as $seccion => $items)
            @if($seccion != 'general')
                <h2>{{ ucwords(str_replace('_', ' ', $seccion)) }}</h2>

                <table class="kpi-grid">
                    @foreach (collect($items)->chunk(2) as $chunk)
                        <tr>
                            @foreach ($chunk as $item)
                                <td>
                                    <div class="kpi-item section-wrapper">
                                        <p class="label">
                                            {{ $item['config']->titulo_reporte ?? $item['config']->indicador->nombre_amigable }}
                                            @if(isset($item['datos']['anio']) && $item['datos']['anio'])
                                                ({{ $item['datos']['anio'] }})
                                            @endif
                                        </p>
                                        
                                        <div class="value-container">
                                            <p class="value">
                                                {{ $item['datos']['valor_actual'] ?? $item['datos']['total'] ?? 0 }}
                                            </p>
                                        </div>

                                        {{-- Mostrar Top Ranking si hay datos para ello --}}
                                        @if(isset($item['datos']['echarts']['series']) && count($item['datos']['echarts']['series']) > 0)
                                            <p style="font-size: 0.8em; color: #666; margin: 0 0 3px 0; font-weight: bold;">Top 3 Municipios</p>
                                            <table class="ranking-table">
                                                <tbody>
                                                    @php
                                                        // Encontrar la serie principal (la primera)
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
                                                        
                                                        // Tomar el top 3
                                                        $top3 = array_slice($datosOrdenados, 0, 3);
                                                        $unidad = $item['datos']['unidad'] ?? '';
                                                        $esPorcentaje = str_contains(strtolower($unidad), '%') || str_contains(strtolower($unidad), 'porcentaje');
                                                    @endphp
                                                    
                                                    @foreach($top3 as $idx => $rankItem)
                                                        <tr>
                                                            <td>{{ $idx + 1 }}. {{ $rankItem['nombre'] }}</td>
                                                            <td class="val-col">
                                                                @if($esPorcentaje)
                                                                    {{ number_format($rankItem['valor'], 2) }}%
                                                                @elseif(str_contains(strtolower($unidad), '$') || str_contains(strtolower($unidad), 'pesos'))
                                                                    ${{ number_format($rankItem['valor'], 2) }}
                                                                @else
                                                                    {{ is_numeric($rankItem['valor']) && floor($rankItem['valor']) != $rankItem['valor'] ? number_format($rankItem['valor'], 2) : number_format($rankItem['valor']) }} {{ $unidad }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            @endforeach

                            {{-- Rellenar celdas vacías --}}
                            @for ($i = 0; $i < 2 - $chunk->count(); $i++)
                                <td></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            @endif
        @endforeach
    </main>

</body>

</html>
