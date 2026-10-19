@extends('layouts.admin')

@section('title', 'Panorama del portal')

@section('content')
<x-page-header
    title="Panorama del portal"
    subtitle="Contenido, cobertura y carga de datos del banco de indicadores"
    icon="fa-solid fa-chart-pie" />

<div class="container py-4">
    @php
        $anios = $serieAnual['anios'];
        $registros = $serieAnual['registros'];
        $maxDatos = $registros ? max($registros) : 0;
        $ultimoAnioTexto = $ultimoAnio ?: '—';
    @endphp

    <style>
        .dash-kpi { position: relative; display: flex; flex-direction: column; gap: .3rem; height: 100%;
                    padding: 1.1rem 1.25rem; background: #fff; border-radius: 12px; overflow: hidden; }
        .dash-kpi__label { color: var(--color8); opacity: .75; font-size: .74rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .dash-kpi__value { color: var(--color1); font-size: 1.85rem; font-weight: 700; line-height: 1.1; }
        .dash-kpi__meta { color: var(--color8); opacity: .8; font-size: .78rem; }
        .dash-kpi__meta strong { color: var(--color6); }
        .dash-kpi__icon { position: absolute; top: 1rem; right: 1.1rem; display: grid; width: 2.1rem; height: 2.1rem;
                          place-items: center; color: var(--color6); background: rgba(36, 98, 87, .1); border-radius: 8px; font-size: .85rem; }
        .dash-kpi--destacada .dash-kpi__icon { color: var(--color1); background: rgba(95, 27, 45, .1); }
        .dash-chart { width: 100%; height: 290px; }
        .dash-chart--sm { height: 240px; }
        .dash-panel-title { margin: 0; color: var(--color1); font-size: .95rem; font-weight: 700; }
        .dash-panel-lead { margin: .1rem 0 0; color: var(--color8); opacity: .7; font-size: .78rem; }
        .dash-metric { display: flex; align-items: center; justify-content: space-between; gap: 1rem;
                       padding: .6rem 0; border-bottom: 1px solid #eee; font-size: .85rem; }
        .dash-metric:last-child { border-bottom: 0; }
        .dash-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .6rem; border-radius: 999px;
                     font-size: .72rem; font-weight: 700; background: var(--arena); color: var(--color8); }
        .dash-chip--ok { background: rgba(36, 98, 87, .12); color: var(--color6); }
        .dash-chip--warn { background: rgba(199, 155, 102, .18); color: #8a6a35; }
        .dash-chip--alert { background: rgba(175, 23, 49, .1); color: var(--color3); }
        .dash-bar { height: .45rem; background: #eee; border-radius: 999px; overflow: hidden; }
        .dash-bar > span { display: block; height: 100%; border-radius: 999px; }
        .dash-quick { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .75rem; }
        .dash-quick a { display: flex; align-items: center; gap: .6rem; padding: .75rem 1rem; color: var(--color8);
                        text-decoration: none; background: #fff; border: 1px solid #eee; border-radius: 10px; font-size: .85rem; }
        .dash-quick a i { color: var(--color1); }
        .dash-quick a:hover { border-color: var(--color6); color: var(--color1); }
        .dash-chart-empty { display: grid; height: 240px; place-items: center; color: var(--color8); opacity: .7; font-size: .85rem; }
    </style>

    {{-- Tarjetas de indicadores --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2">
            <div class="card-panel dash-kpi dash-kpi--destacada h-100">
                <span class="dash-kpi__icon"><i class="fa-solid fa-database"></i></span>
                <span class="dash-kpi__label">Datos históricos</span>
                <span class="dash-kpi__value">{{ number_format($kpis['datos']) }}</span>
                <span class="dash-kpi__meta">máx. <strong>{{ number_format($maxDatos) }}</strong> en {{ $anios ? end($anios) : '—' }}</span>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card-panel dash-kpi h-100">
                <span class="dash-kpi__icon"><i class="fa-solid fa-chart-simple"></i></span>
                <span class="dash-kpi__label">Indicadores</span>
                <span class="dash-kpi__value">{{ number_format($kpis['indicadores']) }}</span>
                <span class="dash-kpi__meta"><strong>{{ $catalogoUltimoAnio['porcentaje'] }}%</strong> con datos en {{ $ultimoAnioTexto }}</span>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card-panel dash-kpi h-100">
                <span class="dash-kpi__icon"><i class="fa-solid fa-tags"></i></span>
                <span class="dash-kpi__label">Variables</span>
                <span class="dash-kpi__value">{{ number_format($kpis['variables']) }}</span>
                <span class="dash-kpi__meta"><strong>{{ $kpis['variablesKpi'] }}</strong> KPI · {{ $kpis['variablesConstruidas'] }} construidas</span>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card-panel dash-kpi h-100">
                <span class="dash-kpi__icon"><i class="fa-solid fa-city"></i></span>
                <span class="dash-kpi__label">Municipios</span>
                <span class="dash-kpi__value">{{ number_format($kpis['municipios']) }}</span>
                <span class="dash-kpi__meta"><strong>{{ $kpis['municipiosConDatos'] }}</strong> con datos en {{ $ultimoAnioTexto }}</span>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card-panel dash-kpi h-100">
                <span class="dash-kpi__icon"><i class="fa-solid fa-box-archive"></i></span>
                <span class="dash-kpi__label">Lotes de datos</span>
                <span class="dash-kpi__value">{{ number_format($kpis['lotes']) }}</span>
                <span class="dash-kpi__meta"><strong>{{ $kpis['lotesPorRevisar'] }}</strong> por revisar · {{ $kpis['lotesRechazados'] }} rechazados</span>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card-panel dash-kpi h-100">
                <span class="dash-kpi__icon"><i class="fa-solid fa-function"></i></span>
                <span class="dash-kpi__label">Fórmulas</span>
                <span class="dash-kpi__value">{{ number_format($kpis['formulas']) }}</span>
                <span class="dash-kpi__meta"><strong>{{ $kpis['formulasActivas'] }}</strong> activas · {{ $kpis['fichasActivas'] }}/{{ $kpis['fichas'] }} fichas activas</span>
            </div>
        </div>
    </div>

    {{-- Gráficas principales --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center gap-3 py-3 px-4">
                    <div>
                        <h5 class="dash-panel-title">Evolución de la carga histórica</h5>
                        <p class="dash-panel-lead">Registros por año y proporción de celdas con motivo de "sin dato"</p>
                    </div>
                    <span class="dash-chip">{{ number_format($kpis['datos']) }} registros</span>
                </div>
                <div class="card-body p-3">
                    <div id="chartSerieAnual" class="dash-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Cobertura del catálogo</h5>
                    <p class="dash-panel-lead">Indicadores con datos en {{ $ultimoAnioTexto }}</p>
                </div>
                <div class="card-body p-3">
                    <div id="chartCobertura" class="dash-chart dash-chart--sm"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Cobertura municipal en {{ $ultimoAnioTexto }}</h5>
                    <p class="dash-panel-lead">Promedio de municipios con registro en las variables de cada dimensión</p>
                </div>
                <div class="card-body p-3">
                    <div id="chartCoberturaDimension" class="dash-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Composición del catálogo por dimensión</h5>
                    <p class="dash-panel-lead">Indicadores y variables registradas</p>
                </div>
                <div class="card-body p-3">
                    <div id="chartCatalogoDimension" class="dash-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Lotes de datos</h5>
                    <p class="dash-panel-lead">Por estado del proceso de revisión</p>
                </div>
                <div class="card-body p-3">
                    <div id="chartLotes" class="dash-chart dash-chart--sm"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Fichas configuradas</h5>
                    <p class="dash-panel-lead">Por tipo de visualización</p>
                </div>
                <div class="card-body p-3">
                    <div id="chartFichas" class="dash-chart dash-chart--sm"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Indicadores con más datos</h5>
                    <p class="dash-panel-lead">Top {{ count($topIndicadores) }} por registros históricos</p>
                </div>
                <div class="card-body p-3">
                    <div id="chartTopIndicadores" class="dash-chart dash-chart--sm"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Salud, actividad y accesos --}}
    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center gap-3 py-3 px-4">
                    <div>
                        <h5 class="dash-panel-title">Salud del catálogo</h5>
                        <p class="dash-panel-lead">Pendientes de atención</p>
                    </div>
                    @can('salud-datos.ver')
                        <a href="{{ route('admin.salud-datos') }}" class="btn btn-sm btn-outline-secondary">Detalle</a>
                    @endcan
                </div>
                <div class="card-body p-3">
                    <div class="dash-metric">
                        <span>Indicadores sin variables</span>
                        <span class="dash-chip {{ $salud['indicadoresVacios'] > 0 ? 'dash-chip--alert' : 'dash-chip--ok' }}">
                            {{ $salud['indicadoresVacios'] }}
                        </span>
                    </div>
                    <div class="dash-metric">
                        <span>Variables huérfanas (sin indicador)</span>
                        <span class="dash-chip {{ $salud['variablesHuerfanas'] > 0 ? 'dash-chip--alert' : 'dash-chip--ok' }}">
                            {{ $salud['variablesHuerfanas'] }}
                        </span>
                    </div>
                    <div class="dash-metric">
                        <span>Indicadores desactualizados en {{ $ultimoAnioTexto }}</span>
                        <span class="dash-chip {{ $salud['desactualizados'] > 0 ? 'dash-chip--warn' : 'dash-chip--ok' }}">
                            {{ $salud['desactualizados'] }}
                        </span>
                    </div>
                    <div class="dash-metric">
                        <span>Indicadores sin polaridad</span>
                        <span class="dash-chip {{ $salud['sinPolaridad'] > 0 ? 'dash-chip--warn' : 'dash-chip--ok' }}">
                            {{ $salud['sinPolaridad'] }}
                        </span>
                    </div>

                    @if(count($motivosSinDato) > 0)
                        <h6 class="text-uppercase text-muted small fw-bold mt-4 mb-2">Motivos de "sin dato"</h6>
                        @foreach($motivosSinDato as $motivo)
                            <div class="dash-metric">
                                <span>{{ $motivo['nombre'] }}</span>
                                <strong class="text-dark">{{ number_format($motivo['total']) }}</strong>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Actividad reciente</h5>
                    <p class="dash-panel-lead">Últimos movimientos registrados</p>
                </div>
                <div class="card-body p-3">
                    @if(count($actividad) === 0)
                        <p class="text-muted small mb-0">Sin movimientos registrados.</p>
                    @else
                        @foreach($actividad as $movimiento)
                            <div class="dash-metric">
                                <div>
                                    <div class="text-dark">{{ $movimiento['evento'] }}</div>
                                    <div class="text-muted small">{{ $movimiento['usuario'] }} · {{ $movimiento['fecha'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card-panel h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="dash-panel-title">Accesos rápidos</h5>
                    <p class="dash-panel-lead">Atajos a las tareas frecuentes</p>
                </div>
                <div class="card-body p-3">
                    <div class="dash-quick">
                        @can('catalogos.ver')
                            <a href="{{ route('admin.catalogos.index') }}"><i class="fa-solid fa-book"></i>Catálogos</a>
                        @endcan
                        @can('catalogos.ver')
                            <a href="{{ route('admin.formulas.index') }}"><i class="fa-solid fa-function"></i>Fórmulas</a>
                        @endcan
                        @can('datos.ver')
                            <a href="{{ route('admin.datos.index') }}"><i class="fa-solid fa-table"></i>Datos</a>
                        @endcan
                        @can('datos.ver')
                            <a href="{{ route('admin.lotes-datos.index') }}"><i class="fa-solid fa-box-archive"></i>Lotes de datos</a>
                        @endcan
                        @can('configuracion-fichas.ver')
                            <a href="{{ route('admin.configuracion-fichas.index') }}"><i class="fa-solid fa-cog"></i>Config. fichas</a>
                        @endcan
                        @can('auditoria.ver')
                            <a href="{{ route('admin.auditoria.index') }}"><i class="fa-solid fa-clipboard-list"></i>Auditoría</a>
                        @endcan
                        @can('datos.importar')
                            <a href="{{ route('admin.import.index') }}"><i class="fa-solid fa-upload"></i>Importación</a>
                        @endcan
                        @can('diccionario.ver')
                            <a href="{{ route('admin.diccionario.index') }}"><i class="fa-solid fa-book-open"></i>Diccionario</a>
                        @endcan
                    </div>

                    @if($satisfaccion['total'] > 0)
                        <h6 class="text-uppercase text-muted small fw-bold mt-4 mb-3">Satisfacción reportada</h6>
                        <div class="d-flex align-items-center gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><i class="fa-regular fa-face-smile me-1"></i>Positivo</span>
                                    <strong class="text-dark">{{ $satisfaccion['feliz'] }}</strong>
                                </div>
                                <div class="dash-bar"><span style="width: {{ round($satisfaccion['feliz'] / $satisfaccion['total'] * 100) }}%; background: var(--color6);"></span></div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><i class="fa-regular fa-face-meh me-1"></i>Neutral</span>
                                    <strong class="text-dark">{{ $satisfaccion['neutral'] }}</strong>
                                </div>
                                <div class="dash-bar"><span style="width: {{ round($satisfaccion['neutral'] / $satisfaccion['total'] * 100) }}%; background: var(--color4);"></span></div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><i class="fa-regular fa-face-frown me-1"></i>Negativo</span>
                                    <strong class="text-dark">{{ $satisfaccion['triste'] }}</strong>
                                </div>
                                <div class="dash-bar"><span style="width: {{ round($satisfaccion['triste'] / $satisfaccion['total'] * 100) }}%; background: var(--color3);"></span></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const estilos = getComputedStyle(document.documentElement);
        const color = (nombre) => estilos.getPropertyValue(nombre).trim();

        const vino = color('--color1');
        const vinoClaro = color('--color3');
        const verde = color('--color6');
        const verdeClaro = color('--color7');
        const oro = color('--color4');
        const tinta = color('--color8');
        const gris = '#8b939a';
        const grisClaro = '#eeeeee';

        const paleta = [vino, verde, oro, verdeClaro, vinoClaro, color('--color5')];

        const fuenteBase = {
            color: tinta,
            fontFamily: '"Gilroy-Regular", "Avenir Next", "Helvetica Neue", Arial, sans-serif',
            fontSize: 12,
        };

        const tooltipBase = {
            backgroundColor: '#ffffff',
            borderColor: grisClaro,
            borderWidth: 1,
            textStyle: { color: tinta, fontSize: 12 },
            extraCssText: 'box-shadow: 0 6px 18px rgba(38,38,38,.12); border-radius: 10px;',
        };

        const graficas = [];

        function registrar(elemento, opcion) {
            if (!elemento) return null;
            const grafica = echarts.init(elemento);
            grafica.setOption(opcion);
            graficas.push(grafica);
            return grafica;
        }

        window.addEventListener('resize', function() {
            graficas.forEach(function(g) { g.resize(); });
        });

        // --- Evolución de la carga histórica ---
        const serieAnual = @json($serieAnual);

        registrar(document.getElementById('chartSerieAnual'), {
            textStyle: fuenteBase,
            tooltip: Object.assign({ trigger: 'axis' }, tooltipBase),
            legend: { top: 0, right: 0, itemWidth: 12, itemHeight: 8, textStyle: { color: gris } },
            grid: { left: 8, right: 8, top: 42, bottom: 4, containLabel: true },
            xAxis: {
                type: 'category',
                data: serieAnual.anios,
                axisLine: { lineStyle: { color: grisClaro } },
                axisLabel: { color: gris },
            },
            yAxis: [
                {
                    type: 'value',
                    name: 'Registros',
                    nameTextStyle: { color: gris, align: 'right' },
                    splitLine: { lineStyle: { color: '#f4f4f4' } },
                    axisLabel: { color: gris, formatter: function(v) { return v >= 1000 ? (v / 1000) + 'k' : v; } },
                },
                {
                    type: 'value',
                    name: '% sin dato',
                    nameTextStyle: { color: gris, align: 'left' },
                    max: 100,
                    splitLine: { show: false },
                    axisLabel: { color: gris, formatter: '{value}%' },
                },
            ],
            series: [
                {
                    name: 'Registros',
                    type: 'bar',
                    data: serieAnual.registros,
                    barMaxWidth: 34,
                    itemStyle: { color: vino, borderRadius: [4, 4, 0, 0] },
                },
                {
                    name: '% con motivo de sin dato',
                    type: 'line',
                    yAxisIndex: 1,
                    smooth: true,
                    symbolSize: 6,
                    data: serieAnual.porcentaje,
                    lineStyle: { color: oro, width: 2 },
                    itemStyle: { color: oro },
                },
            ],
        });

        // --- Cobertura del catálogo ---
        registrar(document.getElementById('chartCobertura'), {
            textStyle: fuenteBase,
            tooltip: Object.assign({ trigger: 'item' }, tooltipBase),
            legend: { bottom: 0, itemWidth: 12, itemHeight: 8, textStyle: { color: gris } },
            series: [
                {
                    name: 'Indicadores',
                    type: 'pie',
                    radius: ['58%', '80%'],
                    center: ['50%', '44%'],
                    avoidLabelOverlap: true,
                    itemStyle: { borderColor: '#fff', borderWidth: 2 },
                    label: { show: true, formatter: '{b}\n{c}', color: tinta, fontSize: 12 },
                    data: [
                        { value: {{ $catalogoUltimoAnio['conDatos'] }}, name: 'Con datos', itemStyle: { color: verde } },
                        { value: {{ $catalogoUltimoAnio['sinDatos'] }}, name: 'Sin datos', itemStyle: { color: grisClaro } },
                    ],
                },
            ],
        });

        // --- Cobertura municipal por dimensión ---
        const dimensiones = @json($porDimension);

        registrar(document.getElementById('chartCoberturaDimension'), {
            textStyle: fuenteBase,
            tooltip: Object.assign({ trigger: 'axis', valueFormatter: function(v) { return v + '%'; } }, tooltipBase),
            grid: { left: 8, right: 24, top: 12, bottom: 4, containLabel: true },
            xAxis: {
                type: 'value',
                max: 100,
                name: '% cobertura',
                nameTextStyle: { color: gris },
                splitLine: { lineStyle: { color: '#f4f4f4' } },
                axisLabel: { color: gris, formatter: '{value}%' },
            },
            yAxis: {
                type: 'category',
                data: dimensiones.map(function(d) { return d.nombre; }),
                axisLine: { show: false },
                axisTick: { show: false },
                axisLabel: { color: tinta, width: 140, overflow: 'truncate' },
            },
            series: [
                {
                    name: 'Cobertura municipal promedio',
                    type: 'bar',
                    data: dimensiones.map(function(d) { return d.cobertura; }),
                    barMaxWidth: 18,
                    itemStyle: { color: verde, borderRadius: [0, 4, 4, 0] },
                    label: { show: true, position: 'right', color: gris, formatter: '{c}%' },
                },
            ],
        });

        // --- Composición del catálogo ---
        registrar(document.getElementById('chartCatalogoDimension'), {
            textStyle: fuenteBase,
            tooltip: Object.assign({ trigger: 'axis', axisPointer: { type: 'shadow' } }, tooltipBase),
            legend: { top: 0, right: 0, itemWidth: 12, itemHeight: 8, textStyle: { color: gris } },
            grid: { left: 8, right: 16, top: 42, bottom: 4, containLabel: true },
            xAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: '#f4f4f4' } },
                axisLabel: { color: gris },
            },
            yAxis: {
                type: 'category',
                data: dimensiones.map(function(d) { return d.nombre; }),
                axisLine: { show: false },
                axisTick: { show: false },
                axisLabel: { color: tinta, width: 140, overflow: 'truncate' },
            },
            series: [
                {
                    name: 'Indicadores',
                    type: 'bar',
                    stack: 'catalogo',
                    data: dimensiones.map(function(d) { return d.indicadores; }),
                    barMaxWidth: 18,
                    itemStyle: { color: vino },
                },
                {
                    name: 'Variables',
                    type: 'bar',
                    stack: 'catalogo',
                    data: dimensiones.map(function(d) { return d.variables; }),
                    barMaxWidth: 18,
                    itemStyle: { color: oro, borderRadius: [0, 4, 4, 0] },
                },
            ],
        });

        // --- Lotes de datos ---
        const lotesEstado = @json($lotes['porEstado']);
        const estadosLote = [
            { clave: 'aprobado', nombre: 'Aprobados', color: verde },
            { clave: 'en_revision', nombre: 'En revisión', color: oro },
            { clave: 'borrador', nombre: 'Borrador', color: gris },
            { clave: 'rechazado', nombre: 'Rechazados', color: vinoClaro },
        ];

        registrar(document.getElementById('chartLotes'), {
            textStyle: fuenteBase,
            tooltip: Object.assign({ trigger: 'item' }, tooltipBase),
            legend: { bottom: 0, itemWidth: 12, itemHeight: 8, textStyle: { color: gris } },
            series: [
                {
                    name: 'Lotes',
                    type: 'pie',
                    radius: ['58%', '80%'],
                    center: ['50%', '44%'],
                    itemStyle: { borderColor: '#fff', borderWidth: 2 },
                    label: { show: true, formatter: '{b}\n{c}', color: tinta },
                    data: estadosLote
                        .filter(function(e) { return lotesEstado[e.clave]; })
                        .map(function(e) { return { value: lotesEstado[e.clave], name: e.nombre, itemStyle: { color: e.color } }; }),
                },
            ],
        });

        // --- Fichas configuradas ---
        const fichas = @json($fichas['porTipo']);

        registrar(document.getElementById('chartFichas'), {
            textStyle: fuenteBase,
            tooltip: Object.assign({ trigger: 'item' }, tooltipBase),
            legend: { bottom: 0, itemWidth: 12, itemHeight: 8, textStyle: { color: gris } },
            series: [
                {
                    name: 'Fichas',
                    type: 'pie',
                    radius: ['58%', '80%'],
                    center: ['50%', '44%'],
                    itemStyle: { borderColor: '#fff', borderWidth: 2 },
                    label: { show: true, formatter: '{b}\n{c}', color: tinta },
                    data: Object.keys(fichas).map(function(tipo, indice) {
                        return {
                            value: fichas[tipo],
                            name: tipo ? tipo.charAt(0).toUpperCase() + tipo.slice(1) : 'Sin tipo',
                            itemStyle: { color: paleta[indice % paleta.length] },
                        };
                    }),
                },
            ],
        });

        // --- Top indicadores ---
        const top = @json($topIndicadores);

        registrar(document.getElementById('chartTopIndicadores'), {
            textStyle: fuenteBase,
            tooltip: Object.assign({ trigger: 'axis', axisPointer: { type: 'shadow' } }, tooltipBase),
            grid: { left: 8, right: 24, top: 8, bottom: 4, containLabel: true },
            xAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: '#f4f4f4' } },
                axisLabel: { color: gris, formatter: function(v) { return v >= 1000 ? (v / 1000) + 'k' : v; } },
            },
            yAxis: {
                type: 'category',
                inverse: true,
                data: top.map(function(i) { return i.nombre; }),
                axisLine: { show: false },
                axisTick: { show: false },
                axisLabel: { color: tinta, width: 150, overflow: 'truncate' },
            },
            series: [
                {
                    name: 'Registros',
                    type: 'bar',
                    data: top.map(function(i) { return i.datos; }),
                    barMaxWidth: 16,
                    itemStyle: { color: vino, borderRadius: [0, 4, 4, 0] },
                    label: { show: true, position: 'right', color: gris, formatter: function(p) {
                        return p.value >= 1000 ? (p.value / 1000).toFixed(1) + 'k' : p.value;
                    } },
                },
            ],
        });
    });
</script>
@endpush
