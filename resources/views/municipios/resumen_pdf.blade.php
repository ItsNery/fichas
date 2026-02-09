<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen Municipal - {{ $municipio->nombre }}</title>
    <style>
        /* --- FUENTES (Optimización recomendada) --- */
        @font-face {
            font-family: 'Gilroy';
            src: url(data:font/truetype;charset=utf-8;base64,TU_FONT_REGULAR_EN_BASE64) format("truetype");
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: 'Gilroy';
            src: url(data:font/truetype;charset=utf-8;base64,TU_FONT_BOLD_EN_BASE64) format("truetype");
            font-weight: 700;
            font-style: normal;
        }

        /* --- Configuración General --- */
        @page {
            /* Márgenes estándar, el -100px del header lo corrige */
            margin: 100px 50px;
        }

        body {
            font-family: 'Gilroy', 'Arial', sans-serif;
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
            border-bottom: 1px solid #eee;
            font-size: 1.1em;
            page-break-after: avoid;
            font-weight: 700;
        }

        .meta-info {
            font-size: 1em;
            color: #555;
            margin-bottom: 25px;
        }

        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .kpi-grid td {
            width: 33.33%;
            vertical-align: top;
            padding: 6px;
            /* Padding mínimo */
        }

        .kpi-item {
            border: 0.5px solid #0c312d;
            padding: 0.5rem;
            border-radius: 10px;
        }

        .kpi-item .label {
            font-size: 0.9em;
            color: #666;
            margin: 0 0 2px 0;
            font-weight: 400;
        }

        .kpi-item .value {
            font-size: 1.3em;
            font-weight: 700;
            margin: 0;
            color: #0c312d;
        }

        .kpi-item .unit {
            font-size: 0.8em;
            font-weight: 400;
            margin-left: 3px;
            color: #666;
        }

        .kpi-item .instrument-table {
            width: 100%;
            margin-top: 5px;
            /* Espacio entre label y tabla */
            border-collapse: collapse;
        }

        .kpi-item .instrument-table td {
            font-size: 0.9em;
            /* Un poco más pequeño para que quepa */
            color: #333;
            padding: 4px 0;
            border-top: 1px solid #f0f0f0;
            /* Borde sutil */
            vertical-align: top;
            line-height: 1.2;
        }

        .kpi-item .instrument-table tr:first-child td {
            border-top: none;
            /* Sin borde en el primer elemento */
        }

        .kpi-item .instrument-table .year-col {
            text-align: right;
            font-weight: 700;
            color: #0c312d;
            width: 40px;
            /* Ancho fijo para el año */
            padding-left: 5px;
        }

        /* --- Utilidades --- */
        .page-break {
            page-break-after: always;
        }

        .section-wrapper {
            page-break-inside: avoid;
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
        <p class="report-title"><strong>Ficha de Resumen Municipal</strong></p>
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
        <h1 class="municipio">{{ $municipio->nombre }}</h1>
        <p class="meta-info">
            <strong>Región:</strong> {{ $municipio->microrregion->macrorregion->nombre }} /
            {{ $municipio->microrregion->nombre }}
        </p>

        @foreach ($datosAgrupados as $dimensionData)
            
            <h2>{{ $dimensionData['nombre'] }}</h2>

            @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
                <div class="section-wrapper">
                    <h3>{{ $tematica }}</h3>

                    <table class="kpi-grid">
                        @foreach (collect($kpis)->chunk(3) as $chunk)
                            <tr>
                                @foreach ($chunk as $kpi)
                                    <td>
                                        
                                        @if ($kpi['valor'] === 'lista' && !empty($kpi['valor_display']) && $kpi['valor_display']->count() > 0)
                                            
                                            {{-- CASO A: Es nuestro KPI especial de Instrumentos --}}
                                            <div class="kpi-item">
                                                <p class="label">{{ $kpi['nombre'] }}</p>
                                                
                                                <table class="instrument-table">
                                                    <tbody>
                                                        @foreach ($kpi['valor_display'] as $instrumento)
                                                            <tr>
                                                                <td>{{ $instrumento->nombre }}</td>
                                                                <td class="year-col">{{ $instrumento->pivot->anio }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                
                                        @else
                                            
                                            {{-- CASO B: Es un KPI normal --}}
                                            <div class="kpi-item">
                                                <p class="label">{{ $kpi['nombre'] }} ({{ $kpi['anio'] }})</p>
                                                <p class="value">
                                                    {{ is_numeric($kpi['valor_display']) ? number_format($kpi['valor_display'], 2) : $kpi['valor_display'] }}
                                
                                                    @if (!empty($kpi['unidad']))
                                                        @if ($kpi['unidad'] == 'Porcentaje')
                                                            <span class="unit">%</span>
                                                        @else
                                                            <span class="unit">{{ $kpi['unidad'] }}</span>
                                                        @endif
                                                    @endif
                                                </p>
                                            </div>
                                
                                        @endif
                                
                                        </td>
                                @endforeach

                                {{-- Rellenar celdas vacías --}}
                                @for ($i = 0; $i < 3 - $chunk->count(); $i++)
                                    <td></td>
                                @endfor
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        @endforeach
    </main>

</body>

</html>
