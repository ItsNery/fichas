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
            $rows = [0, 0, 0];
            foreach ($items as $item) {
                $values = $item['datos']['valores'] ?? [];
                $type = mb_strtolower((string) $item['indicador']->tipo_grafico_default);
                $longestLabel = collect($values)->max(fn ($value) => mb_strlen((string) ($value['nombre'] ?? ''))) ?? 0;
                $span = str_contains($type, 'pirámide') || count($values) > 8
                    ? 3
                    : ((count($values) > 4 || (count($values) > 1 && $longestLabel > 32)) ? 2 : 1);
                $row = collect($rows)->search(fn ($used) => $used + $span <= 3);
                if ($row === false && $chunk->isNotEmpty()) {
                    $chunks->push($chunk);
                    $chunk = collect();
                    $rows = [0, 0, 0];
                    $row = 0;
                }
                $chunk->push($item + ['span' => $span]);
                $rows[$row] += $span;
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
        :root { --burgundy:#872341; --gold:#b88b5b; --green:#256259; --ink:#262626; --light:#eaeaea; --sand:#dcb993; }
        @page { size:432mm 279mm; margin:0; }
        * { box-sizing:border-box; }
        body { margin:0; background:#eaeaea; color:var(--ink); font-family:Corra,Arial,sans-serif; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .sheet { width:432mm; height:279mm; margin:0 auto 5mm; padding:11mm 15mm 8mm; background:#fff; position:relative; overflow:hidden; break-after:page; page-break-after:always; }
        .sheet:last-of-type { break-after:auto; page-break-after:auto; }
        .sheet::before { content:""; position:absolute; top:11mm; left:0; width:4mm; height:8mm; background:var(--green); box-shadow:0 10mm #eaeaea,0 20mm #eaeaea,0 30mm #eaeaea; }
        .identity { height:48mm; margin:0 0 5mm; display:grid; grid-template-columns:58mm minmax(0,1fr) 126mm; align-items:center; gap:9mm; border-bottom:.5mm solid var(--light); }
        .identity-copy { min-width:0; text-align:center; }
        .municipality { margin:0; font:700 25pt/1 Gilroy,Arial,sans-serif; color:var(--green); }
        .regionalization { margin:2mm 0 4mm; font-size:10.5pt; color:#555; }
        .stats { display:flex; justify-content:center; flex-wrap:wrap; gap:3mm 8mm; }
        .stat { display:flex; align-items:baseline; gap:2mm; border-left:1.4mm solid var(--gold); padding-left:2mm; font-size:10pt; }
        .stat b { color:var(--burgundy); font-size:13pt; }
        .map { width:58mm; height:44mm; position:relative; }
        .map-label { position:absolute; bottom:0; width:100%; text-align:center; font-size:8pt; color:#777; }
        .municipality-map { width:100%; height:40mm; }
        .identity img { width:126mm; max-height:18mm; object-fit:contain; object-position:right center; }
        .section-head { display:flex; align-items:center; gap:4mm; margin:0 0 4mm; }
        .section-head::before { content:""; width:2mm; align-self:stretch; background:var(--green); }
        .section-head h2 { font:700 17pt Gilroy,Arial,sans-serif; margin:0; color:var(--ink); }
        .section-head span { margin-left:auto; font-size:9pt; color:#777; }
        .cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); grid-template-rows:repeat(3,61mm); grid-auto-flow:dense; gap:4mm 6mm; }
        .card { position:relative; border-top:.7mm solid var(--light); padding:3mm 3mm 2mm; min-width:0; overflow:hidden; display:flex; flex-direction:column; break-inside:avoid; }
        .card--span-2 { grid-column:span 2; }
        .card--span-3 { grid-column:1 / -1; }
        .card:nth-child(3n + 1) { border-top-color:var(--burgundy); }
        .card:nth-child(3n + 2) { border-top-color:var(--gold); }
        .card:nth-child(3n) { border-top-color:var(--green); }
        .card-header { display:flex; justify-content:space-between; align-items:flex-start; gap:3mm; min-height:14mm; }
        .card h3 { margin:0; font:700 13pt/1.12 Gilroy,Arial,sans-serif; max-width:150mm; }
        .year { flex:none; background:var(--light); color:var(--green); padding:1mm 2.5mm; font:700 10pt Corra,Arial,sans-serif; }
        .topic { font-size:8pt; color:var(--green); text-transform:uppercase; letter-spacing:.06em; margin:0 0 1mm; }
        .visual { flex:1; width:100%; min-height:0; }
        .visual--kpi { display:flex; align-items:center; gap:6mm; padding:3mm 5mm; background:linear-gradient(90deg,#f7f3ef 0,#fff 72%); }
        .kpi-number { font:700 28pt/1 Gilroy,Arial,sans-serif; color:var(--burgundy); letter-spacing:-.03em; }
        .kpi-copy { max-width:105mm; color:#555; font-size:10pt; line-height:1.15; }
        .kpi-unit { display:block; margin-top:1mm; color:var(--green); font-size:9pt; text-transform:uppercase; letter-spacing:.06em; }
        .visual--category { display:flex; align-items:center; justify-content:center; }
        .category-value { max-width:150mm; padding:4mm 9mm; background:var(--green); color:#fff; font:700 18pt Gilroy,Arial,sans-serif; text-align:center; }
        .visual--percent { display:flex; align-items:center; justify-content:center; gap:7mm; }
        .percent-ring { --value:0; width:35mm; height:35mm; border-radius:50%; display:grid; place-items:center; background:conic-gradient(var(--green) calc(var(--value) * 1%),var(--light) 0); position:relative; }
        .percent-ring::before { content:""; position:absolute; inset:4mm; border-radius:50%; background:#fff; }
        .percent-ring b { position:relative; z-index:1; font:700 17pt Gilroy,Arial,sans-serif; color:var(--burgundy); }
        .percent-label { width:75mm; font-size:11pt; line-height:1.2; color:#555; }
        .values { display:flex; flex-wrap:wrap; gap:1mm 5mm; margin:1mm 0; }
        .values span { font-size:9pt; }
        .values b { color:var(--burgundy); }
        .source { color:#666; font-size:7pt; line-height:1.1; margin:1mm 0 0; max-height:8mm; overflow:hidden; }
        .empty { color:#777; font-size:11pt; align-content:center; text-align:center; }
        .footer { position:absolute; bottom:5mm; left:15mm; right:15mm; border-top:.3mm solid var(--light); padding-top:1.5mm; display:flex; justify-content:space-between; color:#777; font-size:8pt; }
        @media screen { .sheet { box-shadow:0 3px 24px #0003; } }
        @media print { body { background:#fff; } .sheet { margin:0; box-shadow:none; } }
    </style>
</head>
<body>
@foreach($pages as $page)
    <section class="sheet">
        <header class="identity">
            <div class="map"><div class="municipality-map"></div><span class="map-label">Ubicación en Puebla</span></div>
            <div class="identity-copy">
                <h1 class="municipality">{{ $municipio->nombre }}</h1>
                <p class="regionalization">{{ $municipio->microrregion?->macrorregion?->nombre ?? 'Puebla' }} · {{ $municipio->microrregion?->nombre ?? 'Sin región' }}</p>
                <div class="stats">
                    <div class="stat">Población <b>{{ number_format($hero['poblacionTotal']) }}</b></div>
                    <div class="stat">Marginación <b>{{ $hero['gradoMarginacion'] }}</b></div>
                    <div class="stat">Pobreza <b>{{ $hero['porcentajePobreza'] }}</b></div>
                </div>
            </div>
            <img src="data:image/png;base64,{{ $cintillo }}" alt="Cintillo institucional SEI">
        </header>
        <div class="section-head"><h2>{{ $page['dimension'] }}</h2><span>{{ $page['continuacion'] ? 'Continuación · ' : '' }}Último corte por indicador</span></div>
        <div class="cards">
            @foreach($page['items'] as $item)
                @php($indicator = $item['indicador'])
                @php($data = $item['datos'])
                <article class="card card--span-{{ $item['span'] }}">
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
        <footer class="footer"><span>Secretaría de Planeación y Finanzas · Sistema Estatal de Información</span><span>{{ $municipio->nombre }} · {{ $loop->iteration }} / {{ $pages->count() }}</span></footer>
    </section>
@endforeach
<script>
    const panorama = @json($perfil);
    const geojsonUrl = @json($geojsonUrl);
    const cvegeo = @json((string) $municipio->cvegeo);
    const colors = ['#872341', '#256259', '#b88b5b', '#3f9882'];
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
        if (!numeric.length) return;
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
        const base = {animation:false,color:colors,tooltip:{show:false},textStyle:{fontFamily:'Corra, Arial'},
            grid:{left:6,right:16,top:12,bottom:6,containLabel:true}};

        if (type.includes('piramide')) {
            const groups = new Map();
            numeric.forEach(value => {
                const name = normalized(value.nombre);
                const match = name.match(/(\d+\s+a\s+\d+|100\s+anos\s+y\s+mas|edad\s+no\s+especificada)/);
                const age = match ? match[1].replace('anos','años') : value.nombre;
                if (!groups.has(age)) groups.set(age,{hombres:0,mujeres:0});
                groups.get(age)[name.includes('mujer') ? 'mujeres' : 'hombres'] = Number(value.valor);
            });
            const rows = Array.from(groups.entries());
            chart.setOption({...base,legend:{top:0},grid:{left:48,right:48,top:22,bottom:4,containLabel:true},
                xAxis:{type:'value',axisLabel:{fontSize:8,formatter:value => numberLabel(Math.abs(value))}},
                yAxis:{type:'category',data:rows.map(row => row[0]),axisLabel:{fontSize:8}},
                series:[
                    {name:'Hombres',type:'bar',stack:'total',data:rows.map(row => -row[1].hombres),itemStyle:{color:'#256259'}},
                    {name:'Mujeres',type:'bar',stack:'total',data:rows.map(row => row[1].mujeres),itemStyle:{color:'#dcb993'}}
                ]});
        } else if (['pastel','pie','donut','dona'].includes(type)) {
            chart.setOption({...base,series:[{type:'pie',radius:type === 'donut' || type === 'dona' ? ['35%','65%'] : '67%',
                data:numeric.map(value => ({name:value.nombre,value:Number(value.valor)})),label:{fontSize:10,formatter:'{b}: {d}%'}}]});
        } else if (type.includes('lineal') || type.includes('linea')) {
            chart.setOption({...base,grid:{left:8,right:24,top:8,bottom:5,containLabel:true},
                xAxis:{type:'value',axisLabel:{fontSize:8},splitLine:{lineStyle:{color:'#eaeaea'}}},
                yAxis:{type:'category',data:categories,axisLabel:{fontSize:9,width:185,overflow:'truncate'}},
                series:[{type:'scatter',symbolSize:13,data:numbers.map((value,index) => [value,index]),itemStyle:{color:'#256259'},
                    label:{show:true,position:'right',fontSize:9,color:'#262626',formatter:params => numberLabel(params.value[0])}}]});
        } else {
            const vertical = numeric.length <= 5 && Math.max(...categories.map(value => value.length)) < 28;
            chart.setOption(vertical ? {...base,grid:{left:8,right:8,top:12,bottom:5,containLabel:true},
                xAxis:{type:'category',data:categories,axisLabel:{fontSize:9,width:100,overflow:'break'}},
                yAxis:{type:'value',axisLabel:{fontSize:8}},
                series:[{type:'bar',data:numbers,barMaxWidth:30,label:{show:true,position:'top',fontSize:9,formatter:params => numberLabel(params.value)}}]}
                : {...base,xAxis:{type:'value',axisLabel:{fontSize:8}},
                    yAxis:{type:'category',data:categories,axisLabel:{fontSize:9,width:185,overflow:'truncate'}},
                    series:[{type:'bar',data:numbers,barMaxWidth:18,label:{show:true,position:'right',fontSize:9,formatter:params => numberLabel(params.value)}}]});
        }
    }

    async function renderPanorama() {
        try {
            if (window.echarts) {
                Object.values(panorama).forEach(tematicas => Object.values(tematicas).forEach(items => items.forEach(item => {
                    try { renderIndicator(item); } catch (error) { console.warn('Gráfica no disponible', item.indicador.id, error); }
                })));
                const response = await fetch(geojsonUrl);
                if (response.ok) {
                    const geojson = await response.json();
                    geojson.features.forEach(feature => feature.properties.name = feature.properties.nomgeo);
                    echarts.registerMap('puebla-panorama',geojson);
                    const feature = geojson.features.find(row => String(row.properties.cvegeo) === cvegeo);
                    document.querySelectorAll('.municipality-map').forEach(el => {
                        echarts.init(el,null,{renderer:'svg'}).setOption({animation:false,series:[{type:'map',map:'puebla-panorama',silent:true,
                            layoutCenter:['50%','47%'],layoutSize:'98%',label:{show:false},
                            itemStyle:{areaColor:'#efefed',borderColor:'#aaa',borderWidth:.35},
                            data:[{name:feature?.properties?.nomgeo || '',itemStyle:{areaColor:'#872341',borderColor:'#b88b5b',borderWidth:1.3},
                                label:{show:true,formatter:'●',color:'#dcb993',fontSize:10,textBorderColor:'#872341',textBorderWidth:2}}]}]});
                    });
                }
            }
        } catch (error) { console.warn('Mapa no disponible', error); }
        finally { requestAnimationFrame(() => requestAnimationFrame(() => window.__pdfReady = true)); }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderPanorama);
    else renderPanorama();
    window.setTimeout(() => window.__pdfReady = true, 12000);
</script>
</body>
</html>
