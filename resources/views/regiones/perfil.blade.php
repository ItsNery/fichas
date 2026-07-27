@extends('layouts.plantilla')

@section('title', $esEstatal ? 'Perfil Estatal de ' . $region->nombre : 'Perfil de ' . $tipoRegion . ' de ' . $region->nombre)

@section('content')
{{-- 1. HERO SECTION --}}
<section class="hero-ficha hero-ficha--{{ Str::slug($tipoRegion) }}" style="background-image: url('{{ $region->banner_image_url ?? asset(config('regionalizacion.fallback_hero')) }}')">
    <div class="hero-ficha__capa-gradiente"></div>

    <div class="container hero-ficha__contenedor">
        <div class="row align-items-center">
            <div class="col-lg-7 text-white">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-white-50">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('ficha-municipal.index') }}" class="text-white-50">Directorio</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">{{ $region->nombre }}</li>
                    </ol>
                </nav>

                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap hero-ficha__acciones">
                    <span class="badge bg-gold px-3 py-2 text-uppercase hero-ficha__badge m-0">PERFIL {{ strtoupper($tipoRegion) }}</span>
                    @php
                        $pdfRoute = match ($tipoRegion) {
                            'Estatal' => route('regiones.estatal.pdf'),
                            'Macrorregión' => route('regiones.macro.pdf', $region->slug),
                            default => route('regiones.micro.pdf', $region->slug),
                        };
                        $excelRoute = match ($tipoRegion) {
                            'Estatal' => route('regiones.estatal.excel'),
                            'Macrorregión' => route('regiones.macro.excel', $region->slug),
                            default => route('regiones.micro.excel', $region->slug),
                        };
                    @endphp
                    <a href="{{ $pdfRoute }}" class="btn btn-outline-light btn-sm fw-bold px-3 py-1 rounded-pill" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ $excelRoute }}" class="btn btn-outline-light btn-sm fw-bold px-3 py-1 rounded-pill" target="_blank">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                </div>
                <h1 class="display-1 fw-bold mb-2">{{ $region->nombre }}</h1>
                <p class="h3 fw-light mb-5 opacity-75">
                    {{ $tipoRegion }} | Estado de Puebla
                </p>

                <div class="d-flex gap-5 mt-5 flex-column flex-md-row">
                    <div class="hero-info-item">
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">{{ $esEstatal ? 'Municipios con información' : 'Municipios que la conforman' }}</small>
                        <span class="h5 fw-bold">{{ $municipios->count() }} municipios</span>
                    </div>
                    <div class="hero-info-item">
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Población total</small>
                        <span class="h5 fw-bold d-block">{{ number_format($poblacionTotal) }} hab.</span>
                        <small class="d-block text-white-50">Año {{ $poblacionAnio ?? 'N/D' }} · {{ $poblacionCobertura['con_dato'] ?? 0 }}/{{ $poblacionCobertura['total'] ?? 0 }} municipios</small>
                    </div>
                    <div class="hero-info-item">
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">{{ $esEstatal ? 'Superficie municipal total' : 'Superficie representada' }}</small>
                        <span class="h5 fw-bold d-block">{{ number_format($superficieTotal, 2) }} km²</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 bg-transparent text-white mt-4">
                    <div class="card-body">
                        @if($esEstatal)
                            <h5 class="fw-bold text-gold mb-3">Estructura territorial</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="border border-white border-opacity-25 rounded-3 p-3 bg-white bg-opacity-10">
                                        <span class="d-block h3 mb-1">{{ $alcanceTerritorial['macrorregiones_oficiales'] }}</span>
                                        <small class="text-white-50 text-uppercase">Macrorregiones oficiales</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border border-white border-opacity-25 rounded-3 p-3 bg-white bg-opacity-10">
                                        <span class="d-block h3 mb-1">{{ $alcanceTerritorial['microrregiones_oficiales'] }}</span>
                                        <small class="text-white-50 text-uppercase">Microrregiones oficiales</small>
                                    </div>
                                </div>
                            </div>
                            <small class="d-block text-white-50 mt-3">La información estadística se representa con municipios completos.</small>
                            <a href="#territorio-estatal" class="btn btn-outline-light btn-sm rounded-pill mt-3">
                                <i class="fas fa-map me-1"></i> Explorar estructura territorial
                            </a>
                        @else
                            <h5 class="fw-bold text-gold mb-3">Municipios:</h5>
                            <div class="d-flex flex-wrap gap-2 hero-ficha__region-list" style="max-height: 200px; overflow-y: auto; scrollbar-width: thin;">
                                @foreach($municipios as $muni)
                                    <a href="{{ route('ficha-municipal.perfil', $muni->slug) }}" class="badge bg-white bg-opacity-10 text-white text-decoration-none border border-white border-opacity-25" style="transition: all 0.2s;" title="Ver ficha de {{ $muni->nombre }}">
                                        {{ $muni->nombre }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container mt-4" aria-labelledby="alcance-territorial-titulo">
    <aside class="territorial-scope-note">
        <div class="d-flex align-items-start gap-3">
            <i class="fas fa-circle-info territorial-scope-note__icon" aria-hidden="true"></i>
            <div>
                <h2 id="alcance-territorial-titulo" class="territorial-scope-note__title">Alcance territorial de esta ficha</h2>
                @if($esEstatal)
                <p class="mb-2">La información estadística se integra a nivel municipal. Algunos municipios intersectan más de una microrregión oficial, pero sus datos no pueden dividirse por porciones territoriales. Para evitar duplicidades, cada municipio se incorpora una sola vez conforme a la asignación municipal disponible.</p>
                <p class="mb-2">Por ello, los conteos de información representada no deben interpretarse como el total territorial oficial de microrregiones.</p>
                @else
                <p class="mb-2">Esta ficha representa municipios completos. No desagrega información para porciones territoriales de municipios que intersectan más de una microrregión oficial.</p>
                @endif
                <a href="{{ $alcanceTerritorial['fuente_url'] }}" target="_blank" rel="noopener noreferrer" class="territorial-scope-note__link">
                    Consultar regionalización oficial vigente <i class="fas fa-arrow-up-right-from-square ms-1" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </aside>
</section>

@if($esEstatal)
<section id="territorio-estatal" class="container mt-5">
    <div class="dimension-header shadow-sm">
        <div>
            <h2 class="display-5 fw-bold mb-1">Estructura territorial</h2>
            <p class="text-white mb-0">{{ $alcanceTerritorial['macrorregiones_oficiales'] }} macrorregiones oficiales; las métricas de las tarjetas usan municipios completos con información disponible.</p>
        </div>
    </div>
    <div class="row g-4">
        @foreach($resumenTerritorial as $macro)
        <div class="col-md-6 col-xl-4">
            <div class="perfil-tarjeta h-100">
                <div class="perfil-tarjeta__body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <h3 class="perfil-tarjeta__titulo mb-0">{{ $macro['nombre'] }}</h3>
                        @if($macro['slug'])
                        <a href="{{ route('regiones.macro.perfil', $macro['slug']) }}" class="btn btn-sm btn-outline-secondary rounded-pill" title="Abrir perfil de {{ $macro['nombre'] }}">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>
                        @endif
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <strong class="d-block text-vino">{{ $macro['municipios'] }}</strong>
                            <small class="text-muted">Municipios</small>
                        </div>
                        <div class="col-4">
                            <strong class="d-block text-vino">{{ $macro['microrregiones_representadas'] }}</strong>
                            <small class="text-muted">Microrregiones con información</small>
                        </div>
                        <div class="col-4">
                            <strong class="d-block text-vino">{{ number_format($macro['poblacion']) }}</strong>
                            <small class="text-muted">Habitantes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- 2. NAV HORIZONTAL PARA DIMENSIONES --}}
<div class="sticky-nav">
    <div class="container sticky-nav__contenedor">
        <ul class="nav justify-content-center sticky-nav__list">
            @foreach($perfil as $seccion => $items)
            @if($seccion != 'general')
            <li class="nav-item sticky-nav__item">
                <a href="#section-{{ Str::slug($seccion) }}" class="nav-link sticky-nav__link">{{ ucwords(str_replace('_', ' ', $seccion)) }}</a>
            </li>
            @endif
            @endforeach
        </ul>
    </div>
</div>

{{-- 3. CONTENIDO EDITORIAL --}}
<div class="container mt-5">
    @php
        $aggregationLabels = [
            'sum' => 'Total acumulado',
            'average' => 'Promedio municipal',
            'ratio' => 'Razón hombres/mujeres',
            'mode' => 'Valor más frecuente',
        ];
    @endphp
    @foreach($perfil as $seccion => $items)
    @if($seccion != 'general')
    <section id="section-{{ Str::slug($seccion) }}" class="section-perfil mb-5 pb-5">
        <div class="dimension-header shadow-sm">
            <h2 class="dimension-header__title display-4 fw-bold mb-0">{{ str_replace('_', ' ', $seccion) }}</h2>
        </div>

        <div class="row g-4 align-items-stretch">
            @foreach($items as $item)
            @php
            $gridClass = 'col-12'; // Para perfiles regionales, siempre usamos col-12 por el ranking horizontal
            @endphp

            <div class="{{ $gridClass }}">
                <div class="perfil-tarjeta">
                    <div class="perfil-tarjeta__body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="perfil-tarjeta__titulo mb-0">
                                {{ $item['config']->titulo_reporte ?? $item['config']->indicador->nombre_amigable }}
                                @if(isset($item['datos']['anio']) && $item['datos']['anio'])
                                <span class="perfil-tarjeta__anio-badge">
                                    {{ $item['datos']['anio'] }}
                                </span>
                                @endif
                            </h3>
                            <div class="d-flex align-items-center gap-2">
                                @if(isset($item['config']->indicador))
                                    <a href="{{ route('banco-indicadores.index', $tipoRegion === 'Estatal' ? ['indicador_id' => $item['config']->indicador->id, 'nivel' => 'municipio', 'municipio_ids' => 'estatal'] : ['indicador_id' => $item['config']->indicador->id, 'nivel' => $tipoRegion === 'Macrorregión' ? 'macrorregion' : 'microrregion', 'region_id' => $region->id]) }}"
                                   class="perfil-tarjeta__info-icon text-muted"
                                   title="Ver gráfico en Banco de Indicadores"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="top"
                                   target="_blank"
                                   style="font-size: 1rem; transition: color 0.2s;"
                                   onmouseover="this.style.color='#861e34'"
                                   onmouseout="this.style.color=''">
                                    <i class="fa-solid fa-chart-column"></i>
                                </a>
                                @endif
                                @if(isset($item['datos']['metodo_calculo']) || isset($item['datos']['fuente']) || isset($item['datos']['correlacion']))
                                 <button type="button" class="btn btn-link p-0 border-0 fa-solid fa-circle-info info-tooltip-trigger perfil-tarjeta__info-icon mb-0"
                                     aria-label="Ver metodología y fuente"
                                     data-bs-toggle="popover"
                                    data-bs-trigger="hover focus"
                                    title="Metodología y fuente"
                                    data-bs-content="<strong>Método:</strong> {{ $item['datos']['metodo_calculo'] ?? 'No especificado' }}@if($item['config']->tipo_visualizacion === 'scatter' && !empty($item['datos']['variables']))<br><strong>Medianas regionales:</strong>@foreach($item['datos']['variables'] as $variableResumen)<br>{{ $variableResumen['nombre'] }}: <strong>{{ number_format($variableResumen['valor'], 2) }} {{ $variableResumen['unidad'] }}</strong>@endforeach @endif @if(isset($item['datos']['correlacion_lectura']))<br><strong>Asociación regional:</strong> {{ $item['datos']['correlacion_lectura'] }} <small>No implica causalidad.</small>@endif<br><strong>Fuente:</strong> {{ $item['datos']['fuente'] ?? 'No especificada' }}"
                                     data-bs-html="true"></button>
                                @endif
                            </div>
                        </div>

                        @if($item['config']->subtitulo_reporte)
                        <p class="text-muted small mb-3">{{ $item['config']->subtitulo_reporte }}</p>
                        @endif

                        <div class="row mb-4 align-items-center">
                            <div class="col-md-4 text-center border-end">
                                <h5 class="text-uppercase small fw-bold text-muted mb-2">
                                    {{ $item['config']->tipo_visualizacion === 'scatter' ? 'Municipios analizados' : ($esEstatal ? 'Valor estatal' : 'Valor regional') }}
                                </h5>
                                <h4 class="perfil-tarjeta__kpi-value text-vino" style="font-size: 2.5rem;">
                                    {{ $item['datos']['valor_actual'] ?? $item['datos']['total'] ?? 0 }}
                                </h4>
                                @if(isset($item['datos']['variables'][0]['unidad']))
                                <p class="perfil-tarjeta__kpi-unit">{{ $item['datos']['variables'][0]['unidad'] }}</p>
                                @endif
                                @if(isset($item['datos']['coverage']))
                                <p class="text-muted small mb-0">
                                    {{ $aggregationLabels[$item['datos']['aggregation_method'] ?? ''] ?? 'Valor' }} |
                                    {{ $item['datos']['coverage']['municipios_con_dato'] ?? 0 }}/{{ $item['datos']['coverage']['municipios_total'] ?? 0 }} municipios con dato
                                </p>
                                @endif
                            </div>
                            <div class="col-md-8">
                                @if($item['narrativa'])
                                <div class="perfil-tarjeta__narrativa-wrapper h-100 d-flex align-items-center mb-0 mt-0 px-3">
                                    <p class="perfil-tarjeta__narrativa-texto mb-0" style="font-size: 1.1rem;">{!! $item['narrativa'] !!}</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        @if(($item['config']->tipo_visualizacion === 'piramide' && !empty($item['datos']['series'])) || !empty($item['datos']['echarts']['series']))
                        @if($esEstatal && ($item['datos']['ranking_limited'] ?? false))
                        <p class="text-muted small mb-2"><i class="fas fa-filter me-1"></i>Vista resumida: 5 municipios con mayor valor y 5 con menor valor. El ranking completo está disponible debajo.</p>
                        @endif
                        <div class="perfil-tarjeta__chart-wrapper perfil-tarjeta__chart-wrapper--full">
                            <div class="perfil-tarjeta__skeleton" id="skeleton-{{ $item['config']->id }}">
                                <div class="spinner-border perfil-tarjeta__spinner" role="status">
                                    <span class="visually-hidden">Cargando gráfico...</span>
                                </div>
                            </div>
                            <div class="perfil-tarjeta__chart-box perfil-tarjeta__chart-box--full lazy-chart {{ $item['config']->tipo_visualizacion === 'piramide' ? 'perfil-chart--piramide' : (in_array($item['config']->tipo_visualizacion, ['scatter', 'mapa']) ? 'perfil-chart--wide' : 'perfil-chart--standard') }}" id="chart-{{ $item['config']->id }}" data-chart-id="{{ $item['config']->id }}"></div>
                        </div>
                        @endif

                        @if($esEstatal && !empty($item['datos']['ranking']) && ($item['datos']['ranking_limited'] ?? false))
                        <details class="mt-3 state-ranking-details">
                            <summary class="btn btn-sm btn-outline-secondary rounded-pill">Ver ranking completo ({{ $item['datos']['ranking_total'] }})</summary>
                            <div class="table-responsive mt-3">
                                <label class="visually-hidden" for="state-ranking-search-{{ $item['config']->id }}">Buscar municipio en el ranking</label>
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-white"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input id="state-ranking-search-{{ $item['config']->id }}" class="form-control state-ranking-search" type="search" placeholder="Buscar municipio..." autocomplete="off" data-ranking-target="state-ranking-table-{{ $item['config']->id }}">
                                </div>
                                <p class="text-muted small mb-2" aria-live="polite" data-ranking-count="state-ranking-table-{{ $item['config']->id }}">{{ $item['datos']['ranking_total'] }} de {{ $item['datos']['ranking_total'] }} municipios</p>
                                <div class="state-ranking-scroll">
                                <table id="state-ranking-table-{{ $item['config']->id }}" class="table table-sm align-middle mb-0">
                                    <caption class="visually-hidden">Ranking completo de {{ $item['config']->indicador->nombre_amigable }} en el Estado de Puebla</caption>
                                    <thead>
                                        <tr><th scope="col">#</th><th scope="col">Municipio</th><th scope="col" class="text-end">Valor</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($item['datos']['ranking'] as $posicion => $ranking)
                                        <tr data-ranking-name="{{ Str::ascii(mb_strtolower($ranking['name'], 'UTF-8')) }}">
                                            <td>{{ $posicion + 1 }}</td>
                                            <td><a href="{{ route('ficha-municipal.perfil', $municipios->firstWhere('id', $ranking['id'])?->slug ?? '#') }}">{{ $ranking['name'] }}</a></td>
                                            <td class="text-end">{{ number_format($ranking['orderValue'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </details>
                        @endif

                    </div>
                    @if(isset($item['datos']['fuente']))
                    <div class="perfil-tarjeta__footer">
                        <p class="fuente-texto">
                            Fuente: <strong>{{ $item['datos']['fuente'] }}</strong>
                        </p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif
    @endforeach
</div>

@endsection

@section('jss')
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
<script src="{{ asset('js/perfil.js') }}"></script>
@php
    $perfilCharts = $perfil;
    if ($esEstatal) {
        foreach ($perfilCharts as $section => $items) {
            foreach ($items as $index => $item) {
                if (isset($item['datos']['ranking_display'])) {
                    $perfilCharts[$section][$index]['datos']['ranking'] = $item['datos']['ranking_display'];
                }
            }
        }
    }
@endphp
<script>
    window.FichaConfig = {
        municipioNombre: "Regional",
        geojsonUrl: "{{ asset('geojson/municipios_puebla_slim.geojson') }}",
        perfilData: @json($perfilCharts)
    };
</script>
@endsection
