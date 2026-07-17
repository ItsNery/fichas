<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $municipio->nombre }} — Resumen Municipal</title>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    @php
        $fontRegular = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Regular.ttf')));
        $fontBold = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Bold.ttf')));
        $logoInstitucional = base64_encode(file_get_contents(public_path('img/Logos-SPFA-SEI.png')));
    @endphp
    <style>
        @font-face {
            font-family: 'Corra Montserra';
            src: url(data:font/ttf;base64,{{ $fontRegular }}) format('truetype');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Corra Montserra';
            src: url(data:font/ttf;base64,{{ $fontBold }}) format('truetype');
            font-weight: 600 900;
        }

        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Corra Montserra', Montserrat, Arial, sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            line-height: 1.4;
            background: #e5e7eb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .hoja {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 12mm;
            margin: 0 auto;
            background: #fff;
            page-break-after: always;
            box-shadow: 0 1px 8px rgba(0,0,0,0.12);
            position: relative;
            overflow: visible;
        }
        .hoja:last-child { page-break-after: avoid; }

        .pdf-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8mm;
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 2.5pt solid #861e34;
        }
        .pdf-header__logo {
            display: block;
            width: 78mm;
            max-height: 18mm;
            object-fit: contain;
            object-position: left center;
        }
        .pdf-header__identity {
            flex: 1;
            text-align: right;
        }
        .pdf-header h1 {
            font-size: 18pt;
            font-weight: 800;
            color: #861e34;
            margin: 0 0 1mm 0;
            letter-spacing: -0.3pt;
        }
        .pdf-header p {
            font-size: 8pt;
            color: #6b7280;
            margin: 0;
        }

        .stats-bar {
            display: flex;
            justify-content: space-between;
            background: #f3f0eb;
            padding: 2.5mm 4mm;
            border-radius: 3pt;
            margin-bottom: 5mm;
            font-size: 7.5pt;
            font-weight: 600;
            color: #374151;
            border-left: 3pt solid #c5a059;
        }

        .dim-section { page-break-before: always; }
        .dim-section:first-of-type { page-break-before: avoid; }

        .dim-header {
            color: #861e34;
            font-size: 13pt;
            font-weight: 800;
            margin: 0 0 3mm 0;
            padding-bottom: 1.5mm;
            border-bottom: 1.5pt solid #861e34;
        }

        .tematica-block {
            page-break-inside: avoid;
            margin-bottom: 4mm;
        }
        .tematica-block h3 {
            color: #246257;
            font-size: 10.5pt;
            font-weight: 700;
            margin: 0 0 2mm 0;
        }

        .chart-box {
            width: 100%;
            height: 240px;
            margin-bottom: 2mm;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(55mm, 1fr));
            gap: 1.5mm;
        }
        .kpi-cell {
            border: 0.5pt solid #d1d5db;
            border-radius: 2pt;
            padding: 2mm 2.5mm;
            background: #fafafa;
        }
        .kpi-cell .label {
            font-size: 6.5pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }
        .kpi-cell .value {
            font-size: 11pt;
            font-weight: 800;
            color: #0c312d;
            margin-top: 0.5mm;
        }
        .kpi-cell .unit {
            font-size: 7pt;
            color: #9ca3af;
            font-weight: 400;
        }
        .kpi-cell .anio {
            font-size: 6.5pt;
            color: #9ca3af;
            margin-top: 0.3mm;
        }

        .footer-num {
            position: absolute;
            bottom: 8mm;
            right: 12mm;
            font-size: 7pt;
            color: #9ca3af;
        }

        @media print {
            body { background: #fff; }
            .hoja { box-shadow: none; margin: 0; padding: 15mm 12mm; width: auto; min-height: auto; }
        }
    </style>
</head>
<body>

@php $pageNum = 0; @endphp

@foreach ($datosAgrupados as $dimId => $dimensionData)
    @php $pageNum++; @endphp

    <div class="hoja">
        @if($pageNum === 1)
            <header class="pdf-header">
                <img class="pdf-header__logo" src="data:image/png;base64,{{ $logoInstitucional }}" alt="Gobierno de Puebla y SEI">
                <div class="pdf-header__identity">
                    <h1>{{ $municipio->nombre }}</h1>
                    <p>{{ $municipio->microrregion->macrorregion->nombre ?? 'Estado de Puebla' }} &middot; {{ $municipio->microrregion->nombre ?? '' }}</p>
                </div>
            </header>
            <div class="stats-bar">
                <span>Población: {{ number_format($poblacionTotal) }}</span>
                <span>Marginación: {{ $gradoMarginacion }}</span>
                <span>Presupuesto: ${{ number_format($presupuestoTotal, 2) }}k</span>
                <span>Superficie: {{ number_format($superficieKm2, 2) }} km²</span>
            </div>
        @endif

        <div class="dim-header">{{ $dimensionData['nombre'] }}</div>

        @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
            <div class="tematica-block">
                <h3>{{ $tematica }}</h3>

                @php $mainKpi = $kpis[0] ?? null; @endphp
                @if($mainKpi && !empty($mainKpi['historial']) && $mainKpi['historial'] !== '[]')
                    <div class="chart-box" id="chart-{{ Str::slug($tematica) }}"></div>
                @endif

                <div class="kpi-grid">
                    @foreach ($kpis as $kpi)
                        <div class="kpi-cell">
                            <div class="label">{{ $kpi['nombre'] }}</div>
                            <div class="value">
                                {{ is_numeric($kpi['valor_display'] ?? $kpi['valor']) ? number_format((float)($kpi['valor_display'] ?? $kpi['valor']), 1) : ($kpi['valor_display'] ?? $kpi['valor']) }}
                                @if(!empty($kpi['unidad']))
                                    <span class="unit">
                                        {{ in_array(strtolower($kpi['unidad']), ['porcentaje', '%']) ? '%' : $kpi['unidad'] }}
                                    </span>
                                @endif
                            </div>
                            @if(!empty($kpi['anio']) && $kpi['anio'] !== 'N/D')
                                <div class="anio">Año {{ $kpi['anio'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="footer-num">Pág. {{ $pageNum }}</div>
    </div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    var charts = {};

    @foreach ($datosAgrupados as $dimId => $dimensionData)
        @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
            @php $mainKpi = $kpis[0] ?? null; @endphp
            @if($mainKpi && !empty($mainKpi['historial']) && $mainKpi['historial'] !== '[]')
                (function () {
                    var el = document.getElementById('chart-{{ Str::slug($tematica) }}');
                    if (!el) return;
                    var historyData = {!! $mainKpi['historial'] !!};
                    var xData = historyData.map(function (d) { return d.anio; });
                    var yData = historyData.map(function (d) { return parseFloat(d.valor); });
                    var chart = echarts.init(el, null, { renderer: 'svg' });
                    chart.setOption({
                        animation: false,
                        tooltip: { trigger: 'axis', confine: true },
                        grid: { top: 16, left: 50, right: 14, bottom: 28 },
                        xAxis: { type: 'category', data: xData, axisLabel: { fontSize: 8 } },
                        yAxis: { type: 'value', axisLabel: { fontSize: 8 } },
                        series: [{
                            data: yData,
                            type: 'bar',
                            itemStyle: { color: '{{ $dimensionData['color'] ?? '#c5a059' }}', borderRadius: [4,4,0,0] },
                            label: { show: true, position: 'top', fontSize: 7, formatter: function(p) { return Number(p.value).toFixed(1); } }
                        }]
                    });
                    charts['{{ Str::slug($tematica) }}'] = chart;
                })();
            @endif
        @endforeach
    @endforeach

    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            window.__pdfReady = true;
        });
    });
});
</script>
</body>
</html>
