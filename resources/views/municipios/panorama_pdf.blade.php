<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panorama municipal · {{ $municipio->nombre }}</title>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    @php
        $gilroy = base64_encode(file_get_contents(public_path('css/fuentes/Gilroy/Gilroy-Bold.ttf')));
        $corra = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Regular.ttf')));
        $corraBold = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Bold.ttf')));
        $pages = collect($perfil)->flatMap(function ($tematicas, $dimension) {
            $items = collect($tematicas)->flatMap(fn ($rows, $tematica) => collect($rows)->map(
                fn ($row) => $row + ['tematica' => $tematica]
            ));
            $chunks = collect();
            $chunk = collect();
            $grid = array_fill(0, 3, array_fill(0, 3, false));
            foreach ($items as $item) {
                $values = $item['datos']['valores'] ?? [];
                $type = mb_strtolower(\Illuminate\Support\Str::ascii((string) $item['indicador']->tipo_grafico_default));
                $isPyramid = str_contains($type, 'piramide');
                $longestLabel = collect($values)->max(fn ($value) => mb_strlen((string) ($value['nombre'] ?? ''))) ?? 0;
                $span = $isPyramid || count($values) > 8
                    ? 3
                    : ((count($values) > 4 || (count($values) > 1 && $longestLabel > 32)) ? 2 : 1);
                $rowSpan = $isPyramid ? 2 : 1;
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

                if ($position === null && $chunk->isNotEmpty()) {
                    $chunks->push($chunk);
                    $chunk = collect();
                    $grid = array_fill(0, 3, array_fill(0, 3, false));
                    $position = [0, 0];
                }

                [$row, $column] = $position;
                for ($gridRow = $row; $gridRow < $row + $rowSpan; $gridRow++) {
                    for ($gridColumn = $column; $gridColumn < $column + $span; $gridColumn++) {
                        $grid[$gridRow][$gridColumn] = true;
                    }
                }
                $chunk->push($item + ['span' => $span, 'rowSpan' => $rowSpan, 'isPyramid' => $isPyramid]);
            }
            if ($chunk->isNotEmpty()) $chunks->push($chunk);
            return $chunks->map(fn ($pageItems, $index) => [
                'dimension' => $dimension, 'continuacion' => $index > 0, 'items' => $pageItems,
            ]);
        })->values();
    @endphp
    <style>
        @font-face { font-family:Gilroy; src:url(data:font/ttf;base64,{{ $gilroy }}) format('truetype'); font-weight:700; }
        @font-face { font-family:Corra; src:url(data:font/ttf;base64,{{ $corra }}) format('truetype'); font-weight:400; }
        @font-face { font-family:Corra; src:url(data:font/ttf;base64,{{ $corraBold }}) format('truetype'); font-weight:700; }
        :root {
            --brand-burgundy-700:#872341;
            --brand-burgundy-600:#961638;
            --brand-burgundy-500:#ac1a33;
            --brand-gold-500:#b88b5b;
            --brand-green-900:#0d312d;
            --brand-green-700:#256259;
            --brand-green-500:#3f9882;
            --brand-sand-300:#dcb993;
            --brand-neutral-900:#262626;
            --brand-neutral-400:#afafaf;
            --brand-neutral-100:#eaeaea;
            --brand-white:#fff;
            --color-primary:var(--brand-burgundy-700);
            --color-secondary:var(--brand-green-900);
            --color-accent:var(--brand-gold-500);
            --color-text:var(--brand-neutral-900);
            --color-border:var(--brand-neutral-400);
            --color-surface-muted:var(--brand-neutral-100);
            --color-surface:var(--brand-white);
            --dimension-accent:var(--brand-burgundy-700);
        }
        @page { size:432mm 279mm; margin:0; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--color-surface-muted); color:var(--color-text); font-family:Corra,Arial,sans-serif; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .sheet { width:432mm; height:279mm; margin:0 auto 5mm; padding:11mm 15mm 8mm; background:var(--color-surface); position:relative; overflow:hidden; break-after:page; page-break-after:always; }
        .sheet:last-of-type { break-after:auto; page-break-after:auto; }
        .sheet::before { content:""; position:absolute; top:11mm; left:0; width:3mm; height:48mm; background:var(--dimension-accent); }
        .dimension--demografica-y-social { --dimension-accent:var(--brand-green-700); }
        .dimension--economico { --dimension-accent:var(--brand-burgundy-700); }
        .dimension--geografica-y-medio-ambiente { --dimension-accent:var(--brand-gold-500); }
        .dimension--gobierno-seguridad-e-imparticion-de-justicia { --dimension-accent:var(--brand-green-900); }
        .identity { height:48mm; margin:0 0 5mm; padding:1mm 0 1.5mm; display:grid; grid-template-columns:58mm minmax(0,1fr) 126mm; align-items:center; gap:9mm; border-top:.6mm solid var(--color-accent); border-bottom:1.2mm solid var(--color-primary); }
        .identity-copy { min-width:0; text-align:center; }
        .municipality { margin:0; font:700 27pt/.95 Gilroy,Arial,sans-serif; color:var(--color-primary); }
        .regionalization { margin:2.2mm 0 3mm; font-size:10.5pt; color:var(--color-text); }
        .regionalization-label { color:var(--brand-green-700); font-weight:700; }
        .stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:0; width:100%; max-width:180mm; margin:0 auto; }
        .stat { min-width:0; border-left:1mm solid var(--color-accent); padding:0 3mm; color:var(--color-text); font-size:8.5pt; line-height:1.05; text-align:left; }
        .stat span { display:block; margin-bottom:.7mm; color:var(--brand-green-700); font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .stat b { display:block; color:var(--color-primary); font:700 13.5pt/1 Gilroy,Arial,sans-serif; }
        .map { width:58mm; height:44mm; position:relative; }
        .map-label { position:absolute; bottom:0; width:100%; text-align:center; font-size:8pt; color:var(--color-text); }
        .municipality-map { width:100%; height:40mm; }
        .municipality-map img { display:block; width:100%; height:100%; object-fit:contain; }
        .identity > img { display:block; width:126mm; max-height:18mm; object-fit:contain; object-position:right center; }
        .section-head { min-height:8mm; display:flex; align-items:center; gap:4mm; margin:0 0 4mm; padding:1mm 3mm; background:var(--dimension-accent); border-left:2mm solid var(--color-accent); }
        .section-head h2 { font:700 17pt/1 Gilroy,Arial,sans-serif; margin:0; color:var(--brand-white); }
        .section-head span { margin-left:auto; color:var(--brand-white); font-size:9pt; font-weight:700; letter-spacing:.02em; }
        .cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); grid-template-rows:repeat(3,61mm); grid-auto-flow:dense; gap:4mm 6mm; }
        .card { position:relative; border:.3mm solid var(--color-surface-muted); border-top:1mm solid var(--dimension-accent); padding:3mm 3mm 2mm; min-width:0; overflow:hidden; display:flex; flex-direction:column; break-inside:avoid; background:var(--color-surface); }
        .card--span-2 { grid-column:span 2; }
        .card--span-3 { grid-column:1 / -1; }
        .card--rows-2 { grid-row:span 2; }
        .card--pyramid { border-top-color:var(--dimension-accent) !important; background:linear-gradient(180deg,var(--brand-white) 0,var(--brand-neutral-100) 100%); padding-left:5mm; padding-right:5mm; }
        .card--pyramid .visual { min-height:88mm; }
        .card--pyramid .source { font-size:8pt; }
        .card:nth-child(3n + 1) { border-top-color:var(--dimension-accent); }
        .card:nth-child(3n + 2) { border-top-color:var(--dimension-accent); }
        .card:nth-child(3n) { border-top-color:var(--dimension-accent); }
        .card-header { display:flex; justify-content:space-between; align-items:flex-start; gap:3mm; min-height:14mm; }
        .card h3 { margin:0; max-width:150mm; color:var(--color-text); font:700 13pt/1.12 Gilroy,Arial,sans-serif; }
        .year { flex:none; min-width:15mm; padding:1mm 2.5mm; background:var(--color-primary); color:var(--brand-white); font:700 10pt Corra,Arial,sans-serif; text-align:center; }
        .topic { margin:0 0 1mm; color:var(--brand-green-700); font-size:8pt; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
        .visual { flex:1; width:100%; min-height:0; }
        .visual--kpi { display:flex; align-items:center; gap:6mm; padding:3mm 5mm; background:var(--color-surface-muted); border-left:1.2mm solid var(--dimension-accent); }
        .kpi-number { color:var(--color-primary); font:700 28pt/1 Gilroy,Arial,sans-serif; letter-spacing:-.03em; }
        .kpi-copy { max-width:105mm; color:var(--color-text); font-size:10pt; line-height:1.15; }
        .kpi-unit { display:block; margin-top:1mm; color:var(--brand-green-700); font-size:9pt; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
        .visual--category { display:flex; align-items:center; justify-content:center; }
        .category-value { max-width:150mm; padding:4mm 9mm; background:var(--color-secondary); color:var(--brand-white); font:700 18pt Gilroy,Arial,sans-serif; text-align:center; border-bottom:1mm solid var(--color-accent); }
        .visual--percent { display:flex; align-items:center; justify-content:center; gap:7mm; }
        .percent-ring { --value:0; width:33mm; height:33mm; border-radius:50%; display:grid; place-items:center; background:conic-gradient(var(--dimension-accent) calc(var(--value) * 1%),var(--color-surface-muted) 0); position:relative; }
        .percent-ring::before { content:""; position:absolute; inset:4mm; border-radius:50%; background:var(--color-surface); }
        .percent-ring b { position:relative; z-index:1; color:var(--color-primary); font:700 17pt Gilroy,Arial,sans-serif; }
        .percent-label { width:75mm; color:var(--color-text); font-size:11pt; line-height:1.2; }
        .values { display:flex; flex-wrap:wrap; gap:1mm 5mm; margin:1mm 0; }
        .values span { font-size:9pt; }
        .values b { color:var(--color-primary); }
        .source { flex:none; margin:1mm 0 0; color:var(--color-text); font-size:7.5pt; line-height:1.1; }
        .empty { display:flex; flex-direction:column; justify-content:center; padding:4mm; background:var(--color-surface-muted); color:var(--color-text); font-size:11pt; font-weight:700; text-align:center; }
        .empty::before { content:"—"; display:block; margin-bottom:1mm; color:var(--color-primary); font:700 16pt/1 Gilroy,Arial,sans-serif; }
        .footer { position:absolute; bottom:5mm; left:15mm; right:15mm; display:flex; justify-content:space-between; align-items:center; border-top:.5mm solid var(--brand-green-700); padding-top:1.5mm; color:var(--color-text); font-size:8pt; }
        .footer-agency { font-weight:700; }
        .footer-page { border-left:1mm solid var(--color-accent); padding-left:3mm; text-align:right; }
        .footer-page b { color:var(--color-primary); }
        @media print { body { background:#fff; } .sheet { margin:0; box-shadow:none; } }
    </style>
</head>
<body>
@foreach($pages as $page)
    <section class="sheet dimension--{{ \Illuminate\Support\Str::slug($page['dimension']) }}">
        <header class="identity">
            <div class="map"><div class="municipality-map"><img src="{{ $mapImageDataUri ?? route('ficha-municipal.panorama.map', $municipio) }}" alt="Ubicación de {{ $municipio->nombre }} en Puebla"></div><span class="map-label">Ubicación en Puebla</span></div>
            <div class="identity-copy">
                <h1 class="municipality">{{ $municipio->nombre }}</h1>
                <p class="regionalization"><span class="regionalization-label">Regionalización:</span> {{ $municipio->microrregion?->macrorregion?->nombre ?? 'Puebla' }} · {{ $municipio->microrregion?->nombre ?? 'Sin región' }}</p>
                <div class="stats">
                    <div class="stat"><span>Población</span><b>{{ number_format($hero['poblacionTotal']) }}</b></div>
                    <div class="stat"><span>Marginación</span><b>{{ $hero['gradoMarginacion'] }}</b></div>
                    <div class="stat"><span>Pobreza</span><b>{{ $hero['porcentajePobreza'] }}</b></div>
                </div>
            </div>
            <img src="data:image/png;base64,{{ $cintillo }}" alt="Cintillo institucional SEI">
        </header>
        <div class="section-head"><h2>{{ $page['dimension'] }}</h2><span>{{ $page['continuacion'] ? 'Continuación · ' : '' }}Último corte por indicador</span></div>
        <div class="cards">
            @foreach($page['items'] as $item)
                @php($indicator = $item['indicador'])
                @php($data = $item['datos'])
                <article class="card card--span-{{ $item['span'] }} card--rows-{{ $item['rowSpan'] }}{{ $item['isPyramid'] ? ' card--pyramid' : '' }}">
                    <p class="topic">{{ $item['tematica'] }}</p>
                    <div class="card-header"><h3>{{ $indicator->nombre_amigable }}</h3><span class="year">{{ $data['anio'] ?? 'S/D' }}</span></div>
                    @if($data && !empty($data['complejo']))
                        <div class="visual empty">Detalle disponible en el banco de indicadores</div>
                    @elseif($data)
                        <div class="visual" id="chart-{{ $indicator->id }}"></div>
                    @else
                        <div class="visual empty">Sin datos disponibles para este municipio</div>
                    @endif
                    @if($indicator->fuente)<p class="source">Fuente: {{ $indicator->fuente }}</p>@endif
                </article>
            @endforeach
        </div>
        <footer class="footer">
            <span class="footer-agency">
                Secretaría de Planeación, Finanzas y Administración · Sistema Estatal de Información
            </span>
            <span class="footer-page">
                {{ $municipio->nombre }} · <b>Página {{ $loop->iteration }} de {{ $pages->count() }}</b>
            </span>
        </footer>
    </section>
@endforeach
<script>
    const panorama = @json($perfil);
    const colors = ['#872341', '#256259', '#b88b5b', '#3f9882', '#ac1a33', '#dcb993'];
    window.__pdfReady = false;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
    }

    function normalized(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function numberLabel(value) {
        return new Intl.NumberFormat('es-MX', {maximumFractionDigits:2}).format(value);
    }

    function wrapAxisLabel(value, maxLength) {
        const lines = [];
        let line = '';

        String(value ?? '').split(/\s+/).forEach(word => {
            if (!line || (line + ' ' + word).length <= maxLength) {
                line += (line ? ' ' : '') + word;
            } else {
                lines.push(line);
                line = word;
            }
        });

        if (line) lines.push(line);
        return lines.join('\n');
    }

    function renderIndicator(item) {
        const el = document.getElementById('chart-' + item.indicador.id);
        if (!el || !item.datos) return;
        const data = item.datos;
        const values = data.valores || [];
        if (!values.length) return;
        const type = normalized(item.indicador.tipo_grafico_default);
        if (['categoria','categorica'].includes(type)) {
            el.classList.add('visual--category');
            el.innerHTML = '<div class="category-value">' + values.map(value => escapeHtml(value.display)).join(' · ') + '</div>';
            return;
        }
        const numeric = values.filter(value => value.valor !== null && Number.isFinite(Number(value.valor)));
        if (!numeric.length) {
            const reasons = [...new Set(values.map(value => value.display || 'Sin dato'))];
            el.classList.add('visual--category');
            el.innerHTML = '<div class="category-value">' + reasons.map(escapeHtml).join(' · ') + '</div>';
            return;
        }
        if (numeric.length === 1) {
            const value = numeric[0];
            const amount = Number(value.valor);
            const unit = normalized(value.unidad);
            if ((unit.includes('porcentaje') || unit === '%') && amount >= 0 && amount <= 100) {
                el.classList.add('visual--percent');
                el.innerHTML = '<div class="percent-ring" style="--value:' + amount + '"><b>' + numberLabel(amount) + '%</b></div>'
                    + '<div class="percent-label">' + escapeHtml(value.nombre) + '</div>';
            } else {
                el.classList.add('visual--kpi');
                el.innerHTML = '<div class="kpi-number">' + escapeHtml(value.display) + '</div><div class="kpi-copy">'
                    + escapeHtml(value.nombre) + (value.unidad ? '<span class="kpi-unit">' + escapeHtml(value.unidad) + '</span>' : '') + '</div>';
            }
            return;
        }
        if (!window.echarts) return;
        const categories = numeric.map(value => value.nombre);
        const numbers = numeric.map(value => Number(value.valor));
        const chart = echarts.init(el, null, {renderer:'svg'});
        const dimensionAccent = getComputedStyle(el.closest('.sheet')).getPropertyValue('--dimension-accent').trim() || colors[0];
        const chartColors = [dimensionAccent, ...colors.filter(color => color.toLowerCase() !== dimensionAccent.toLowerCase())];
        const base = {animation:false,color:chartColors,tooltip:{show:false},textStyle:{fontFamily:'Corra, Arial'},
            grid:{left:6,right:16,top:12,bottom:6,containLabel:true}};
        const isWideChart = el.closest('.card')?.classList.contains('card--span-2')
            || el.closest('.card')?.classList.contains('card--span-3');
        const wrapCategoryLabels = categories.length <= 5;
        const categoryAxisLabel = {
            fontSize:9,
            lineHeight:11,
            interval:0,
            width:wrapCategoryLabels && isWideChart ? 310 : 185,
            overflow:wrapCategoryLabels ? 'break' : 'truncate',
            formatter:wrapCategoryLabels ? value => wrapAxisLabel(value, isWideChart ? 42 : 26) : undefined
        };

        if (type.includes('piramide')) {
            const groups = new Map();
            numeric.forEach(value => {
                const name = normalized(value.nombre);
                const range = name.match(/(\d+)\s+a\s+(\d+)/);
                const openRange = name.match(/(\d+)\s+anos\s+y\s+mas/);
                const unspecified = name.includes('edad no especificada');
                const sex = name.includes('mujer') ? 'mujeres' : (name.includes('hombre') ? 'hombres' : null);
                if (!sex || (!range && !openRange && !unspecified)) return;

                const age = range
                    ? range[1] + ' a ' + range[2]
                    : (openRange ? openRange[1] + ' y más' : 'No especificada');
                const order = range ? Number(range[1]) : (openRange ? Number(openRange[1]) : Number.MAX_SAFE_INTEGER);
                if (!groups.has(age)) groups.set(age,{hombres:0,mujeres:0,order});
                groups.get(age)[sex] = Number(value.valor);
            });
            const rows = Array.from(groups.entries()).sort((a,b) => a[1].order - b[1].order);
            const maxValue = Math.max(1, ...rows.flatMap(row => [row[1].hombres,row[1].mujeres]));
            const rawStep = maxValue / 4;
            const magnitude = 10 ** Math.floor(Math.log10(rawStep));
            const normalizedStep = rawStep / magnitude;
            const stepFactor = normalizedStep <= 1 ? 1 : (normalizedStep <= 2 ? 2 : (normalizedStep <= 5 ? 5 : 10));
            const interval = stepFactor * magnitude;
            const axisMax = Math.ceil(maxValue / interval) * interval;

            chart.setOption({...base,
                legend:{top:0,left:'center',itemWidth:14,itemHeight:8,textStyle:{fontSize:11,color:'#262626'}},
                grid:{left:72,right:72,top:28,bottom:22,containLabel:true},
                xAxis:{type:'value',min:-axisMax,max:axisMax,interval,
                    name:'Habitantes',nameLocation:'middle',nameGap:25,nameTextStyle:{fontSize:10,color:'#262626'},
                    axisLabel:{fontSize:9,color:'#262626',formatter:value => numberLabel(Math.abs(value))},
                    axisLine:{show:true,lineStyle:{color:'#afafaf'}},splitLine:{lineStyle:{color:'#eaeaea'}}},
                yAxis:{type:'category',data:rows.map(row => row[0]),
                    axisTick:{show:false},axisLine:{lineStyle:{color:'#afafaf'}},
                    axisLabel:{show:true,interval:0,fontSize:10,lineHeight:12,color:'#262626',margin:10}},
                series:[
                    {name:'Hombres',type:'bar',stack:'total',barMaxWidth:14,barCategoryGap:'20%',data:rows.map(row => -row[1].hombres),
                        itemStyle:{color:'#256259'},label:{show:true,position:'left',distance:4,fontSize:8,color:'#262626',formatter:params => numberLabel(Math.abs(params.value))}},
                    {name:'Mujeres',type:'bar',stack:'total',barMaxWidth:14,barCategoryGap:'20%',data:rows.map(row => row[1].mujeres),
                        itemStyle:{color:'#872341'},label:{show:true,position:'right',distance:4,fontSize:8,color:'#262626',formatter:params => numberLabel(params.value)}}
                ]});
        } else if (['pastel','pie','donut','dona'].includes(type)) {
            chart.setOption({...base,series:[{type:'pie',radius:type === 'donut' || type === 'dona' ? ['35%','65%'] : '67%',
                data:numeric.map(value => ({name:value.nombre,value:Number(value.valor)})),label:{fontSize:10,formatter:'{b}: {d}%'}}]});
        } else if (type.includes('lineal') || type.includes('linea')) {
            chart.setOption({...base,grid:{left:8,right:24,top:8,bottom:5,containLabel:true},
                xAxis:{type:'value',axisLabel:{fontSize:8},splitLine:{lineStyle:{color:'#eaeaea'}}},
                yAxis:{type:'category',data:categories,axisLabel:categoryAxisLabel},
                series:[{type:'scatter',symbolSize:13,data:numbers.map((value,index) => [value,index]),itemStyle:{color:dimensionAccent},
                    label:{show:true,position:'right',fontSize:9,color:'#262626',formatter:params => numberLabel(params.value[0])}}]});
        } else {
            const vertical = numeric.length <= 5 && Math.max(...categories.map(value => value.length)) < 28;
            chart.setOption(vertical ? {...base,grid:{left:8,right:8,top:12,bottom:5,containLabel:true},
                xAxis:{type:'category',data:categories,axisLabel:{fontSize:9,width:100,overflow:'break'}},
                yAxis:{type:'value',axisLabel:{fontSize:8}},
                series:[{type:'bar',data:numbers,barMaxWidth:30,itemStyle:{color:dimensionAccent},label:{show:true,position:'top',fontSize:9,formatter:params => numberLabel(params.value)}}]}
                : {...base,xAxis:{type:'value',axisLabel:{fontSize:8}},
                    yAxis:{type:'category',data:categories,axisLabel:categoryAxisLabel},
                    series:[{type:'bar',data:numbers,barMaxWidth:18,itemStyle:{color:dimensionAccent},label:{show:true,position:'right',fontSize:9,formatter:params => numberLabel(params.value)}}]});
        }
    }

    async function renderPanorama() {
        try {
            if (window.echarts) {
                Object.values(panorama).forEach(tematicas => Object.values(tematicas).forEach(items => items.forEach(item => {
                    try { renderIndicator(item); } catch (error) { console.warn('Gráfica no disponible', item.indicador.id, error); }
                })));
            }
            await Promise.all(Array.from(document.images).map(image => image.decode().catch(() => {})));
        } catch (error) { console.warn('Mapa no disponible', error); }
        finally { requestAnimationFrame(() => requestAnimationFrame(() => window.__pdfReady = true)); }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderPanorama);
    else renderPanorama();
    window.setTimeout(() => window.__pdfReady = true, 12000);
</script>
</body>
</html>
