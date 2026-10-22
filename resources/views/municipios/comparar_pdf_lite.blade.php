<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comparativa municipal - {{ $municipio1->nombre }} y {{ $municipio2->nombre }}</title>
    @php
        $gilroy = base64_encode(file_get_contents(public_path('css/fuentes/Gilroy/Gilroy-Bold.ttf')));
        $corra = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Regular.ttf')));
        $corraBold = base64_encode(file_get_contents(public_path('css/fuentes/corra-montserra/TTF/Corra-Montserra-Bold.ttf')));
        $logo = base64_encode(file_get_contents(public_path('img/LOGOS_negativo.png')));
    @endphp
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        @font-face { font-family: Gilroy; src: url(data:font/ttf;base64,{{ $gilroy }}) format('truetype'); font-weight: 700; }
        @font-face { font-family: Corra; src: url(data:font/ttf;base64,{{ $corra }}) format('truetype'); font-weight: 400; }
        @font-face { font-family: Corra; src: url(data:font/ttf;base64,{{ $corraBold }}) format('truetype'); font-weight: 700; }
        :root { --vino:#872341; --verde:#256259; --oro:#b88b5b; --tinta:#262626; --muted:#eaeaea; --fondo:#f8f6f3; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--tinta); font: 9pt/1.35 Corra, Arial, sans-serif; background: #fff; }
        .sheet { min-height: 190mm; position: relative; page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .masthead { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 10mm; padding: 4mm 5mm; color: #fff; background: linear-gradient(110deg, var(--vino), var(--verde)); }
        .masthead h1 { margin: 0; font: 700 23pt/1 Gilroy, Arial, sans-serif; letter-spacing: -.02em; }
        .masthead p { margin: 1.5mm 0 0; opacity: .88; font-size: 9pt; }
        .masthead img { display: block; width: 68mm; max-height: 14mm; object-fit: contain; object-position: right center; }
        .comparison-hero { display: grid; grid-template-columns: 1fr 18mm 1fr; gap: 4mm; align-items: stretch; margin: 6mm 0; }
        .municipio { min-width: 0; padding: 4mm 5mm; background: var(--fondo); border-top: 1.2mm solid var(--vino); }
        .municipio--right { border-top-color: var(--verde); }
        .municipio h2 { margin: 0 0 1mm; color: var(--vino); font: 700 17pt/1 Gilroy, Arial, sans-serif; }
        .municipio--right h2 { color: var(--verde); }
        .municipio p { margin: 0; color: #4c4c4c; font-size: 8.5pt; }
        .vs { display: grid; place-items: center; color: #fff; background: var(--oro); font: 700 11pt/1 Gilroy, Arial, sans-serif; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3mm; margin-top: 4mm; }
        .stat { padding-left: 2mm; border-left: .6mm solid var(--oro); }
        .stat span { display: block; color: #52645f; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .stat b { display: block; margin-top: .5mm; font: 700 12pt/1 Gilroy, Arial, sans-serif; }
        .section-head { display: flex; align-items: center; justify-content: space-between; gap: 6mm; margin: 5mm 0 3mm; padding: 2.5mm 4mm; color: #fff; background: var(--dimension); }
        .section-head h2 { margin: 0; font: 700 16pt/1 Gilroy, Arial, sans-serif; }
        .section-head span { font-size: 8pt; font-weight: 700; opacity: .9; }
        .indicators-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 3mm; align-items: start; }
        .indicator { min-width: 0; break-inside: avoid; border: .25mm solid var(--muted); }
        .indicator--wide { grid-column: 1 / -1; }
        .indicator-title { display: flex; justify-content: space-between; gap: 5mm; padding: 2mm 3mm; background: var(--fondo); }
        .indicator-title h3 { margin: 0; font: 700 10.5pt/1.2 Gilroy, Arial, sans-serif; }
        .indicator-title span { flex: none; color: var(--verde); font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1.8mm 3mm; color: #fff; background: var(--verde); font: 700 8pt/1.1 Gilroy, Arial, sans-serif; text-align: left; }
        th:first-child { width: 34%; }
        td { padding: 1.8mm 3mm; border-top: .2mm solid var(--muted); vertical-align: top; }
        td:not(:first-child) { font-weight: 700; }
        tr:nth-child(even) td { background: #fcfbf9; }
        .unit { display: block; margin-top: .3mm; color: #68746f; font-size: 7.5pt; font-weight: 400; }
        .year { display: block; margin-top: .3mm; color: #68746f; font-size: 7.5pt; font-weight: 400; }
        .indicator--compact .indicator-title { gap: 2mm; }
        .indicator--compact .indicator-title h3 { font-size: 9.5pt; }
        .indicator--compact .indicator-title span { max-width: 25mm; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .indicator--compact th { padding: 1.5mm 2mm; font-size: 7pt; overflow-wrap: anywhere; }
        .indicator--compact th:first-child { width: 32%; }
        .indicator--compact td { padding: 1.5mm 2mm; font-size: 8pt; overflow-wrap: anywhere; }
        .indicator--compact .unit, .indicator--compact .year { font-size: 6.8pt; }
        .footer { display: flex; justify-content: space-between; margin-top: 5mm; padding-top: 2mm; border-top: .35mm solid var(--verde); color: #52645f; font-size: 7.5pt; }
        @media print { body { background: #fff; } }
    </style>
</head>
<body>
@foreach ($secciones as $seccion)
    <section class="sheet" style="--dimension: {{ $seccion['color'] }};">
        <header class="masthead">
            <div><h1>Comparativa municipal</h1><p>Último dato disponible por indicador</p></div>
            <img src="data:image/png;base64,{{ $logo }}" alt="Sistema Estatal de Información">
        </header>
        <div class="comparison-hero">
            <article class="municipio"><h2>{{ $municipio1->nombre }}</h2><p>{{ $municipio1->microrregion?->macrorregion?->nombre ?? 'Estado de Puebla' }} · {{ $municipio1->microrregion?->nombre ?? 'Sin región' }}</p><div class="stats"><div class="stat"><span>Población</span><b>{{ number_format($hero1['poblacionTotal'] ?? 0) }}</b></div><div class="stat"><span>Marginación</span><b>{{ $hero1['gradoMarginacion'] ?? 'N/D' }}</b></div><div class="stat"><span>Presupuesto</span><b>${{ number_format($hero1['presupuesto'] ?? 0, 0) }}</b></div></div></article>
            <div class="vs">VS</div>
            <article class="municipio municipio--right"><h2>{{ $municipio2->nombre }}</h2><p>{{ $municipio2->microrregion?->macrorregion?->nombre ?? 'Estado de Puebla' }} · {{ $municipio2->microrregion?->nombre ?? 'Sin región' }}</p><div class="stats"><div class="stat"><span>Población</span><b>{{ number_format($hero2['poblacionTotal'] ?? 0) }}</b></div><div class="stat"><span>Marginación</span><b>{{ $hero2['gradoMarginacion'] ?? 'N/D' }}</b></div><div class="stat"><span>Presupuesto</span><b>${{ number_format($hero2['presupuesto'] ?? 0, 0) }}</b></div></div></article>
        </div>
        <div class="section-head"><h2>{{ $seccion['nombre'] }}</h2><span>Sin gráficas</span></div>
        <div class="indicators-grid">
        @foreach ($seccion['indicadores'] as $indicador)
            @php
                $esCompacto = count($indicador['filas']) <= 2
                    && collect($indicador['filas'])->max(fn ($fila) => mb_strlen($fila['nombre'])) <= 42;
                $nombreMunicipio1 = $esCompacto ? \Illuminate\Support\Str::limit($municipio1->nombre, 16) : $municipio1->nombre;
                $nombreMunicipio2 = $esCompacto ? \Illuminate\Support\Str::limit($municipio2->nombre, 16) : $municipio2->nombre;
            @endphp
            <article class="indicator {{ $esCompacto ? 'indicator--compact' : 'indicator--wide' }}">
                <div class="indicator-title"><h3>{{ $indicador['nombre'] }}</h3><span>{{ $indicador['tematica'] }}</span></div>
                <table>
                    <thead><tr><th>Variable</th><th>{{ $nombreMunicipio1 }}</th><th>{{ $nombreMunicipio2 }}</th></tr></thead>
                    <tbody>@foreach ($indicador['filas'] as $fila)<tr><td>{{ $fila['nombre'] }}@if($fila['unidad'])<span class="unit">{{ $fila['unidad'] }}</span>@endif</td><td>{{ $fila['izquierda']['valor'] }}<span class="year">{{ $fila['izquierda']['anio'] ? 'Año '.$fila['izquierda']['anio'] : 'Sin año disponible' }}</span></td><td>{{ $fila['derecha']['valor'] }}<span class="year">{{ $fila['derecha']['anio'] ? 'Año '.$fila['derecha']['anio'] : 'Sin año disponible' }}</span></td></tr>@endforeach</tbody>
                </table>
            </article>
        @endforeach
        </div>
        <footer class="footer"><span>Secretaría de Planeación, Finanzas y Administración · Sistema Estatal de Información</span><span>Generado el {{ $generadoEn->format('d/m/Y') }}</span></footer>
    </section>
@endforeach
</body>
</html>
