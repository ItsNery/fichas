<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $municipio->nombre }} — Perfil Municipal</title>
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

        @page {
            size: A4;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.12);
            position: relative;
            overflow: visible;
        }

        .hoja:last-child {
            page-break-after: avoid;
        }

        .pdf-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8mm;
            margin-bottom: 6mm;
            padding-bottom: 4mm;
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
            margin: 0 0 1.2mm 0;
            letter-spacing: -0.3pt;
        }

        .pdf-header p {
            font-size: 8pt;
            color: #6b7280;
            margin: 0;
        }

        .dim-header {
            background: linear-gradient(90deg, #861e34, #b8914a);
            color: #fff;
            font-size: 12pt;
            font-weight: 700;
            padding: 2.5mm 4mm;
            border-radius: 3pt;
            margin-bottom: 2.5mm;
        }

        .perfil-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5mm;
        }

        .config-card-wrapper {
            page-break-inside: avoid;
            overflow: visible;
        }

        .config-card {
            border: 0.5pt solid #d1d5db;
            border-radius: 3pt;
            padding: 2.2mm 3mm;
            background: #fafafa;
            height: 100%;
            overflow: visible;
        }

        .config-card h3 {
            font-size: 9.5pt;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 1mm 0;
        }

        .kpi-block {
            display: flex;
            align-items: baseline;
            gap: 2mm;
            flex-wrap: wrap;
        }

        .kpi-main-value {
            font-size: 22pt;
            font-weight: 800;
            color: #0c312d;
            line-height: 1.1;
        }

        .kpi-main-unit {
            font-size: 11pt;
            color: #6b7280;
            font-weight: 400;
        }

        .kpi-anio {
            font-size: 7pt;
            color: #9ca3af;
        }

        .kpi-variables {
            margin-top: 2mm;
            width: 100%;
        }

        .kpi-variables table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .kpi-variables td {
            padding: 0.8mm 2mm 0.8mm 0;
            border-bottom: 0.5pt solid #e5e7eb;
            vertical-align: top;
        }

        .kpi-variables td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .kpi-variables .var-label {
            color: #4b5563;
        }

        .kpi-variables .var-unit {
            color: #9ca3af;
            font-size: 7pt;
        }

        .benchmark-row {
            display: flex;
            gap: 4mm;
            margin-top: 1.5mm;
            font-size: 7.5pt;
            color: #4b5563;
        }

        .benchmark-item {
            padding: 0.8mm 2mm;
            background: #f3f4f6;
            border-radius: 2pt;
        }

        .benchmark-label {
            font-weight: 600;
        }

        .chart-container {
            width: 100%;
            height: 165px;
            margin: 0;
        }

        .instrument-list {
            font-size: 7.5pt;
            margin: 0;
            padding-left: 4mm;
            color: #374151;
        }

        .instrument-list li {
            margin-bottom: 0.3mm;
        }

        .narrativa {
            font-size: 7pt;
            color: #6b7280;
            font-style: italic;
            margin-top: 0.8mm;
            padding-top: 0.8mm;
            border-top: 0.5pt solid #e5e7eb;
        }

        .footer-num {
            position: absolute;
            bottom: 8mm;
            right: 12mm;
            font-size: 7pt;
            color: #9ca3af;
        }

        @media print {
            body {
                background: #fff;
            }

            .hoja {
                box-shadow: none;
                margin: 0;
                padding: 15mm 12mm;
                width: auto;
                min-height: auto;
            }
        }
    </style>
</head>

<body>

    @php
        $pageNum = 0;
        $palette = ['#861e34', '#c5a059', '#246257', '#d4a04a', '#5f1b2d', '#8b6f47', '#3a7a6b', '#b8924a'];

        function gridWidth($clase)
        {
            if (!$clase)
                return '100%';
            $parts = explode('-', $clase);
            $num = (int) end($parts);
            if ($num < 1 || $num > 12)
                return '100%';
            return round($num / 12 * 100, 2) . '%';
        }
    @endphp

    @foreach ($perfil as $dimensionKey => $items)
        @php
            $pageNum++;
            $dimName = str_replace('_', ' ', $dimensionKey);
            $dimName = ucwords($dimName);
            $dimColor = $palette[($pageNum - 1) % count($palette)];
            $munNombre = $municipio->nombre;
        @endphp

        <div class="hoja">
            @if($pageNum === 1)
                <header class="pdf-header">
                    <img class="pdf-header__logo" src="data:image/png;base64,{{ $logoInstitucional }}" alt="Gobierno de Puebla y SEI">
                    <div class="pdf-header__identity">
                        <h1>{{ $munNombre }}</h1>
                        <p>{{ $municipio->microrregion->macrorregion->nombre ?? 'Estado de Puebla' }} &middot;
                            {{ $municipio->microrregion->nombre ?? '' }}</p>
                    </div>
                </header>
            @endif

            <div class="dim-header">{{ $dimName }}</div>

            <div class="perfil-grid">
                @foreach ($items as $item)
                    @php
                        $config = $item['config'];
                        $datos = $item['datos'];
                        $isKpi = $config->tipo_visualizacion === 'kpi';
                        $isLista = $config->tipo_visualizacion === 'lista';
                        $titulo = $config->titulo_reporte ?? ($config->indicador->nombre_amigable ?? 'Indicador');
                        $anio = is_array($datos) ? ($datos['anio'] ?? '') : '';
                        $w = gridWidth($config->clase_grid ?? '');
                        $isChart = !$isKpi && !$isLista;
                    @endphp

                    <div class="config-card-wrapper"
                        style="{{ $isChart ? 'flex:0 0 100%;max-width:100%;' : 'flex:0 0 calc(' . $w . ' - 1.5mm);max-width:calc(' . $w . ' - 1.5mm);' }}">
                        <div class="config-card">
                            <h3>{{ $titulo }} @if($anio)<span style="font-weight:400;color:#9ca3af;"> ({{ $anio }})</span>@endif
                            </h3>

                            @if($isKpi)
                                @php
                                    $valor = is_array($datos) ? ($datos['valor_actual'] ?? $datos['total'] ?? 0) : $datos;
                                    $unidad = is_array($datos) && isset($datos['variables'][0]['unidad']) ? $datos['variables'][0]['unidad'] : '';
                                    $suffix = in_array(strtolower($unidad), ['porcentaje', '%', 'pct']) ? '%' : (in_array(strtolower($unidad), ['pesos', '$']) ? '$' : '');
                                    $ranking = $datos['ranking'] ?? null;
                                @endphp

                                <div class="kpi-block">
                                    <span
                                        class="kpi-main-value">{{ $suffix === '$' ? '$' : '' }}{{ is_numeric($valor) ? number_format((float) $valor, 1) : $valor }}{{ $suffix === '%' ? '%' : '' }}</span>
                                    @if($suffix === '' && $unidad)
                                        <span class="kpi-main-unit">{{ $unidad }}</span>
                                    @endif
                                    @if($ranking)
                                        <span style="font-size:7pt;color:#6b7280;margin-left:auto;">
                                            #{{ $ranking['posicion'] }}/{{ $ranking['total_municipios'] }}
                                        </span>
                                    @endif
                                </div>

                                @if(($config->mostrar_comparativa ?? false) && is_array($datos))
                                    <div class="benchmark-row">
                                        @if(isset($datos['promedio_macrorregional']) && $datos['promedio_macrorregional'] !== null)
                                            <div class="benchmark-item"><span class="benchmark-label">Macrorregión:</span>
                                                {{ number_format((float) $datos['promedio_macrorregional'], 1) }}</div>
                                        @endif
                                        @if(isset($datos['promedio_estatal']) && $datos['promedio_estatal'] !== null)
                                            <div class="benchmark-item"><span class="benchmark-label">Estado:</span>
                                                {{ number_format((float) $datos['promedio_estatal'], 1) }}</div>
                                        @endif
                                    </div>
                                @endif

                                @if(is_array($datos) && isset($datos['variables']) && count($datos['variables']) > 1)
                                    <div class="kpi-variables">
                                        <table>
                                            @foreach($datos['variables'] as $var)
                                                <tr>
                                                    <td class="var-label">{{ $var['nombre'] ?? '' }}</td>
                                                    <td>
                                                        {{ is_numeric($var['valor'] ?? 0) ? number_format((float) $var['valor'], 1) : ($var['valor'] ?? 'N/D') }}
                                                        <span class="var-unit">{{ $var['unidad'] ?? '' }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                @endif

                            @elseif($isLista && is_array($datos) && isset($datos['items']))
                                <ul class="instrument-list">
                                    @foreach($datos['items'] as $itemName)
                                        <li>{{ $itemName }}</li>
                                    @endforeach
                                </ul>

                            @else
                                <div class="chart-container" id="chart-{{ $config->id }}"></div>
                            @endif

                            @if(!empty($item['narrativa']))
                                <p class="narrativa">{{ strip_tags($item['narrativa']) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="footer-num">Pág. {{ $pageNum }}</div>
        </div>
    @endforeach

    <script>
        var perfilData = @json($perfil);
        var currentMunicipality = @json($municipio->nombre);

        (function () {
            var chartQueue = [];
            Object.values(perfilData).forEach(function (items) {
                items.forEach(function (item) {
                    chartQueue.push(item);
                });
            });

            var idx = 0;

            function norm(t) {
                var m = { barras: 'bar', lineas: 'line', 'líneas': 'line', donut: 'doughnut', areaspline: 'line' };
                return m[t] || t;
            }

            function flatData(arr) {
                return (arr || []).map(function (d) { return typeof d === 'object' && d !== null ? d.value : d; });
            }

            function normalizeName(value) {
                return String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim()
                    .toUpperCase();
            }

            function wrapAxisLabel(value, maxLength) {
                var words = String(value || '').split(/\s+/);
                var lines = [];
                var line = '';

                words.forEach(function (word) {
                    var candidate = line ? line + ' ' + word : word;
                    if (candidate.length > maxLength && line) {
                        lines.push(line);
                        line = word;
                    } else {
                        line = candidate;
                    }
                });

                if (line) lines.push(line);
                return lines.join('\n');
            }

            function horizontalAxisLayout(labels, containerWidth, defaultLeft) {
                var longest = (labels || []).reduce(function (max, value) {
                    return Math.max(max, String(value || '').length);
                }, 0);
                var maxLeft = Math.max(defaultLeft, Math.floor(containerWidth * 0.42));
                var left = Math.min(Math.max(defaultLeft, longest * 4.2 + 16), maxLeft);

                return {
                    left: Math.round(left),
                    labelWidth: Math.max(45, Math.round(left - 14))
                };
            }

            function renderNextChart() {
                if (idx >= chartQueue.length) {
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            window.__pdfReady = true;
                        });
                    });
                    return;
                }
                var item = chartQueue[idx++];
                var config = item.config;
                if (config.tipo_visualizacion === 'kpi' || config.tipo_visualizacion === 'lista') {
                    setTimeout(renderNextChart, 10);
                    return;
                }

                var el = document.getElementById('chart-' + config.id);
                if (!el) { setTimeout(renderNextChart, 10); return; }

                var datos = item.datos;
                if (!datos) { setTimeout(renderNextChart, 10); return; }

                var ech = datos.echarts || datos;
                var series = ech.series || [];
                if (!series.length && !ech.data) { setTimeout(renderNextChart, 10); return; }

                var chart = echarts.init(el, null, { renderer: 'svg' });
                chart.setOption({ animation: false });
                var actualType = norm(ech.type || config.tipo_visualizacion);
                var colors = ['#861e34', '#246257', '#c5a059', '#6b7280'];

                if (actualType === 'piramide') {
                    el.style.height = '220px';
                } else if (actualType === 'bar-horizontal') {
                    var compactBarCategories = (ech.eje_y && ech.eje_y.categorias) || [];
                    el.style.height = Math.min(200, Math.max(125, compactBarCategories.length * 26 + 42)) + 'px';
                } else if (actualType === 'map' || actualType === 'mapa' || actualType === 'treemap' || actualType === 'doughnut' || actualType === 'pie') {
                    el.style.height = '180px';
                } else if (actualType === 'scatter') {
                    el.style.height = '165px';
                } else {
                    el.style.height = '150px';
                }
                chart.resize();

                if (actualType === 'piramide') {
                    var cats = (datos.eje_x && datos.eje_x.categorias) || datos.categorias || [];
                    var rcats = cats.slice().reverse();
                    var h = (datos.series && datos.series[0] ? datos.series[0].data : datos.hombres || []).slice().reverse();
                    var m = (datos.series && datos.series[1] ? datos.series[1].data : datos.mujeres || []).slice().reverse();
                    chart.setOption({
                        tooltip: { trigger: 'axis', confine: true, axisPointer: { type: 'shadow' } },
                        grid: { top: 12, left: 60, right: 60, bottom: 28 },
                        xAxis: [{ type: 'value', axisLabel: { fontSize: 8 }, splitLine: { show: false } }, { type: 'value', axisLabel: { fontSize: 8 }, splitLine: { show: false } }],
                        yAxis: { type: 'category', data: rcats, axisLabel: { fontSize: 7 } },
                        series: [
                            { name: 'Hombres', type: 'bar', data: h.map(function (v) { return -Math.abs(v); }), itemStyle: { color: '#246257' }, label: { show: true, position: 'left', fontSize: 7, formatter: function (p) { return Math.abs(p.value); } } },
                            { name: 'Mujeres', type: 'bar', data: m.map(function (v) { return Math.abs(v); }), itemStyle: { color: '#c5a059' }, label: { show: true, position: 'right', fontSize: 7, formatter: function (p) { return p.value; } } }
                        ]
                    });
                } else if (actualType === 'scatter') {
                    var opt = {
                        tooltip: { trigger: 'item', confine: true, formatter: function (p) { return (p.data[2] || '') + ': (' + Number(p.data[0]).toFixed(1) + ', ' + Number(p.data[1]).toFixed(1) + ')'; } },
                        grid: { top: 30, left: 70, right: 70, bottom: 32 },
                        xAxis: { type: 'value', axisLabel: { fontSize: 8 } },
                        yAxis: { type: 'value', axisLabel: { fontSize: 8 } },
                        series: []
                    };
                    series.forEach(function (s, i) {
                        var last = (i === series.length - 1);
                        opt.series.push({
                            type: 'scatter', name: s.name || '', data: s.data || [],
                            symbolSize: last ? 22 : 8,
                            itemStyle: last ? { color: '#861e34', borderColor: '#fff', borderWidth: 2, shadowBlur: 6, shadowColor: 'rgba(134,30,52,0.5)' } : { color: '#6b7280', opacity: 0.55 },
                            label: last ? {
                                show: true,
                                position: 'top',
                                distance: 5,
                                fontSize: 8,
                                fontWeight: 700,
                                color: '#861e34',
                                backgroundColor: 'rgba(255,255,255,0.88)',
                                padding: [2, 3],
                                borderRadius: 2,
                                formatter: function (p) {
                                    var value = p.data && p.data.value ? p.data.value : p.data;
                                    return '(' + Number(value[0]).toFixed(1) + ', ' + Number(value[1]).toFixed(1) + ')';
                                }
                            } : { show: false }
                        });
                    });
                    chart.setOption(opt);
                } else if (actualType === 'doughnut' || actualType === 'pie') {
                    var pieData = series[0] ? series[0].data : [];
                    chart.setOption({
                        tooltip: { trigger: 'item', confine: true, formatter: '{b}: {c} ({d}%)' },
                        series: [{ type: 'pie', radius: ['38%', '70%'], data: pieData, label: { fontSize: 8, fontWeight: 600 }, itemStyle: { borderRadius: 4 } }]
                    });
                } else if (actualType === 'bar-horizontal') {
                    var hCats = (ech.eje_y && ech.eje_y.categorias) || [];
                    if (!hCats.length) hCats = series[0] ? series[0].data.map(function (_, i) { return i + 1; }) : [];
                    var hAxisLayout = horizontalAxisLayout(hCats, el.clientWidth, 70);
                    var opt = {
                        tooltip: { trigger: 'axis', confine: true },
                        grid: { top: 14, left: hAxisLayout.left, right: 18, bottom: 28 },
                        xAxis: { type: 'value', axisLabel: { fontSize: 8 } },
                        yAxis: {
                            type: 'category',
                            data: hCats,
                            axisLabel: {
                                fontSize: 7,
                                width: hAxisLayout.labelWidth,
                                overflow: 'truncate',
                                ellipsis: '...'
                            },
                            inverse: true
                        },
                        series: series.map(function (s, i) {
                            return {
                                type: 'bar', name: s.name || '', data: s.data || [],
                                itemStyle: { color: colors[i % colors.length], opacity: i > 0 ? 0.7 : 1 },
                                label: { show: true, position: 'right', fontSize: 7, formatter: function (p) { var v = typeof p.value === 'object' ? p.value.value : p.value; return Number(v).toFixed(1); } }
                            };
                        })
                    };
                    chart.setOption(opt);
                } else if (actualType === 'map' || actualType === 'mapa' || actualType === 'treemap') {
                    var mapData = ech.data || (series[0] ? series[0].data : []);
                    var isMapFallback = actualType === 'map' || actualType === 'mapa';
                    var currentKey = normalizeName(currentMunicipality);
                    var items = mapData.map(function (d) {
                        return {
                            name: d.name || '',
                            value: Number(d.value) || 0,
                            isCurrent: normalizeName(d.name) === currentKey
                        };
                    });
                    items.sort(function (a, b) { return b.value - a.value; });
                    items.forEach(function (d, i) { d.rank = i + 1; });

                    var selected;
                    if (isMapFallback) {
                        var currentIndex = items.findIndex(function (d) { return d.isCurrent; });
                        if (currentIndex >= 0) {
                            var start = Math.max(0, currentIndex - 2);
                            var end = Math.min(items.length, start + 5);
                            start = Math.max(0, end - 5);
                            selected = items.slice(start, end);
                        } else {
                            selected = items.slice(0, 5);
                        }
                    } else {
                        selected = items.slice(0, 18);
                    }

                    var cats = selected.map(function (d) {
                        var name = d.name || '';
                        return d.isCurrent ? name + ' (' + d.rank + '°)' : name;
                    }).reverse();
                    var vals = selected.map(function (d, i) {
                        var treemapColor = colors[i % colors.length];
                        return {
                            value: d.value,
                            isCurrent: d.isCurrent,
                            itemStyle: isMapFallback
                                ? (d.isCurrent
                                    ? { color: '#861e34', borderColor: '#c5a059', borderWidth: 2 }
                                    : { color: '#d1d5db' })
                                : { color: treemapColor },
                            label: isMapFallback && d.isCurrent
                                ? { color: '#861e34', fontWeight: 700 }
                                : { color: '#374151' }
                        };
                    }).reverse();
                    var mapAxisLayout = horizontalAxisLayout(cats, el.clientWidth, isMapFallback ? 105 : 90);
                    chart.setOption({
                        tooltip: { trigger: 'axis', confine: true },
                        grid: { top: 12, left: mapAxisLayout.left, right: 22, bottom: 28 },
                        xAxis: { type: 'value', axisLabel: { fontSize: 8 } },
                        yAxis: {
                            type: 'category',
                            data: cats,
                            axisLabel: {
                                fontSize: isMapFallback ? 8 : 7,
                                lineHeight: isMapFallback ? 10 : 9,
                                width: mapAxisLayout.labelWidth,
                                overflow: 'truncate',
                                ellipsis: '...',
                                formatter: function (value) {
                                    var wrapped = wrapAxisLabel(value, isMapFallback ? 22 : 20);
                                    return isMapFallback && normalizeName(value).indexOf(currentKey) === 0
                                        ? '{current|' + wrapped + '}'
                                        : wrapped;
                                },
                                rich: { current: { color: '#861e34', fontWeight: 700, lineHeight: 10 } }
                            }
                        },
                        series: [{
                            type: 'bar',
                            data: vals,
                            label: {
                                show: true,
                                position: 'right',
                                fontSize: 7,
                                formatter: function (p) {
                                    var suffix = isMapFallback && p.data && p.data.isCurrent ? '  ← Municipio actual' : '';
                                    return Number(p.value).toFixed(1) + suffix;
                                }
                            }
                        }]
                    });
                } else {
                    var xCats = (ech.eje_x && ech.eje_x.categorias) || [];
                    var isLine = (actualType === 'line' || actualType === 'areaspline');
                    var sType = isLine ? 'line' : (['bar', 'line', 'area', 'areaspline'].indexOf(actualType) >= 0 ? actualType : 'bar');
                    var xLabelWidth = xCats.length ? Math.max(28, Math.min(75, Math.floor((el.clientWidth - 75) / xCats.length) - 4)) : 75;
                    var opt = {
                        tooltip: { trigger: 'axis', confine: true },
                        grid: { top: 14, left: 55, right: 14, bottom: 36 },
                        xAxis: {
                            type: 'category',
                            data: xCats,
                            axisLabel: {
                                fontSize: 8,
                                rotate: xCats.length > 6 ? 30 : 0,
                                hideOverlap: true,
                                width: xLabelWidth,
                                overflow: 'truncate',
                                ellipsis: '...'
                            }
                        },
                        yAxis: { type: 'value', axisLabel: { fontSize: 8 } },
                        series: series.map(function (s, i) {
                            return {
                                name: s.name || '', type: i > 0 ? 'bar' : sType, data: flatData(s.data),
                                itemStyle: { color: colors[i % colors.length], opacity: i > 0 ? 0.7 : 1 },
                                smooth: isLine, symbol: isLine ? 'circle' : undefined, symbolSize: isLine ? 6 : undefined,
                                areaStyle: (actualType === 'areaspline' || actualType === 'area') ? { color: 'rgba(134,30,52,0.12)' } : undefined,
                                barGap: i > 0 ? '10%' : undefined,
                                label: isLine ? { show: true, position: 'top', fontSize: 7, formatter: function (p) { return Number(p.value).toFixed(1); } } : undefined
                            };
                        })
                    };
                    chart.setOption(opt);
                }

                setTimeout(renderNextChart, 50);
            }

            setTimeout(renderNextChart, 50);
        })();
    </script>
</body>

</html>
