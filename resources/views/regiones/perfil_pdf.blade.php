<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil {{ $tipoRegion }} - {{ $region->nombre }}</title>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    @php
        $gilroy = base64_encode(file_get_contents(public_path('css/fuentes/Gilroy/Gilroy-Bold.ttf')));
        $corra = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Regular.ttf')));
        $corraBold = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Bold.ttf')));
        $regionalizacion = $tipoRegion === 'Microrregión'
            ? (($region->macrorregion?->nombre ?? 'Estado de Puebla') . ' · Estado de Puebla')
            : 'Estado de Puebla';

        $sectionPages = collect($perfil)
            ->reject(fn ($items, $section) => $section === 'general')
            ->flatMap(function ($items, $section) {
                $pages = collect();
                $pageItems = collect();
                $grid = array_fill(0, 3, array_fill(0, 3, false));

                foreach ($items as $item) {
                    $chartType = mb_strtolower((string) ($item['config']->tipo_visualizacion ?? 'bar-horizontal'));
                    $categories = $item['datos']['echarts']['eje_y']['categorias']
                        ?? $item['datos']['echarts']['eje_x']['categorias']
                        ?? [];
                    $isPyramid = str_contains($chartType, 'piramide');
                    $isKpi = $chartType === 'kpi';
                    // KPI data may include a municipal ranking for the web profile.
                    // It must not determine the compact PDF card size.
                    $isDense = !$isKpi && (count($categories) >= 6 || $chartType === 'mapa');
                    $span = $isPyramid || $isDense ? 3 : ($isKpi ? 1 : 2);
                    $rowSpan = $isPyramid || $isDense ? 2 : 1;
                    $position = null;

                    for ($row = 0; $row <= 3 - $rowSpan && $position === null; $row++) {
                        for ($column = 0; $column <= 3 - $span; $column++) {
                            $fits = true;
                            for ($gridRow = $row; $gridRow < $row + $rowSpan && $fits; $gridRow++) {
                                for ($gridColumn = $column; $gridColumn < $column + $span; $gridColumn++) {
                                    if ($grid[$gridRow][$gridColumn]) {
                                        $fits = false;
                                        break;
                                    }
                                }
                            }
                            if ($fits) $position = [$row, $column];
                        }
                    }

                    if ($position === null && $pageItems->isNotEmpty()) {
                        $pages->push($pageItems);
                        $pageItems = collect();
                        $grid = array_fill(0, 3, array_fill(0, 3, false));
                        $position = [0, 0];
                    }

                    [$row, $column] = $position;
                    for ($gridRow = $row; $gridRow < $row + $rowSpan; $gridRow++) {
                        for ($gridColumn = $column; $gridColumn < $column + $span; $gridColumn++) {
                            $grid[$gridRow][$gridColumn] = true;
                        }
                    }

                    $pageItems->push($item + [
                        'span' => $span,
                        'rowSpan' => $rowSpan,
                        'isPyramid' => $isPyramid,
                        'isKpi' => $isKpi,
                    ]);
                }

                if ($pageItems->isNotEmpty()) $pages->push($pageItems);

                return $pages->values()->map(fn ($items, $index) => [
                    'section' => $section,
                    'continuation' => $index > 0,
                    'items' => $items,
                ]);
            })
            ->values();
    @endphp
    <style>
        @font-face { font-family:Gilroy; src:url(data:font/ttf;base64,{{ $gilroy }}) format('truetype'); font-weight:700; }
        @font-face { font-family:Corra; src:url(data:font/ttf;base64,{{ $corra }}) format('truetype'); font-weight:400; }
        @font-face { font-family:Corra; src:url(data:font/ttf;base64,{{ $corraBold }}) format('truetype'); font-weight:700; }
        :root {
            --burgundy-700:#5f1b2d;
            --burgundy-600:#861e34;
            --gold:#c79b66;
            --green-900:#0c312d;
            --green-700:#246257;
            --green-500:#609b84;
            --ink:#262626;
            --muted:#eaeaea;
            --paper:#fff;
            --dimension-accent:var(--burgundy-600);
        }
        @page { size:432mm 279mm; margin:0; }
        * { box-sizing:border-box; }
        body { margin:0; background:#eaeaea; color:var(--ink); font-family:Corra,Arial,sans-serif; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .sheet { width:432mm; height:279mm; margin:0 auto 5mm; padding:11mm 15mm 8mm; position:relative; overflow:hidden; background:var(--paper); break-after:page; page-break-after:always; }
        .sheet:last-of-type { break-after:auto; page-break-after:auto; }
        .sheet::before { content:""; position:absolute; top:11mm; left:0; width:3mm; height:48mm; background:var(--dimension-accent); }
        .identity { height:48mm; display:grid; grid-template-columns:58mm minmax(0,1fr) 126mm; align-items:center; gap:9mm; margin:0 0 5mm; padding:1mm 0 1.5mm; border-top:.6mm solid var(--gold); border-bottom:1.2mm solid var(--burgundy-700); }
        .map { width:58mm; height:44mm; position:relative; }
        .map img { display:block; width:100%; height:40mm; object-fit:contain; }
        .map-label { position:absolute; bottom:0; width:100%; text-align:center; font-size:8pt; color:var(--ink); }
        .identity-copy { min-width:0; text-align:center; }
        .identity-copy h1 { margin:0 auto; }
        .eyebrow { margin:0 0 1mm; color:var(--green-700); font-size:9pt; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .region { margin:0; color:var(--burgundy-700); font:700 27pt/.95 Gilroy,Arial,sans-serif; }
        .identity-detail { margin:2mm 0 0; color:var(--ink); font-size:10pt; }
        .identity-detail strong { color:var(--green-700); }
        .stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); width:100%; margin-top:3mm; }
        .stat { min-width:0; padding:0 3mm; border-left:1mm solid var(--gold); font-size:8.5pt; line-height:1.05; }
        .stat span { display:block; margin-bottom:.7mm; color:var(--green-700); font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        .stat b { display:block; color:var(--burgundy-700); font:700 13.5pt/1 Gilroy,Arial,sans-serif; }
        .identity > img { display:block; width:126mm; max-height:18mm; object-fit:contain; object-position:right center; }
        .section-head { min-height:8mm; display:flex; align-items:center; gap:4mm; margin:0 0 4mm; padding:1mm 3mm; background:var(--dimension-accent); border-left:2mm solid var(--gold); }
        .section-head h2 { margin:0; color:#fff; font:700 17pt/1 Gilroy,Arial,sans-serif; text-transform:capitalize; }
        .section-head span { margin-left:auto; color:#fff; font-size:9pt; font-weight:700; letter-spacing:.02em; }
        .cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); grid-template-rows:repeat(3,61mm); grid-auto-flow:dense; gap:4mm 6mm; }
        .card { display:flex; flex-direction:column; min-width:0; padding:3mm 3mm 2mm; overflow:hidden; border:.3mm solid var(--muted); border-top:1mm solid var(--dimension-accent); background:#fff; break-inside:avoid; }
        .card--span-2 { grid-column:span 2; }
        .card--span-3 { grid-column:1 / -1; }
        .card--rows-2 { grid-row:span 2; }
        .card--pyramid { padding-left:5mm; padding-right:5mm; background:linear-gradient(180deg,#fff 0,#f8f8f8 100%); }
        .card-head { display:flex; align-items:flex-start; justify-content:space-between; min-height:14mm; gap:3mm; }
        .card h3 { max-width:150mm; margin:0; color:var(--ink); font:700 13pt/1.12 Gilroy,Arial,sans-serif; }
        .year { flex:none; min-width:15mm; padding:1mm 2.5mm; color:#fff; background:var(--burgundy-700); font:700 10pt Corra,Arial,sans-serif; text-align:center; }
        .subtitle { margin:0 0 1mm; color:var(--green-700); font-size:8pt; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .narrative { min-height:7mm; margin:0 0 1.5mm; color:#4b5563; font-size:8.5pt; line-height:1.2; }
        .chart-note { flex:none; margin:0 0 1mm; color:#666; font-size:7.5pt; font-style:italic; }
        .visual { flex:1; min-height:0; width:100%; }
        .visual--kpi { flex:none; display:flex; align-items:center; min-height:24mm; gap:4mm; padding:3mm 4mm; background:#f7f7f7; border-left:1.2mm solid var(--dimension-accent); }
        .kpi-number { color:var(--burgundy-700); font:700 28pt/1 Gilroy,Arial,sans-serif; letter-spacing:-.03em; }
        .kpi-copy { max-width:105mm; color:var(--ink); font-size:10pt; line-height:1.15; }
        .kpi-unit { display:block; margin-top:1mm; color:var(--green-700); font-size:9pt; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .category { display:flex; align-items:center; justify-content:center; min-height:30mm; padding:4mm 9mm; color:#fff; background:var(--green-900); border-bottom:1mm solid var(--gold); font:700 18pt/1.1 Gilroy,Arial,sans-serif; text-align:center; }
        .source { flex:none; margin:1mm 0 0; color:#666; font-size:7.5pt; line-height:1.1; }
        .empty { display:flex; align-items:center; justify-content:center; min-height:30mm; padding:4mm; background:#f7f7f7; color:#555; font-size:10pt; font-weight:700; text-align:center; }
        .footer { position:absolute; right:15mm; bottom:5mm; left:15mm; display:flex; align-items:center; justify-content:space-between; padding-top:1.5mm; border-top:.5mm solid var(--green-700); color:#555; font-size:8pt; }
        .footer-agency { font-weight:700; }
        .footer-page { padding-left:3mm; border-left:1mm solid var(--gold); text-align:right; }
        .footer-page b { color:var(--burgundy-700); }
        @media print { body { background:#fff; } .sheet { margin:0; } }
    </style>
</head>
<body>
@forelse($sectionPages as $page)
    @php
        $sectionName = str_replace('_', ' ', $page['section']);
        $accent = $dimensionColors[$page['section']]
            ?? data_get($page['items'], '0.config.indicador.tematica.dimension.color')
            ?? '#861e34';
    @endphp
    <section class="sheet" style="--dimension-accent: {{ $accent }};">
        <header class="identity">
            <div class="map">
                <img src="{{ $mapImageDataUri }}" alt="Ubicación de {{ $region->nombre }} en Puebla">
                <span class="map-label">Ubicación en Puebla</span>
            </div>
            <div class="identity-copy">
                <p class="eyebrow">Ficha de perfil {{ strtolower($tipoRegion) }}</p>
                <h1 class="region">{{ $region->nombre }}</h1>
                <p class="identity-detail"><strong>Regionalización:</strong> {{ $regionalizacion }}</p>
                <div class="stats">
                    <div class="stat"><span>Población</span><b>{{ number_format($poblacionTotal) }}</b></div>
                    <div class="stat"><span>Marginación</span><b>{{ $gradoMarginacion }}</b></div>
                    <div class="stat"><span>Superficie</span><b>{{ number_format($superficieTotal, 2) }} km²</b></div>
                    <div class="stat"><span>Municipios</span><b>{{ $municipios->count() }}</b></div>
                </div>
            </div>
            <img src="data:image/png;base64,{{ $cintillo }}" alt="Cintillo institucional SEI">
        </header>
        <div class="section-head">
            <h2>{{ $sectionName }}</h2>
            <span>{{ $page['continuation'] ? 'Continuación · ' : '' }}Perfil territorial</span>
        </div>
        <div class="cards">
            @foreach($page['items'] as $item)
                @php
                    $config = $item['config'];
                    $data = $item['datos'] ?? [];
                    $chartType = mb_strtolower((string) ($config->tipo_visualizacion ?? 'bar-horizontal'));
                    $title = $config->titulo_reporte ?? $config->indicador?->nombre_amigable ?? 'Indicador';
                    $isCategory = ($data['echarts']['type'] ?? null) === 'categorical';
                    $isKpi = $item['isKpi'];
                    $categoryCount = count($data['echarts']['eje_y']['categorias'] ?? []);
                @endphp
                <article class="card card--span-{{ $item['span'] }} card--rows-{{ $item['rowSpan'] }}{{ $item['isPyramid'] ? ' card--pyramid' : '' }}{{ $isKpi ? ' card--kpi' : '' }}">
                    <p class="subtitle">{{ $config->indicador?->tematica?->nombre ?? 'Indicador' }}</p>
                    <div class="card-head"><h3>{{ $title }}</h3><span class="year">{{ $data['anio'] ?? 'S/D' }}</span></div>
                    @if(!$isKpi && !empty($item['narrativa']))
                        <p class="narrative">{{ strip_tags($item['narrativa']) }}</p>
                    @endif
                    @if($isKpi)
                        @php
                            $value = $data['valor_actual'] ?? $data['total'] ?? 'S/D';
                            $unit = $data['unidad'] ?? data_get($data, 'variables.0.unidad', '');
                        @endphp
                        <div class="visual visual--kpi">
                            <div class="kpi-number">{{ $value }}</div>
                            @if($unit)<div class="kpi-copy"><span class="kpi-unit">{{ $unit }}</span></div>@endif
                        </div>
                    @elseif($isCategory)
                        <div class="visual category">{{ $data['valor_actual'] ?? 'Sin dato disponible' }}</div>
                    @elseif(!empty($data['echarts']))
                        @if($data['ranking_limited'] ?? false)
                            <p class="chart-note">Vista resumida: se muestran los 5 municipios con mayor valor y los 5 con menor valor.</p>
                        @elseif($categoryCount > 10)
                            <p class="chart-note">Se muestran los 10 municipios con mayor valor total.</p>
                        @endif
                        <div class="visual regional-chart" id="chart-{{ $config->id }}"></div>
                    @else
                        <div class="visual empty">Sin datos disponibles para este perfil</div>
                    @endif
                    @if(!empty($data['fuente']))<p class="source">Fuente: {{ $data['fuente'] }}</p>@endif
                </article>
            @endforeach
        </div>
        <footer class="footer">
            <span class="footer-agency">Secretaría de Planeación, Finanzas y Administración · Sistema Estatal de Información</span>
            <span class="footer-page">{{ $region->nombre }} · <b>Página {{ $loop->iteration }} de {{ $sectionPages->count() }}</b></span>
        </footer>
    </section>
@empty
    <section class="sheet">
        <header class="identity"><div class="identity-copy"><p class="eyebrow">Ficha de perfil {{ strtolower($tipoRegion) }}</p><h1 class="region">{{ $region->nombre }}</h1></div><img src="data:image/png;base64,{{ $cintillo }}" alt="Cintillo institucional SEI"></header>
        <div class="section-head"><h2>Perfil territorial</h2></div>
        <div class="empty">No hay indicadores configurados para este perfil.</div>
    </section>
@endforelse

<script>
    const profile = @json($perfil);
    const chartPalette = ['#861e34', '#c79b66', '#246257', '#af1731', '#609b84', '#5f1b2d', '#484747', '#0c312d'];
    window.__pdfReady = false;

    const numberLabel = value => new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 }).format(Number(value) || 0);
    const normalizeType = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const contrast = color => ['#c79b66', '#609b84'].includes(color.toLowerCase()) ? '#262626' : '#fff';

    function treemapData(nodes, depth = 0) {
        return (nodes || []).map((node, index) => {
            const color = node.itemStyle?.color || chartPalette[(index + depth) % chartPalette.length];
            return {
                ...node,
                itemStyle: { ...(node.itemStyle || {}), color, borderColor: '#fff', borderWidth: 2 },
                label: { ...(node.label || {}), color: node.label?.color || contrast(color) },
                children: node.children ? treemapData(node.children, depth + 1) : undefined,
            };
        });
    }

    function renderChart(item) {
        const element = document.getElementById(`chart-${item.config.id}`);
        const data = item.datos || {};
        const chartData = data.echarts || {};
        if (!element || !window.echarts) return;

        const type = normalizeType(chartData.type || item.config.tipo_visualizacion);
        const series = chartData.series || [];
        const chart = echarts.init(element, null, { renderer: 'svg' });
        const base = { animation: false, color: chartPalette, textStyle: { fontFamily: 'Corra, Arial' }, tooltip: { confine: true } };

        if (type.includes('piramide')) {
            const rows = (chartData.eje_x?.categorias || data.eje_x?.categorias || []).map((category, index) => {
                const age = String(category).match(/(\d+)/);
                return {
                    category,
                    hombres: Math.abs(Number(series[0]?.data?.[index]) || 0),
                    mujeres: Math.abs(Number(series[1]?.data?.[index]) || 0),
                    order: age ? Number(age[1]) : Number.MAX_SAFE_INTEGER,
                };
            }).sort((a, b) => a.order - b.order);
            const max = Math.max(1, ...rows.flatMap(row => [row.hombres, row.mujeres]));
            const rawStep = max / 4;
            const magnitude = 10 ** Math.floor(Math.log10(rawStep));
            const normalizedStep = rawStep / magnitude;
            const interval = (normalizedStep <= 1 ? 1 : (normalizedStep <= 2 ? 2 : (normalizedStep <= 5 ? 5 : 10))) * magnitude;
            const axisMax = Math.ceil(max / interval) * interval;
            chart.setOption({ ...base,
                legend: { top: 0, left: 'center', itemWidth: 14, itemHeight: 8, textStyle: { fontSize: 11, color: '#262626' } },
                grid: { top: 28, bottom: 22, left: 72, right: 72, containLabel: true },
                xAxis: { type: 'value', min: -axisMax, max: axisMax, interval, name: 'Habitantes', nameLocation: 'middle', nameGap: 25,
                    nameTextStyle: { fontSize: 10, color: '#262626' }, axisLabel: { fontSize: 9, color: '#262626', formatter: value => numberLabel(Math.abs(value)) },
                    axisLine: { show: true, lineStyle: { color: '#afafaf' } }, splitLine: { lineStyle: { color: '#eaeaea' } } },
                yAxis: { type: 'category', data: rows.map(row => row.category), axisTick: { show: false }, axisLine: { lineStyle: { color: '#afafaf' } },
                    axisLabel: { show: true, interval: 0, fontSize: 10, lineHeight: 12, color: '#262626', margin: 10 } },
                series: [
                    { name: series[0]?.name || 'Hombres', type: 'bar', stack: 'total', barMaxWidth: 14, barCategoryGap: '20%', data: rows.map(row => -row.hombres), itemStyle: { color: '#256259' },
                        label: { show: true, position: 'left', distance: 4, fontSize: 8, color: '#262626', formatter: params => numberLabel(Math.abs(params.value ?? 0)) } },
                    { name: series[1]?.name || 'Mujeres', type: 'bar', stack: 'total', barMaxWidth: 14, barCategoryGap: '20%', data: rows.map(row => row.mujeres), itemStyle: { color: '#872341' },
                        label: { show: true, position: 'right', distance: 4, fontSize: 8, color: '#262626', formatter: params => numberLabel(Math.abs(params.value ?? 0)) } },
                ],
            });
        } else if (type === 'treemap') {
            // Treemaps hide small municipalities in a static PDF. Use a ranked
            // bar chart, keeping the eight largest contributors plus "Otros".
            const ranked = [...(series[0]?.data || [])]
                .map(item => ({ name: item.name || 'Sin nombre', value: Number(item.value) || 0 }))
                .sort((a, b) => b.value - a.value);
            const top = ranked.slice(0, 8);
            const others = ranked.slice(8).reduce((sum, item) => sum + item.value, 0);
            if (others > 0) top.push({ name: 'Otros municipios', value: others });
            chart.setOption({ ...base,
                grid: { top: 10, left: 150, right: 38, bottom: 16 },
                xAxis: { type: 'value', axisLabel: { fontSize: 8 }, splitLine: { lineStyle: { color: '#eaeaea' } } },
                yAxis: { type: 'category', inverse: true, data: top.map(item => item.name), axisTick: { show: false }, axisLabel: { fontSize: 8, width: 135, overflow: 'truncate' } },
                series: [{ type: 'bar', data: top.map(item => item.value), itemStyle: { color: chartPalette[0] }, label: { show: true, position: 'right', fontSize: 8, formatter: params => numberLabel(params.value) } }]
            });
        } else if (type === 'pie' || type === 'pastel') {
            chart.setOption({ ...base, legend: { bottom: 0, textStyle: { fontSize: 8 } }, series: [{ type: 'pie', radius: ['35%', '68%'], data: series[0]?.data || [], label: { fontSize: 8 } }] });
        } else if (type === 'line' || type === 'lineas') {
            const hasMultipleSeries = series.length > 1;
            chart.setOption({ ...base,
                legend: hasMultipleSeries ? { top: 0, left: 'center', textStyle: { fontSize: 8 } } : undefined,
                grid: { top: hasMultipleSeries ? 28 : 18, left: 45, right: 18, bottom: 32 },
                xAxis: { type: 'category', data: chartData.eje_x?.categorias || [], axisLabel: { fontSize: 8 } },
                yAxis: { type: 'value', axisLabel: { fontSize: 8 }, splitLine: { lineStyle: { color: '#eaeaea' } } },
                series: series.map((serie, index) => ({ ...serie, type: 'line', smooth: true, symbol: 'circle', symbolSize: 6,
                    itemStyle: { ...(serie.itemStyle || {}), color: chartPalette[index % chartPalette.length] },
                    label: { show: true, position: 'top', fontSize: 7, formatter: params => numberLabel(params.value ?? 0) } })),
            });
        } else {
            const categories = chartData.eje_y?.categorias || [];
            const fallbackData = chartData.data || series[0]?.data || [];
            const horizontal = type === 'bar-horizontal' || type === 'map' || type === 'mapa' || categories.length > 0;
            const normalizedSeries = series.length ? series : [{ name: '', data: fallbackData }];
            const sourceCategories = categories.length ? categories : fallbackData.map(row => row.name || '');
            const valueAt = (serie, index) => Number(serie.data?.[index]?.value ?? serie.data?.[index] ?? 0);
            const indexes = sourceCategories.map((_, index) => index);
            const visibleIndexes = horizontal && indexes.length > 10
                ? indexes.sort((a, b) => normalizedSeries.reduce((total, serie) => total + valueAt(serie, b), 0) - normalizedSeries.reduce((total, serie) => total + valueAt(serie, a), 0)).slice(0, 10)
                : indexes;
            const visibleCategories = visibleIndexes.map(index => sourceCategories[index]);
            const visibleSeries = normalizedSeries.map(serie => ({ ...serie, data: visibleIndexes.map(index => serie.data?.[index] ?? 0) }));
            const totals = visibleCategories.map((_, index) => visibleSeries.reduce((total, serie) => total + valueAt(serie, index), 0));
            const hasMultipleSeries = visibleSeries.length > 1;
            const serieLabels = visibleSeries.map((serie, index) => ({ ...serie, type: 'bar',
                stack: horizontal ? (serie.stack || 'total') : undefined,
                itemStyle: { ...(serie.itemStyle || {}), color: chartPalette[index % chartPalette.length] },
                label: { show: !hasMultipleSeries || index === visibleSeries.length - 1, position: horizontal ? 'right' : 'top', fontSize: 7,
                    formatter: params => numberLabel(hasMultipleSeries ? totals[params.dataIndex] : params.value ?? 0) } }));
            const legend = hasMultipleSeries ? { top: 0, left: 'center', textStyle: { fontSize: 8 } } : undefined;
            chart.setOption(horizontal ? { ...base,
                legend,
                grid: { top: hasMultipleSeries ? 25 : 12, left: 140, right: 48, bottom: 18 },
                xAxis: { type: 'value', axisLabel: { fontSize: 8 }, splitLine: { lineStyle: { color: '#eaeaea' } } },
                yAxis: { type: 'category', inverse: true, data: visibleCategories, axisTick: { show: false }, axisLabel: { fontSize: 8, width: 125, overflow: 'truncate' } },
                series: serieLabels,
            } : { ...base,
                legend,
                grid: { top: hasMultipleSeries ? 28 : 14, left: 45, right: 18, bottom: 32 },
                xAxis: { type: 'category', data: chartData.eje_x?.categorias || [], axisLabel: { fontSize: 8 } },
                yAxis: { type: 'value', axisLabel: { fontSize: 8 }, splitLine: { lineStyle: { color: '#eaeaea' } } },
                series: serieLabels,
            });
        }
        chart.resize();
    }

    async function renderProfile() {
        try {
            Object.values(profile).forEach(items => (items || []).forEach(item => {
                try { renderChart(item); } catch (error) { console.warn('Gráfica regional no disponible', error); }
            }));
            await Promise.all(Array.from(document.images).map(image => image.decode().catch(() => {})));
        } finally {
            requestAnimationFrame(() => requestAnimationFrame(() => { window.__pdfReady = true; }));
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderProfile);
    else renderProfile();
    window.setTimeout(() => { window.__pdfReady = true; }, 12000);
</script>
</body>
</html>
