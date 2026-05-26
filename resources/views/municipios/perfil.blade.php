@extends('layouts.plantilla')

@section('title', 'Ficha del municipio de ' . $municipio->nombre)

@section('css')
<link rel="stylesheet" href="{{ asset('css/perfil_editorial.css') }}">
@endsection

@section('content')
{{-- 1. HERO SECTION (v2) --}}
<section class="hero-ficha" style="background-image: url('{{ $municipio->banner_image_url ?? 'https://picsum.photos/id/1015/1920/1080?blur=2' }}')">
    <div class="hero-ficha__capa-gradiente"></div>

    <div class="container hero-ficha__contenedor">
        <div class="row align-items-center">
            <div class="col-lg-7 text-white">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-white-50">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/ficha-municipal') }}" class="text-white-50">Directorio</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">{{ $municipio->nombre }}</li>
                    </ol>
                </nav>

                <span class="badge bg-gold mb-3 px-3 py-2 text-uppercase" style="background-color: var(--color4);">FICHA MUNICIPAL</span>
                <h1 class="display-1 fw-bold mb-2">{{ $municipio->nombre }}</h1>
                <p class="h3 fw-light mb-5 opacity-75">
                    {{ $municipio->microrregion->macrorregion->nombre ?? 'Estado de Puebla' }} | {{ $municipio->microrregion->nombre ?? 'Región' }}
                </p>

                <div class="d-flex gap-5 mt-5">
                    <div class="hero-info-item">
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Cabecera</small>
                        <span class="h5 fw-bold">{{ $municipio->cabecera ?? $municipio->nombre }}</span>
                    </div>
                    <div class="hero-info-item border-start ps-4">
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Presidente Municipal</small>
                        <span class="h5 fw-bold d-block">{{ $municipio->presidente_municipal ?? 'N/D' }}</span>
                        <small class="text-white-50">{{ $municipio->periodo_gobierno }}</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-ficha__mapa-contenedor">
                    <div id="hero-map" class="hero-ficha__mapa-visual" data-cvegeo="{{ $municipio->cvegeo }}"></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 2. ICON BAR (Hero Stats) --}}
<section class="barra-indicadores shadow-lg">
    <div class="container">
        <div class="row text-center justify-content-center flex-wrap g-4">
            <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                <i class="fas fa-users barra-indicadores__icono"></i>
                <span class="barra-indicadores__valor">{{ number_format($poblacionTotal) }}</span>
                <span class="barra-indicadores__etiqueta">Población Total</span>
            </div>
            <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                <i class="fas fa-briefcase barra-indicadores__icono"></i>
                <span class="barra-indicadores__valor">{{ number_format($pea) }}</span>
                <span class="barra-indicadores__etiqueta">Población Activa (PEA)</span>
            </div>
            <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                <i class="fas fa-hand-holding-usd barra-indicadores__icono"></i>
                <span class="barra-indicadores__valor">{{ $porcentajePobreza }}</span>
                <span class="barra-indicadores__etiqueta">Población en Pobreza</span>
            </div>
            <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                <i class="fas fa-dollar-sign barra-indicadores__icono"></i>
                <span class="barra-indicadores__valor">${{ number_format($presupuesto, 2) }}</span>
                <span class="barra-indicadores__etiqueta">Presupuesto {{ $ultimoAnioPres }}</span>
            </div>
            <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                <i class="fas fa-chart-line barra-indicadores__icono"></i>
                <span class="barra-indicadores__valor">{{ $gradoMarginacion }}</span>
                <span class="barra-indicadores__etiqueta">Marginación</span>
            </div>
            <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                <i class="fas fa-expand-arrows-alt barra-indicadores__icono"></i>
                <span class="barra-indicadores__valor">{{ number_format($superficieKm2, 2) }} km²</span>
                <span class="barra-indicadores__etiqueta">Superficie Territorial</span>
            </div>
        </div>
    </div>
</section>

{{-- 3. NAV HORIZONTAL PARA DIMENSIONES --}}
<div class="sticky-nav-horizontal">
    <div class="container">
        <ul class="nav justify-content-center">
            @foreach($perfil as $seccion => $items)
            @if($seccion != 'general')
            <li class="nav-item">
                <a href="#section-{{ Str::slug($seccion) }}" class="nav-link nav-premium-link">{{ ucwords(str_replace('_', ' ', $seccion)) }}</a>
            </li>
            @endif
            @endforeach
        </ul>
    </div>
</div>

{{-- 4. CONTENIDO EDITORIAL --}}
<div class="container mt-5">
    @foreach($perfil as $seccion => $items)
    @if($seccion != 'general')
    <section id="section-{{ Str::slug($seccion) }}" class="section-perfil mb-5 pb-5">
        <div class="dimension-header shadow-sm">
            <h2 class="display-4 fw-bold mb-0">{{ ucwords(str_replace('_', ' ', $seccion)) }}</h2>
        </div>

        <div class="row g-4 align-items-stretch">
            @foreach($items as $item)
            @php
            $gridClass = $item['config']->clase_grid ?: 'col-12';
            $isKpi = $item['config']->tipo_visualizacion === 'kpi';
            @endphp

            <div class="{{ $gridClass }}">
                <div class="card h-100 border-0 shadow-sm overflow-hidden" style="background: white; border-radius: 12px;">
                    <div class="card-body p-4 d-flex flex-column" style="position: relative; z-index: 1;">
                        {{-- Watermark Icon --}}
                        @php $cardIcon = $item['datos']['icono_actual'] ?? $item['config']->icono; @endphp
                        @if($cardIcon)
                        <div class="card-watermark">
                            <i class="{{ $cardIcon }}"></i>
                        </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="h4 fw-bold mb-0" style="color: var(--color1); position: relative; z-index: 2;">
                                {{ $item['config']->titulo_reporte ?? $item['config']->indicador->nombre_amigable }}
                                @if(isset($item['datos']['anio']) && $item['datos']['anio'])
                                <span class="badge bg-light text-secondary border ms-2" style="font-size: 0.7rem; vertical-align: middle;">
                                    {{ $item['datos']['anio'] }}
                                </span>
                                @endif
                            </h3>
                            @if(isset($item['datos']['metodo_calculo']) || isset($item['datos']['fuente']))
                            <i class="fa-solid fa-circle-info info-tooltip-trigger"
                                data-bs-toggle="popover"
                                data-bs-trigger="hover focus"
                                title="Metodología y Fuente"
                                data-bs-content="<strong>Método:</strong> {{ $item['datos']['metodo_calculo'] ?? 'No especificado' }}<br><strong>Fuente:</strong> {{ $item['datos']['fuente'] ?? 'No especificada' }}"
                                data-bs-html="true"
                                style="position: relative; z-index: 2;"></i>
                            @endif
                        </div>

                        {{-- Insight Badge --}}
                        @if(isset($item['datos']['ranking']) && isset($item['datos']['polaridad']) && !is_array($item['datos']['ranking']))
                        @php
                        $rank = $item['datos']['ranking'];
                        $pol = $item['datos']['polaridad'];
                        $badgeClass = 'insight-badge--info';
                        $badgeIcon = 'fa-circle-check';
                        $badgeText = 'Dato Relevante';

                        if($pol == 'asendente') {
                        if($rank <= 20) { $badgeClass='insight-badge--success' ; $badgeIcon='fa-award' ; $badgeText='Líder Estatal' ; }
                            elseif($rank>= 180) { $badgeClass = 'insight-badge--danger'; $badgeIcon = 'fa-triangle-exclamation'; $badgeText = 'Área de Oportunidad'; }
                            } elseif($pol == 'descendente') {
                            if($rank <= 20) { $badgeClass='insight-badge--danger' ; $badgeIcon='fa-triangle-exclamation' ; $badgeText='Alerta de Prioridad' ; }
                                elseif($rank>= 180) { $badgeClass = 'insight-badge--success'; $badgeIcon = 'fa-award'; $badgeText = 'Desempeño Destacado'; }
                                }
                                @endphp
                                <div class="mb-3" style="position: relative; z-index: 2;">
                                    <span class="insight-badge {{ $badgeClass }}">
                                        <i class="fa-solid {{ $badgeIcon }}"></i> {{ $badgeText }} (#{{ $rank }})
                                    </span>
                                </div>
                                @endif

                                @if($item['narrativa'])
                                <div class="narrativa-box mb-4" style="position: relative; z-index: 2;">
                                    <p class="text-muted mb-0" style="font-size: 0.95rem;">{!! $item['narrativa'] !!}</p>
                                </div>
                                @endif

                                @if($isKpi)
                                <div class="kpi-card text-center mt-auto p-3 bg-light rounded" style="position: relative; z-index: 2; background: rgba(248, 249, 250, 0.7) !important; backdrop-filter: blur(2px);">
                                    <div class="d-flex align-items-center justify-content-center gap-3">
                                        <h4 class="display-4 fw-bold text-primary mb-0">
                                            {{ $item['datos']['valor_actual'] ?? $item['datos']['total'] ?? 0 }}
                                        </h4>

                                    </div>

                                    @if(isset($item['datos']['variables'][0]['unidad']))
                                    <p class="text-muted mb-0 small">{{ $item['datos']['variables'][0]['unidad'] }}</p>
                                    @endif
                                    @if(isset($item['datos']['tendencia']) && count($item['datos']['tendencia']) > 1)
                                    <div class="sparkline-container" id="sparkline-{{ $item['config']->id }}"></div>
                                    @endif
                                </div>
                                @else
                                <div class="chart-box-premium mt-auto" id="chart-{{ $item['config']->id }}" style="height: {{ $gridClass == 'col-12' ? '400px' : '300px' }}; width: 100%; position: relative; z-index: 2;"></div>
                                @endif
                    </div>
                    @if(isset($item['datos']['fuente']))
                    <div class="card-footer-premium border-0">
                        <p class="fuente-texto">
                            <i class="fa-solid fa-database me-1"></i> Fuente: <strong>{{ $item['datos']['fuente'] }}</strong>
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
<script>
    window.FichaConfig = {
        cvegeo: "{{ $municipio->cvegeo }}",
        municipioNombre: "{{ strtoupper($municipio->nombre) }}",
        geojsonUrl: "{{ asset('geojson/municipios_puebla_slim.geojson ') }}",
        perfilData: @json($perfil)
    };
</script>
<script src="{{ asset('js/perfil.js') }}?v={{ time() }}"></script>
@endsection