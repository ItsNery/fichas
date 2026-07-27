@extends('layouts.plantilla')

@section('title', 'Ficha del municipio de ' . $municipio->nombre)

@section('css')
@endsection

@section('content')

    {{-- 1. HERO SECTION (v2) --}}
    <section class="hero-ficha"
        style="background-image: url('{{ $municipio->banner_image_url ?? asset(config('regionalizacion.fallback_hero')) }}')">
        <div class="hero-ficha__capa-gradiente"></div>

        <div class="container hero-ficha__contenedor">
            <div class="row align-items-center">
                <div class="col-lg-7 text-white">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-white-50">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('ficha-municipal.index') }}"
                                    class="text-white-50">Directorio</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">{{ $municipio->nombre }}</li>
                        </ol>
                    </nav>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge bg-gold px-3 py-2 text-uppercase hero-ficha__badge m-0">FICHA MUNICIPAL</span>
                        <button type="button" class="btn btn-outline-light btn-sm fw-bold px-3 py-1 rounded-pill"
                            data-bs-toggle="modal" data-bs-target="#compararModal">
                            Comparar
                        </button>
                        <a href="{{ route('ficha-municipal.perfil.pdf', $municipio->slug) }}"
                           class="btn btn-outline-light btn-sm fw-bold px-3 py-1 rounded-pill"
                           target="_blank" data-pdf-link>
                            <i class="fa-solid fa-file-pdf me-1"></i> PDF
                        </a>
                    </div>
                    <h1 class="hero-ficha__titulo mb-2">{{ $municipio->nombre }}</h1>
                    <p class="hero-ficha__subtitulo mb-5 opacity-75">
                        {{ $municipio->microrregion->macrorregion->nombre ?? 'Estado de Puebla' }} |
                        {{ $municipio->microrregion->nombre ?? 'Región' }}
                    </p>

                    <div class="d-flex gap-5 mt-5 flex-column flex-md-row">
                        <div class="hero-info-item">
                            <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Cabecera</small>
                            <span class="h5 fw-bold">{{ $municipio->cabecera ?? $municipio->nombre }}</span>
                        </div>
                        <div class="hero-info-item">
                            <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Clima predominante</small>
                            <span class="h5 fw-bold d-block">{{ $municipio->clima ?? 'Información no disponible' }}</span>
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

        @php
            $attr = $municipio->banner_attribution;
            $creditUrl = $attr['source_url'] ?? null;
        @endphp
        @if($attr && ($attr['author'] ?? null) && ($attr['license'] ?? null) && $creditUrl)
            <a href="{{ $creditUrl }}" target="_blank" rel="noopener noreferrer"
                class="hero-ficha__creditos"
                data-bs-toggle="tooltip" data-bs-placement="left"
                title="Foto por {{ $attr['author'] }} — {{ $attr['license'] }}">
                <i class="fas fa-camera"></i>
            </a>
        @elseif($attr && $creditUrl)
            <a href="{{ $creditUrl }}" target="_blank" rel="noopener noreferrer"
                class="hero-ficha__creditos"
                data-bs-toggle="tooltip" data-bs-placement="left"
                title="Ver fuente de la imagen">
                <i class="fas fa-camera"></i>
            </a>
        @endif
    </section>

    {{-- 2. ICON BAR (Hero Stats) --}}
    <section class="barra-indicadores shadow-lg">
        <div class="container">
            <div class="row text-center justify-content-center flex-wrap g-4">
                <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                    <i class="fas fa-users barra-indicadores__icono"></i>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <span class="barra-indicadores__valor">{{ number_format($poblacionTotal) }}</span>
                        <button type="button"
                            class="btn btn-link btn-sm p-0 text-gold similarity-popover-trigger hero-similarity-btn"
                            data-municipio-id="{{ $municipio->id }}" data-config-id="poblacion"
                            title="<i class='fa-solid fa-lightbulb me-1 text-gold'></i> Municipios Similares en Población"
                            data-bs-toggle="popover" data-bs-html="true"
                            data-bs-content="<div class='text-center py-2'><div class='spinner-border spinner-border-sm text-vino' role='status'></div><p class='small text-muted mb-0 mt-1' style='font-size: 11px;'>Calculando cercanía...</p></div>"
                            data-bs-trigger="click">
                            <i class="fa-solid fa-lightbulb"></i>
                        </button>
                    </div>
                    <span class="barra-indicadores__etiqueta">Población Total</span>
                </div>
                <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                    <i class="fas fa-briefcase barra-indicadores__icono"></i>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <span class="barra-indicadores__valor">{{ number_format($pea) }}</span>
                        <button type="button"
                            class="btn btn-link btn-sm p-0 text-gold similarity-popover-trigger hero-similarity-btn"
                            data-municipio-id="{{ $municipio->id }}" data-config-id="pea"
                            title="<i class='fa-solid fa-lightbulb me-1 text-gold'></i> Municipios Similares en PEA"
                            data-bs-toggle="popover" data-bs-html="true"
                            data-bs-content="<div class='text-center py-2'><div class='spinner-border spinner-border-sm text-vino' role='status'></div><p class='small text-muted mb-0 mt-1' style='font-size: 11px;'>Calculando cercanía...</p></div>"
                            data-bs-trigger="click">
                            <i class="fa-solid fa-lightbulb"></i>
                        </button>
                    </div>
                    <span class="barra-indicadores__etiqueta">Población Activa (PEA)</span>
                </div>
                <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                    <i class="fas fa-hand-holding-usd barra-indicadores__icono"></i>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <span class="barra-indicadores__valor">{{ $porcentajePobreza }}</span>
                        <button type="button"
                            class="btn btn-link btn-sm p-0 text-gold similarity-popover-trigger hero-similarity-btn"
                            data-municipio-id="{{ $municipio->id }}" data-config-id="pobreza"
                            title="<i class='fa-solid fa-lightbulb me-1 text-gold'></i> Municipios Similares en Pobreza"
                            data-bs-toggle="popover" data-bs-html="true"
                            data-bs-content="<div class='text-center py-2'><div class='spinner-border spinner-border-sm text-vino' role='status'></div><p class='small text-muted mb-0 mt-1' style='font-size: 11px;'>Calculando cercanía...</p></div>"
                            data-bs-trigger="click">
                            <i class="fa-solid fa-lightbulb"></i>
                        </button>
                    </div>
                    <span class="barra-indicadores__etiqueta">Población en Pobreza</span>
                </div>
                <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                    <i class="fas fa-dollar-sign barra-indicadores__icono"></i>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <span class="barra-indicadores__valor">${{ number_format($presupuesto, 2) }}</span>
                        <button type="button"
                            class="btn btn-link btn-sm p-0 text-gold similarity-popover-trigger hero-similarity-btn"
                            data-municipio-id="{{ $municipio->id }}" data-config-id="presupuesto"
                            title="<i class='fa-solid fa-lightbulb me-1 text-gold'></i> Municipios Similares en Presupuesto"
                            data-bs-toggle="popover" data-bs-html="true"
                            data-bs-content="<div class='text-center py-2'><div class='spinner-border spinner-border-sm text-vino' role='status'></div><p class='small text-muted mb-0 mt-1' style='font-size: 11px;'>Calculando cercanía...</p></div>"
                            data-bs-trigger="click">
                            <i class="fa-solid fa-lightbulb"></i>
                        </button>
                    </div>
                    <span class="barra-indicadores__etiqueta">Presupuesto {{ $ultimoAnioPres }}</span>
                </div>
                <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                    <i class="fas fa-chart-line barra-indicadores__icono"></i>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <span class="barra-indicadores__valor">{{ $gradoMarginacion }}</span>
                        <button type="button"
                            class="btn btn-link btn-sm p-0 text-gold similarity-popover-trigger hero-similarity-btn"
                            data-municipio-id="{{ $municipio->id }}" data-config-id="marginacion"
                            title="<i class='fa-solid fa-lightbulb me-1 text-gold'></i> Municipios Similares en Marginación"
                            data-bs-toggle="popover" data-bs-html="true"
                            data-bs-content="<div class='text-center py-2'><div class='spinner-border spinner-border-sm text-vino' role='status'></div><p class='small text-muted mb-0 mt-1' style='font-size: 11px;'>Calculando cercanía...</p></div>"
                            data-bs-trigger="click">
                            <i class="fa-solid fa-lightbulb"></i>
                        </button>
                    </div>
                    <span class="barra-indicadores__etiqueta">Marginación</span>
                </div>
                <div class="col-6 col-md-2 col-lg flex-fill barra-indicadores__item">
                    <i class="fas fa-expand-arrows-alt barra-indicadores__icono"></i>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <span class="barra-indicadores__valor">{{ number_format($superficieKm2, 2) }} km²</span>
                        <button type="button"
                            class="btn btn-link btn-sm p-0 text-gold similarity-popover-trigger hero-similarity-btn"
                            data-municipio-id="{{ $municipio->id }}" data-config-id="superficie"
                            title="<i class='fa-solid fa-lightbulb me-1 text-gold'></i> Municipios Similares en Superficie"
                            data-bs-toggle="popover" data-bs-html="true"
                            data-bs-content="<div class='text-center py-2'><div class='spinner-border spinner-border-sm text-vino' role='status'></div><p class='small text-muted mb-0 mt-1' style='font-size: 11px;'>Calculando cercanía...</p></div>"
                            data-bs-trigger="click">
                            <i class="fa-solid fa-lightbulb"></i>
                        </button>
                    </div>
                    <span class="barra-indicadores__etiqueta">Superficie Territorial</span>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. NAV HORIZONTAL PARA DIMENSIONES --}}
    <div class="sticky-nav">
        <div class="container sticky-nav__contenedor">
            <ul class="nav justify-content-center sticky-nav__list">
                @foreach($perfil as $seccion => $items)
                    @if($seccion != 'general')
                        <li class="nav-item sticky-nav__item">
                            <a href="#section-{{ Str::slug($seccion) }}"
                                class="nav-link sticky-nav__link">{{ ucwords(str_replace('_', ' ', $seccion)) }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    {{-- 4. CONTENIDO EDITORIAL --}}
    <div class="container mt-4">
        @foreach($perfil as $seccion => $items)
            @if($seccion != 'general')
                <section id="section-{{ Str::slug($seccion) }}" class="section-perfil mb-4 pb-4">
                    <div class="dimension-header shadow-sm">
                        <h2 class="dimension-header__title mb-0">{{ ucwords(str_replace('_', ' ', $seccion)) }}</h2>
                    </div>

                    <div class="row g-4 align-items-stretch">
                        @foreach($items as $item)
                            @php
                                $gridClass = $item['config']->clase_grid ?: 'col-12';
                                $isKpi = $item['config']->tipo_visualizacion === 'kpi';
                            @endphp

                            <div class="{{ $gridClass }}">
                                <article class="perfil-tarjeta">
                                    <div class="perfil-tarjeta__body">
                                        {{-- Watermark Icon --}}
                                        @php $cardIcon = $item['datos']['icono_actual'] ?? $item['config']->icono; @endphp
                                        @if($cardIcon)
                                            <div class="perfil-tarjeta__watermark">
                                                <i class="{{ $cardIcon }}"></i>
                                            </div>
                                        @endif

                                        @php
                                            $tendenciaDss = $item['datos']['tendencia'] ?? [];
                                            if (count($tendenciaDss) > 1) {
                                                usort($tendenciaDss, fn($a, $b) => $a['anio'] <=> $b['anio']);
                                            }
                                            $mostrarTendenciaDss = count($tendenciaDss) > 1
                                                && (float) $tendenciaDss[count($tendenciaDss) - 2]['valor'] != 0.0;
                                            $polaridad = $item['datos']['polaridad'] ?? null;
                                            $mostrarTendenciaDss = $mostrarTendenciaDss
                                                && ($item['config']->tipo_visualizacion ?? null) !== 'scatter'
                                                && in_array($polaridad, ['asendente', 'ascendente', 'descendente'], true);
                                            $cambioReciente = null;
                                            $tendenciaIcono = null;
                                            $tendenciaClase = 'text-muted';
                                            $polaridadTexto = null;

                                            if ($mostrarTendenciaDss) {
                                                $polaridadEsAscendente = in_array($polaridad, ['asendente', 'ascendente'], true);
                                                $polaridadTexto = $polaridadEsAscendente
                                                    ? 'Subir se interpreta como mejora.'
                                                    : 'Bajar se interpreta como mejora.';
                                                $anterior = (float) $tendenciaDss[count($tendenciaDss) - 2]['valor'];
                                                $actual = (float) $tendenciaDss[count($tendenciaDss) - 1]['valor'];
                                                $cambioReciente = (($actual - $anterior) / abs($anterior)) * 100;
                                                $esMejora = $polaridadEsAscendente ? $cambioReciente > 0 : $cambioReciente < 0;
                                                $tendenciaIcono = $cambioReciente > 0 ? 'fa-arrow-up' : ($cambioReciente < 0 ? 'fa-arrow-down' : 'fa-minus');
                                                $tendenciaClase = abs($cambioReciente) < 0.05 ? 'text-muted' : ($esMejora ? 'text-success' : 'text-danger');
                                            }
                                        @endphp

                                        <header class="perfil-tarjeta__header">
                                            <h3 class="perfil-tarjeta__titulo mb-0">
                                                {{ $item['config']->titulo_reporte ?? $item['config']->indicador->nombre_amigable }}
                                                @if(isset($item['datos']['anio']) && $item['datos']['anio'])
                                                    <span class="perfil-tarjeta__anio-badge">
                                                        {{ $item['datos']['anio'] }}
                                                    </span>
                                                @endif

                                            </h3>
                                            <div class="d-flex align-items-center gap-2">
                                                @if(isset($item['datos']['echarts']['type']) && $item['datos']['echarts']['type'] === 'bar-horizontal')
                                                    <span class="perfil-tarjeta__context-icon" tabindex="0" role="img"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="Comparativa regional: {{ $municipio->microrregion->macrorregion->nombre ?? 'región correspondiente' }}"
                                                        aria-label="Comparativa regional: {{ $municipio->microrregion->macrorregion->nombre ?? 'región correspondiente' }}">
                                                        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                                                    </span>
                                                @endif
                                                @if($mostrarTendenciaDss)
                                                    <span class="perfil-tarjeta__trend-icon {{ $tendenciaClase }}" tabindex="0" role="img"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="{{ $polaridadTexto }} Cambio reciente: {{ number_format(abs($cambioReciente), 1) }}%"
                                                        aria-label="{{ $polaridadTexto }} Cambio reciente: {{ number_format(abs($cambioReciente), 1) }}%">
                                                        <i class="fa-solid {{ $tendenciaIcono }}" aria-hidden="true"></i>
                                                        <span>{{ number_format(abs($cambioReciente), 1) }}%</span>
                                                    </span>
                                                @endif
                                                @if(isset($item['config']->indicador))
                                                    <a href="{{ route('banco-indicadores.index', ['indicador_id' => $item['config']->indicador->id, 'municipio_ids' => $municipio->id]) }}"
                                                        class="perfil-tarjeta__info-icon text-muted"
                                                        title="Ver gráfico en Banco de Indicadores" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" target="_blank"
                                                        style="font-size: 1rem; transition: color 0.2s;"
                                                        onmouseover="this.style.color='#861e34'" onmouseout="this.style.color=''">
                                                        <i class="fa-solid fa-chart-column"></i>
                                                    </a>
                                                @endif
                                                @if(isset($item['datos']['metodo_calculo']) || isset($item['datos']['fuente']) || isset($item['datos']['correlacion']))
                                                    <i class="fa-solid fa-circle-info info-tooltip-trigger perfil-tarjeta__info-icon mb-0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus" title="Metodología y fuente"
                                                        data-bs-content="<strong>Método:</strong> {{ $item['datos']['metodo_calculo'] ?? 'No especificado' }}@if(isset($item['datos']['correlacion_lectura']))<br><strong>Asociación lineal:</strong> {{ $item['datos']['correlacion_lectura'] }} <small>Es una medida descriptiva y no implica causalidad.</small>@endif<br><strong>Fuente:</strong> {{ $item['datos']['fuente'] ?? 'No especificada' }}"
                                                        data-bs-html="true"></i>
                                                @endif
                                            </div>
                                        </header>

                                        @php
                                            $subtituloReporte = $item['config']->subtitulo_reporte;
                                            $historialConfigurado = (int) ($item['config']->anios_historial ?? 5);
                                            if ($historialConfigurado < 2 && $subtituloReporte && str_contains(mb_strtolower($subtituloReporte), 'compar')) {
                                                $subtituloReporte = 'Último corte disponible';
                                            }
                                        @endphp
                                        @if($subtituloReporte)
                                            <p class="text-muted small mb-2">{{ $subtituloReporte }}</p>
                                        @endif
                                        {{-- Insight Badge --}}
                                        @if(isset($item['datos']['ranking']) && isset($item['datos']['polaridad']) && !is_array($item['datos']['ranking']))
                                            @php
                                                $rank = $item['datos']['ranking'];
                                                $pol = $item['datos']['polaridad'];
                                                $badgeClass = 'insight-badge--info';
                                                $badgeIcon = 'fa-circle-check';
                                                $badgeText = 'Dato Relevante';

                                                if ($pol == 'asendente') {
                                                    if ($rank <= 20) {
                                                        $badgeClass = 'insight-badge--success';
                                                        $badgeIcon = 'fa-award';
                                                        $badgeText = 'Líder Estatal';
                                                    } elseif ($rank >= 180) {
                                                        $badgeClass = 'insight-badge--danger';
                                                        $badgeIcon = 'fa-triangle-exclamation';
                                                        $badgeText = 'Área de Oportunidad';
                                                    }
                                                } elseif ($pol == 'descendente') {
                                                    if ($rank <= 20) {
                                                        $badgeClass = 'insight-badge--danger';
                                                        $badgeIcon = 'fa-triangle-exclamation';
                                                        $badgeText = 'Alerta de Prioridad';
                                                    } elseif ($rank >= 180) {
                                                        $badgeClass = 'insight-badge--success';
                                                        $badgeIcon = 'fa-award';
                                                        $badgeText = 'Desempeño Destacado';
                                                    }
                                                }
                                            @endphp
                                            <div class="perfil-tarjeta__insight-wrapper">
                                                <span class="insight-badge {{ $badgeClass }}">
                                                    <i class="fa-solid {{ $badgeIcon }}"></i> {{ $badgeText }} (#{{ $rank }})
                                                </span>
                                            </div>
                                        @endif

                                        @if($item['narrativa'])
                                            <div class="perfil-tarjeta__narrativa-wrapper">
                                                <p class="perfil-tarjeta__narrativa-texto">{!! $item['narrativa'] !!}</p>
                                            </div>
                                        @endif

                                        @if($isKpi)
                                            <div class="perfil-tarjeta__kpi-wrapper">
                                                <div class="d-flex align-items-center justify-content-center gap-3">
                                                    <h4 class="perfil-tarjeta__kpi-value">
                                                        {{ $item['datos']['valor_actual'] ?? $item['datos']['total'] ?? 0 }}
                                                    </h4>
                                                </div>

                                                @if(isset($item['datos']['variables'][0]['unidad']))
                                                    <p class="perfil-tarjeta__kpi-unit">{{ $item['datos']['variables'][0]['unidad'] }}</p>
                                                @endif

                                                @php
                                                    $munVal = (float) ($item['datos']['total'] ?? 0);
                                                    $promMacro = isset($item['datos']['promedio_macrorregional']) && $item['datos']['promedio_macrorregional'] !== null ? (float) $item['datos']['promedio_macrorregional'] : null;
                                                    $promEst = isset($item['datos']['promedio_estatal']) && $item['datos']['promedio_estatal'] !== null ? (float) $item['datos']['promedio_estatal'] : null;
                                                    $polaridad = $item['datos']['polaridad'] ?? 'neutral';
                                                    $unidadRaw = $item['datos']['variables'][0]['unidad'] ?? '';
                                                    $suffix = in_array(strtolower($unidadRaw), ['porcentaje', '%', 'pct']) ? '%' : '';

                                                    $isCategorical = false;
                                                    if (isset($item['datos']['valor_actual']) && !is_numeric(str_replace([',', '$', '%', ' '], '', $item['datos']['valor_actual']))) {
                                                        $isCategorical = true;
                                                    }

                                                    $formatValue = function ($val) use ($unidadRaw, $suffix) {
                                                        if (in_array(strtolower($unidadRaw), ['pesos', '$', 'pesos mexicanos'])) {
                                                            return '$' . number_format($val, 0);
                                                        }
                                                        if (floor($val) == $val) {
                                                            return number_format($val, 0) . $suffix;
                                                        }
                                                        return number_format($val, 1) . $suffix;
                                                    };
                                                @endphp

                                                @if(($item['config']->mostrar_comparativa ?? false) && !$isCategorical && (($promMacro !== null && $promMacro > 0) || ($promEst !== null && $promEst > 0)))
                                                    <div class="perfil-tarjeta__benchmarks">
                                                        @if($promMacro !== null && $promMacro > 0)
                                                            @php
                                                                $diffMacro = $munVal - $promMacro;
                                                                $pctMacro = $promMacro > 0 ? ($diffMacro / $promMacro) * 100 : 0;

                                                                $macroTrendClass = 'perfil-tarjeta__benchmark-indicator--neutral';
                                                                $macroIcon = 'fa-minus';

                                                                if (in_array($polaridad, ['asendente', 'ascendente'])) {
                                                                    if ($diffMacro > 0.01) {
                                                                        $macroTrendClass = 'perfil-tarjeta__benchmark-indicator--positivo';
                                                                        $macroIcon = 'fa-arrow-up';
                                                                    } elseif ($diffMacro < -0.01) {
                                                                        $macroTrendClass = 'perfil-tarjeta__benchmark-indicator--negativo';
                                                                        $macroIcon = 'fa-arrow-down';
                                                                    }
                                                                } elseif ($polaridad === 'descendente') {
                                                                    if ($diffMacro < -0.01) {
                                                                        $macroTrendClass = 'perfil-tarjeta__benchmark-indicator--positivo';
                                                                        $macroIcon = 'fa-arrow-down';
                                                                    } elseif ($diffMacro > 0.01) {
                                                                        $macroTrendClass = 'perfil-tarjeta__benchmark-indicator--negativo';
                                                                        $macroIcon = 'fa-arrow-up';
                                                                    }
                                                                } else {
                                                                    if ($diffMacro > 0.01) {
                                                                        $macroIcon = 'fa-arrow-up';
                                                                    } elseif ($diffMacro < -0.01) {
                                                                        $macroIcon = 'fa-arrow-down';
                                                                    }
                                                                }
                                                            @endphp
                                                            <div class="perfil-tarjeta__benchmark-item">
                                                                <span class="perfil-tarjeta__benchmark-label">Macrorregión</span>
                                                                <span class="perfil-tarjeta__benchmark-value">{{ $formatValue($promMacro) }}</span>
                                                                <span class="perfil-tarjeta__benchmark-indicator {{ $macroTrendClass }}">
                                                                    <i class="fa-solid {{ $macroIcon }}"></i>
                                                                    {{ number_format(abs($pctMacro), 1) }}%
                                                                </span>
                                                            </div>
                                                        @endif

                                                        @if($promEst !== null && $promEst > 0)
                                                            @php
                                                                $diffEst = $munVal - $promEst;
                                                                $pctEst = $promEst > 0 ? ($diffEst / $promEst) * 100 : 0;

                                                                $estTrendClass = 'perfil-tarjeta__benchmark-indicator--neutral';
                                                                $estIcon = 'fa-minus';

                                                                if (in_array($polaridad, ['asendente', 'ascendente'])) {
                                                                    if ($diffEst > 0.01) {
                                                                        $estTrendClass = 'perfil-tarjeta__benchmark-indicator--positivo';
                                                                        $estIcon = 'fa-arrow-up';
                                                                    } elseif ($diffEst < -0.01) {
                                                                        $estTrendClass = 'perfil-tarjeta__benchmark-indicator--negativo';
                                                                        $estIcon = 'fa-arrow-down';
                                                                    }
                                                                } elseif ($polaridad === 'descendente') {
                                                                    if ($diffEst < -0.01) {
                                                                        $estTrendClass = 'perfil-tarjeta__benchmark-indicator--positivo';
                                                                        $estIcon = 'fa-arrow-down';
                                                                    } elseif ($diffEst > 0.01) {
                                                                        $estTrendClass = 'perfil-tarjeta__benchmark-indicator--negativo';
                                                                        $estIcon = 'fa-arrow-up';
                                                                    }
                                                                } else {
                                                                    if ($diffEst > 0.01) {
                                                                        $estIcon = 'fa-arrow-up';
                                                                    } elseif ($diffEst < -0.01) {
                                                                        $estIcon = 'fa-arrow-down';
                                                                    }
                                                                }
                                                            @endphp
                                                            <div class="perfil-tarjeta__benchmark-item">
                                                                <span class="perfil-tarjeta__benchmark-label">Estado</span>
                                                                <span class="perfil-tarjeta__benchmark-value">{{ $formatValue($promEst) }}</span>
                                                                <span class="perfil-tarjeta__benchmark-indicator {{ $estTrendClass }}">
                                                                    <i class="fa-solid {{ $estIcon }}"></i> {{ number_format(abs($pctEst), 1) }}%
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if(isset($item['datos']['tendencia']) && count($item['datos']['tendencia']) > 1)
                                                    <div class="perfil-tarjeta__sparkline" id="sparkline-{{ $item['config']->id }}"
                                                        data-chart-id="{{ $item['config']->id }}"></div>
                                                @endif
                                            </div>
                                        @else
                                            <div
                                                class="perfil-tarjeta__chart-wrapper {{ $gridClass == 'col-12' ? 'perfil-tarjeta__chart-wrapper--full' : 'perfil-tarjeta__chart-wrapper--half' }}">
                                                <div class="perfil-tarjeta__skeleton" id="skeleton-{{ $item['config']->id }}">
                                                    <div class="spinner-border perfil-tarjeta__spinner" role="status">
                                                        <span class="visually-hidden">Cargando gráfico...</span>
                                                    </div>
                                                </div>
                                                <div class="perfil-tarjeta__chart-box lazy-chart {{ $gridClass == 'col-12' ? 'perfil-tarjeta__chart-box--full' : 'perfil-tarjeta__chart-box--half' }}"
                                                    id="chart-{{ $item['config']->id }}" data-chart-id="{{ $item['config']->id }}"></div>
                                            </div>

                                            @if(isset($item['datos']['available_years']) && count($item['datos']['available_years']) > 1 && (isset($item['datos']['echarts']['type']) && $item['datos']['echarts']['type'] !== 'line'))
                                                <div class="perfil-tarjeta__years-container" aria-label="Seleccionar corte temporal">
                                                    <span class="perfil-tarjeta__years-label">Corte temporal</span>
                                                    <div class="perfil-tarjeta__years-list">
                                                    @foreach($item['datos']['available_years'] as $yr)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2 btn-year-selector {{ (isset($item['datos']['anio']) && $item['datos']['anio'] == $yr) ? 'active btn-vino text-white border-vino' : '' }}"
                                                            data-year="{{ $yr }}" data-config-id="{{ $item['config']->id }}"
                                                            data-muni-slug="{{ $municipio->slug }}">
                                                            {{ $yr }}
                                                        </button>
                                                    @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    @if(isset($item['datos']['fuente']))
                                        <div class="perfil-tarjeta__footer">
                                            <p class="fuente-texto">
                                                Fuente:
                                                <strong>{{ $item['datos']['fuente'] }}</strong>
                                            </p>
                                        </div>
                                    @endif
                                </article>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>

    {{-- 5. SECCIÓN DE MUNICIPIOS SIMILARES --}}
    <section class="similares-seccion py-4 bg-light border-top">
        <div class="container">
            <div class="row align-items-center mb-4">
                <div class="col-md-8 text-start">
                    <h2 class="display-6 fw-bold mb-1 text-vino">
                        Municipios con Condiciones Similares
                    </h2>
                    <p class="text-muted mb-0">Identifica territorios con características demográficas o geográficas afines
                        en el Estado de Puebla.</p>
                </div>
            </div>

            <div class="row g-4 align-items-stretch">
                {{-- Columna 1: Por Población --}}
                <div class="col-lg-6">
                    <div class="similares-seccion__col-card p-4 bg-white shadow-sm rounded-4 h-100 border text-start">
                        <h3 class="h5 fw-bold mb-3 pb-2 border-bottom text-vino">
                            Población Similar
                        </h3>

                        @if($similaresPoblacion->isEmpty())
                            <p class="text-muted small">No se encontraron datos comparativos.</p>
                        @else
                            <div class="d-flex flex-column gap-3">
                                @foreach($similaresPoblacion as $sim)
                                    <div
                                        class="similar-card p-3 rounded border transition-all d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="h6 fw-bold text-vino mb-1">{{ $sim->nombre }}</h4>
                                            <p class="text-muted small mb-0">Población:
                                                <strong>{{ number_format($sim->poblacion_valor) }} hab.</strong>
                                            </p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('ficha-municipal.perfil', $sim->slug) }}"
                                                class="btn btn-outline-secondary btn-sm px-3 rounded-pill">Ver Perfil</a>
                                            <a href="{{ route('ficha-municipal.comparar', [$municipio->slug, $sim->slug]) }}"
                                                class="btn btn-vino btn-sm px-3 text-white rounded-pill">
                                                <i class="fa-solid fa-scale-balanced me-1"></i> Comparar
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Columna 2: Por Ubicación --}}
                <div class="col-lg-6">
                    <div class="similares-seccion__col-card p-4 bg-white shadow-sm rounded-4 h-100 border text-start">
                        <h3 class="h5 fw-bold mb-3 pb-2 border-bottom text-vino">
                            Misma Región Geográfica
                        </h3>

                        @if($similaresRegion->isEmpty())
                            <p class="text-muted small">No se encontraron otros municipios en esta microrregión.</p>
                        @else
                            <div class="d-flex flex-column gap-3">
                                @foreach($similaresRegion as $sim)
                                    <div
                                        class="similar-card p-3 rounded border transition-all d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="h6 fw-bold text-vino mb-1">{{ $sim->nombre }}</h4>
                                            <p class="text-muted small mb-0">Región:
                                                <strong>{{ $sim->microrregion->nombre ?? 'N/D' }}</strong>
                                            </p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('ficha-municipal.perfil', $sim->slug) }}"
                                                class="btn btn-outline-secondary btn-sm px-3 rounded-pill">Ver Perfil</a>
                                            <a href="{{ route('ficha-municipal.comparar', [$municipio->slug, $sim->slug]) }}"
                                                class="btn btn-vino btn-sm px-3 text-white rounded-pill">
                                                <i class="fa-solid fa-scale-balanced me-1"></i> Comparar
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- COMPARAR MODAL --}}
    <div class="modal fade" id="compararModal" tabindex="-1" aria-labelledby="compararModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-vino text-white rounded-top-4 py-3">
                    <h5 class="modal-title fw-bold" id="compararModalLabel">
                        <i class="fa-solid fa-scale-balanced me-2"></i> Comparar con otro Municipio
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small">Selecciona un municipio de la lista para contrastar sus indicadores
                        demográficos, económicos y de gobierno lado a lado.</p>
                    <form id="compare-modal-form" class="mt-3">
                        <div class="mb-4">
                            <label for="modal-select-muni" class="form-label fw-bold small text-uppercase">Municipio a
                                comparar</label>
                            <select id="modal-select-muni" class="form-select select-muni" required>
                                <option value="">Buscar municipio...</option>
                                @foreach(\App\Models\Municipio::where('id', '!=', $municipio->id)->orderBy('nombre', 'asc')->get() as $m)
                                    <option value="{{ $m->slug }}">{{ $m->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-vino py-2 fw-bold text-uppercase">Iniciar
                                Comparativa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script>
        window.FichaConfig = {
            cvegeo: "{{ $municipio->cvegeo }}",
            municipioNombre: "{{ strtoupper($municipio->nombre) }}",
            municipioSlug: "{{ $municipio->slug }}",
            geojsonUrl: "{{ asset('geojson/municipios_puebla_slim.geojson ') }}",
            perfilData: @json($perfil)
        };
    </script>
    <script src="{{ asset('js/perfil.js') }}?v={{ time() }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const pdfLink = document.querySelector('[data-pdf-link]');

            if (pdfLink) {
                pdfLink.addEventListener('click', function (event) {
                    if (this.dataset.loading === 'true') {
                        event.preventDefault();
                        return;
                    }

                    this.dataset.loading = 'true';
                    this.setAttribute('aria-disabled', 'true');
                    this.setAttribute('aria-busy', 'true');
                    this.classList.add('disabled');
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Generando PDF...';

                    window.setTimeout(() => {
                        this.dataset.loading = 'false';
                        this.removeAttribute('aria-disabled');
                        this.removeAttribute('aria-busy');
                        this.classList.remove('disabled');
                        this.innerHTML = '<i class="fa-solid fa-file-pdf me-1"></i> PDF';
                    }, 30000);
                });
            }

            const form = document.getElementById("compare-modal-form");
            if (form) {
                form.addEventListener("submit", function (e) {
                    e.preventDefault();
                    const selectedMuni = document.getElementById("modal-select-muni").value;
                    if (selectedMuni) {
                        window.location.href = `/ficha/municipio/comparar/{{ $municipio->slug }}/${selectedMuni}`;
                    }
                });
            }
        });
    </script>
@endsection
