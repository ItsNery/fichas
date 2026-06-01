<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparativa - {{ $municipio1->nombre }} vs {{ $municipio2->nombre }}</title>
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
        .municipios-title {
            border-bottom: 2px solid #5f1b2d;
            color: #5f1b2d;
            font-size: 1.6em;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .meta-info {
            font-size: 1em;
            color: #555;
            margin-bottom: 20px;
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

        /* --- Tabla de Atributos del Hero --- */
        .hero-comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .hero-comparison-table th {
            background-color: #5f1b2d;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9.5pt;
        }

        .hero-comparison-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }

        .hero-comparison-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .col-concept {
            font-weight: bold;
            color: #555;
            width: 30%;
        }

        .col-muni1 {
            width: 35%;
        }

        .col-muni2 {
            width: 35%;
        }

        /* --- Cuadrícula de KPIs --- */
        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .kpi-grid td {
            width: 50%;
            vertical-align: top;
            padding: 6px;
        }

        .kpi-item {
            border: 0.5px solid #246257;
            padding: 0.5rem;
            border-radius: 8px;
            background-color: #fff;
            min-height: 80px;
        }

        .kpi-item .label {
            font-size: 0.85em;
            color: #555;
            margin: 0 0 5px 0;
            font-weight: bold;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 3px;
        }

        .kpi-split-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kpi-split-table td {
            padding: 0;
            vertical-align: middle;
        }

        .muni-col {
            width: 45%;
        }

        .muni-col--left {
            text-align: left;
        }

        .muni-col--right {
            text-align: right;
        }

        .muni-name {
            font-size: 0.75em;
            color: #777;
            display: block;
            text-transform: uppercase;
        }

        .muni-value {
            font-size: 1.15em;
            font-weight: 700;
            color: #861e34;
        }

        .muni-col--right .muni-value {
            color: #495057;
        }

        .vs-col {
            width: 10%;
            text-align: center;
            font-size: 0.8em;
            color: #999;
            font-weight: bold;
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
        <img src="{{ asset('img/Logos-SPFA-SEI.png') }}" alt="Logos" style="width: 100%">
        <p class="report-title"><strong>Ficha Comparativa Municipal</strong></p>
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
        <h1 class="municipios-title">{{ $municipio1->nombre }} vs {{ $municipio2->nombre }}</h1>
        <p class="meta-info">
            Reporte comparativo de indicadores y toma de decisiones públicas territoriales
        </p>

        {{-- Tabla de Atributos Generales (Hero Stats) --}}
        <table class="hero-comparison-table">
            <thead>
                <tr>
                    <th>Indicador General</th>
                    <th>{{ $municipio1->nombre }}</th>
                    <th>{{ $municipio2->nombre }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-concept">Macrorregión</td>
                    <td>{{ $municipio1->microrregion->macrorregion->nombre ?? 'N/D' }}</td>
                    <td>{{ $municipio2->microrregion->macrorregion->nombre ?? 'N/D' }}</td>
                </tr>
                <tr>
                    <td class="col-concept">Región / Microrregión</td>
                    <td>{{ $municipio1->microrregion->nombre ?? 'N/D' }}</td>
                    <td>{{ $municipio2->microrregion->nombre ?? 'N/D' }}</td>
                </tr>
                <tr>
                    <td class="col-concept">Población Total</td>
                    <td>{{ number_format($hero1['poblacionTotal']) }} hab.</td>
                    <td>{{ number_format($hero2['poblacionTotal']) }} hab.</td>
                </tr>
                <tr>
                    <td class="col-concept">Presupuesto Anual</td>
                    <td>${{ number_format($hero1['presupuesto'], 2) }}</td>
                    <td>${{ number_format($hero2['presupuesto'], 2) }}</td>
                </tr>
                <tr>
                    <td class="col-concept">Población Activa (PEA)</td>
                    <td>{{ number_format($hero1['pea']) }} hab.</td>
                    <td>{{ number_format($hero2['pea']) }} hab.</td>
                </tr>
                <tr>
                    <td class="col-concept">Grado de Marginación</td>
                    <td>{{ $hero1['gradoMarginacion'] }}</td>
                    <td>{{ $hero2['gradoMarginacion'] }}</td>
                </tr>
                <tr>
                    <td class="col-concept">Superficie Territorial</td>
                    <td>{{ number_format($hero1['superficieKm2'], 2) }} km²</td>
                    <td>{{ number_format($hero2['superficieKm2'], 2) }} km²</td>
                </tr>
            </tbody>
        </table>

        {{-- Grid Comparativo de Indicadores Configurados --}}
        @foreach ($comparativa as $seccion => $items)
            <div class="section-wrapper">
                <h2>Dimensiones: {{ ucwords(str_replace('_', ' ', $seccion)) }}</h2>
                <table class="kpi-grid">
                    @foreach (collect($items)->chunk(2) as $chunk)
                        <tr>
                            @foreach ($chunk as $item)
                                @php
                                    $config = $item['config'];
                                    $datos1 = $item['datos1'];
                                    $datos2 = $item['datos2'];
                                @endphp
                                <td>
                                    <div class="kpi-item">
                                        <p class="label">
                                            {{ $config->titulo_reporte ?? $config->indicador->nombre_amigable }}
                                            @if (isset($datos1['anio']))
                                                ({{ $datos1['anio'] }})
                                            @endif
                                        </p>

                                        <table class="kpi-split-table">
                                            <tr>
                                                <td class="muni-col muni-col--left">
                                                    <span
                                                        class="muni-name">{{ Str::limit($municipio1->nombre, 15) }}</span>
                                                    <span class="muni-value">
                                                        {{ $datos1['valor_actual'] ?? ($datos1['total'] ?? 'N/D') }}
                                                    </span>
                                                </td>
                                                <td class="vs-col">vs</td>
                                                <td class="muni-col muni-col--right">
                                                    <span
                                                        class="muni-name">{{ Str::limit($municipio2->nombre, 15) }}</span>
                                                    <span class="muni-value">
                                                        {{ $datos2['valor_actual'] ?? ($datos2['total'] ?? 'N/D') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            @endforeach

                            {{-- Rellenar celdas vacías si es impar --}}
                            @if ($chunk->count() < 2)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    </main>
</body>

</html>
