@extends('layouts.plantilla')

@section('title', 'Comparación: ' . $municipio1->nombre . ' vs ' . $municipio2->nombre)

@section('content')
    {{-- 1. COMPARATOR HERO --}}
    <section class="hero-comparar">
        <div class="hero-comparar__gradient"></div>
        <div class="container hero-comparar__container text-white">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-white-50">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('ficha-municipal.index') }}"
                                    class="text-white-50">Directorio</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Comparar Municipios</li>
                        </ol>
                    </nav>
                    <h1 class="display-4 fw-bold mb-2">Comparador Municipal</h1>
                    <p class="lead opacity-75">Análisis comparativo directo entre dos municipios</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="{{ route('ficha-municipal.comparar.pdf', [$municipio1->slug, $municipio2->slug]) }}"
                        class="badge bg-gold px-4 py-2 fw-bold text-uppercase shadow-sm">
                        <i class="fa-solid fa-file-pdf me-2"></i> Exportar Reporte PDF
                    </a>
                </div>
            </div>

            <div class="row g-4 align-items-stretch">
                {{-- Municipio 1 --}}
                <div class="col-md-5">
                    <div class="hero-comparar__card hero-comparar__card--left text-start p-4">
                        <span class="badge bg-gold mb-2 text-uppercase">Municipio 1</span>
                        <h2 class="display-5 fw-bold mb-1">
                            <a href="{{ route('ficha-municipal.perfil', $municipio1->slug) }}"
                                class="text-white">{{ $municipio1->nombre }}</a>
                        </h2>
                        <p class="text-white-50 small mb-4">
                            {{ $municipio1->microrregion->macrorregion->nombre ?? 'Estado de Puebla' }} |
                            {{ $municipio1->microrregion->nombre ?? 'Región' }}
                        </p>

                        <div class="d-flex flex-wrap gap-4 mt-3">
                            <div>
                                <small class="d-block text-white-50 text-uppercase letter-spacing-1 small">Población</small>
                                <span class="h5 fw-bold">{{ number_format($hero1['poblacionTotal']) }}</span>
                            </div>
                            <div class="border-start ps-3">
                                <small
                                    class="d-block text-white-50 text-uppercase letter-spacing-1 small">Presupuesto</small>
                                <span class="h5 fw-bold">${{ number_format($hero1['presupuesto'], 0) }}</span>
                            </div>
                            <div class="border-start ps-3">
                                <small
                                    class="d-block text-white-50 text-uppercase letter-spacing-1 small">Marginación</small>
                                <span class="h5 fw-bold">{{ $hero1['gradoMarginacion'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- VS Circle --}}
                <div class="col-md-2 d-flex align-items-center justify-content-center">
                    <div class="hero-comparar__vs-circle">
                        <span class="fw-bold h4 m-0">VS</span>
                    </div>
                </div>

                {{-- Municipio 2 --}}
                <div class="col-md-5">
                    <div class="hero-comparar__card hero-comparar__card--right text-start p-4">
                        <span class="badge bg-gold mb-2 text-uppercase">Municipio 2</span>
                        <h2 class="display-5 fw-bold mb-1">
                            <a href="{{ route('ficha-municipal.perfil', $municipio2->slug) }}"
                                class="text-white">{{ $municipio2->nombre }}</a>
                        </h2>
                        <p class="text-white-50 small mb-4">
                            {{ $municipio2->microrregion->macrorregion->nombre ?? 'Estado de Puebla' }} |
                            {{ $municipio2->microrregion->nombre ?? 'Región' }}
                        </p>

                        <div class="d-flex flex-wrap gap-4 mt-3">
                            <div>
                                <small class="d-block text-white-50 text-uppercase letter-spacing-1 small">Población</small>
                                <span class="h5 fw-bold">{{ number_format($hero2['poblacionTotal']) }}</span>
                            </div>
                            <div class="border-start ps-3">
                                <small
                                    class="d-block text-white-50 text-uppercase letter-spacing-1 small">Presupuesto</small>
                                <span class="h5 fw-bold">${{ number_format($hero2['presupuesto'], 0) }}</span>
                            </div>
                            <div class="border-start ps-3">
                                <small
                                    class="d-block text-white-50 text-uppercase letter-spacing-1 small">Marginación</small>
                                <span class="h5 fw-bold">{{ $hero2['gradoMarginacion'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. SELECTOR COCKPIT --}}
    <section class="barra-comparar bg-white border-bottom shadow-sm">
        <div class="container py-3">
            <form id="compare-selector-form" class="row align-items-center justify-content-center g-3">
                <div class="col-md-4">
                    <select id="select-muni1" class="form-select select-muni" required>
                        @foreach ($todosMunicipios as $m)
                            <option value="{{ $m->slug }}" {{ $m->id === $municipio1->id ? 'selected' : '' }}>
                                {{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto text-muted fw-bold">comparado frente a</div>
                <div class="col-md-4">
                    <select id="select-muni2" class="form-select select-muni" required>
                        @foreach ($todosMunicipios as $m)
                            <option value="{{ $m->slug }}" {{ $m->id === $municipio2->id ? 'selected' : '' }}>
                                {{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-vino px-4">Comparar</button>
                </div>
            </form>
        </div>
    </section>

    {{-- 3. NAV HORIZONTAL --}}
    <div class="sticky-nav">
        <div class="container sticky-nav__contenedor">
            <ul class="nav justify-content-center sticky-nav__list">
                @foreach ($comparativa as $seccion => $items)
                    <li class="nav-item sticky-nav__item">
                        <a href="#section-{{ Str::slug($seccion) }}"
                            class="nav-link sticky-nav__link">{{ ucwords(str_replace('_', ' ', $seccion)) }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- 4. COMPARATIVE CONTENT --}}
    <div class="container mt-5">
        @foreach ($comparativa as $seccion => $items)
            <section id="section-{{ Str::slug($seccion) }}" class="section-perfil mb-5 pb-5">
                <div class="dimension-header shadow-sm mb-4">
                    <h2 class="display-5 fw-bold mb-0 text-center">{{ ucwords(str_replace('_', ' ', $seccion)) }}</h2>
                </div>

                <div class="row g-4 align-items-stretch">
                    @foreach ($items as $item)
                        @php
                            $isKpi = $item['config']->tipo_visualizacion === 'kpi';
                            $hasCombined = $item['echarts_combinado'] !== null;
                        @endphp

                        <div class="col-12">
                            <div class="perfil-tarjeta">
                                <div class="perfil-tarjeta__body">
                                    {{-- Header --}}
                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                        <h3 class="perfil-tarjeta__titulo m-0">
                                            {{ $item['config']->titulo_reporte ?? $item['config']->indicador->nombre_amigable }}
                                        </h3>
                                        @if (isset($item['datos1']['metodo_calculo']) || isset($item['datos1']['fuente']))
                                            <i class="fa-solid fa-circle-info info-tooltip-trigger perfil-tarjeta__info-icon"
                                                data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                title="Metodología y Fuente"
                                                data-bs-content="<strong>Método:</strong> {{ $item['datos1']['metodo_calculo'] ?? 'No especificado' }}<br><strong>Fuente:</strong> {{ $item['datos1']['fuente'] ?? 'No especificada' }}"
                                                data-bs-html="true"></i>
                                        @endif
                                    </div>

                                    {{-- Layout Split para KPI --}}
                                    @if ($isKpi)
                                        @php
                                            // --- Resolución de valores categóricos mediante mapeo_valores ---
                                            $displayVal1 = $item['datos1']['valor_actual'] ?? ($item['datos1']['total'] ?? 'N/D');
                                            $displayVal2 = $item['datos2']['valor_actual'] ?? ($item['datos2']['total'] ?? 'N/D');

                                            // Buscar mapeo en las variables (prioridad: ajustes_visuales > variable.mapeo)
                                            $mapeoActivo = null;
                                            $ajustesVis = $item['config']->ajustes_visuales ?? [];
                                            if (isset($ajustesVis['mapping']) && is_array($ajustesVis['mapping'])) {
                                                $mapeoActivo = $ajustesVis['mapping'];
                                            } elseif (
                                                isset($item['datos1']['variables']) &&
                                                is_array($item['datos1']['variables']) &&
                                                !empty($item['datos1']['variables'])
                                            ) {
                                                $firstVar = reset($item['datos1']['variables']);
                                                if (!empty($firstVar['mapeo']) && is_array($firstVar['mapeo'])) {
                                                    $mapeoActivo = $firstVar['mapeo'];
                                                }
                                            }
                                            // Fallback: buscar en datos2 si datos1 no tenía mapeo
                                            if (
                                                !$mapeoActivo &&
                                                isset($item['datos2']['variables']) &&
                                                is_array($item['datos2']['variables']) &&
                                                !empty($item['datos2']['variables'])
                                            ) {
                                                $firstVar2 = reset($item['datos2']['variables']);
                                                if (!empty($firstVar2['mapeo']) && is_array($firstVar2['mapeo'])) {
                                                    $mapeoActivo = $firstVar2['mapeo'];
                                                }
                                            }

                                            if ($mapeoActivo && $item['datos1']) {
                                                // Intentar mapear valor del municipio 1
                                                $rawTotal1 = $item['datos1']['total'] ?? null;
                                                if ($rawTotal1 !== null) {
                                                    $key1 = (int) $rawTotal1;
                                                    if (array_key_exists($key1, $mapeoActivo)) {
                                                        $displayVal1 = $mapeoActivo[$key1];
                                                    } elseif (array_key_exists((string) $rawTotal1, $mapeoActivo)) {
                                                        $displayVal1 = $mapeoActivo[(string) $rawTotal1];
                                                    }
                                                }
                                            }

                                            if ($mapeoActivo && $item['datos2']) {
                                                // Intentar mapear valor del municipio 2
                                                $rawTotal2 = $item['datos2']['total'] ?? null;
                                                if ($rawTotal2 !== null) {
                                                    $key2 = (int) $rawTotal2;
                                                    if (array_key_exists($key2, $mapeoActivo)) {
                                                        $displayVal2 = $mapeoActivo[$key2];
                                                    } elseif (array_key_exists((string) $rawTotal2, $mapeoActivo)) {
                                                        $displayVal2 = $mapeoActivo[(string) $rawTotal2];
                                                    }
                                                }
                                            }
                                        @endphp
                                        <div class="row g-4 align-items-center justify-content-center my-2">
                                            {{-- Municipio 1 Value --}}
                                            <div class="col-5 text-center border-end">
                                                <span
                                                    class="badge bg-gold mb-2 text-uppercase">{{ $municipio1->nombre }}</span>
                                                <h4 class="perfil-tarjeta__kpi-value text-vino">
                                                    {{ $displayVal1 }}
                                                </h4>
                                                @if (isset($item['datos1']['variables'][0]['unidad']))
                                                    <small
                                                        class="text-muted d-block mt-1">{{ $item['datos1']['variables'][0]['unidad'] }}</small>
                                                @endif
                                            </div>

                                            {{-- Middle VS Badge --}}
                                            <div class="col-2 text-center">
                                                @php
                                                    $val1 = (float) ($item['datos1']['total'] ?? 0);
                                                    $val2 = (float) ($item['datos2']['total'] ?? 0);
                                                    $unidadRaw = $item['datos1']['variables'][0]['unidad'] ?? '';

                                                    $isCategorical = false;
                                                    // Si el valor ya fue mapeado a texto, es categórico
                                                    if ($mapeoActivo) {
                                                        $isCategorical = true;
                                                    } elseif (
                                                        isset($item['datos1']['valor_actual']) &&
                                                        !is_numeric(
                                                            str_replace(
                                                                [',', '$', '%', ' '],
                                                                '',
                                                                $item['datos1']['valor_actual'],
                                                            ),
                                                        )
                                                    ) {
                                                        $isCategorical = true;
                                                    }
                                                @endphp
                                                @if (!$isCategorical && $val1 > 0 && $val2 > 0)
                                                    @php
                                                        $diff = $val1 - $val2;
                                                        $pct = $val2 > 0 ? ($diff / $val2) * 100 : 0;
                                                        $polarity = $item['config']->indicador->polaridad ?? 'neutral';

                                                        $label = '';
                                                        $colorClass = 'text-muted';
                                                        if (in_array($polarity, ['asendente', 'ascendente'])) {
                                                            if ($pct > 0.1) {
                                                                $label = '+' . number_format($pct, 1) . '%';
                                                                $colorClass = 'text-success fw-bold';
                                                            } elseif ($pct < -0.1) {
                                                                $label = number_format($pct, 1) . '%';
                                                                $colorClass = 'text-danger fw-bold';
                                                            } else {
                                                                $label = '=';
                                                            }
                                                        } elseif ($polarity === 'descendente') {
                                                            if ($pct < -0.1) {
                                                                $label = number_format($pct, 1) . '%'; // Municipio 1 has LESS (better)
                                                                $colorClass = 'text-success fw-bold';
                                                            } elseif ($pct > 0.1) {
                                                                $label = '+' . number_format($pct, 1) . '%'; // Municipio 1 has MORE (worse)
                                                                $colorClass = 'text-danger fw-bold';
                                                            } else {
                                                                $label = '=';
                                                            }
                                                        } else {
                                                            $label =
                                                                ($pct >= 0 ? '+' : '') . number_format($pct, 1) . '%';
                                                        }
                                                    @endphp
                                                    <div
                                                        class="comparar-kpi__diff-badge shadow-sm px-2 py-1 rounded bg-light border">
                                                        <span
                                                            class="{{ $colorClass }} small">{{ $label }}</span>
                                                    </div>
                                                @else
                                                    <div class="comparar-kpi__vs-mid text-muted small">vs</div>
                                                @endif
                                            </div>

                                            {{-- Municipio 2 Value --}}
                                            <div class="col-5 text-center border-start">
                                                <span
                                                    class="badge bg-color-5 mb-2 text-uppercase text-white">{{ $municipio2->nombre }}</span>
                                                <h4 class="perfil-tarjeta__kpi-value text-secondary">
                                                    {{ $displayVal2 }}
                                                </h4>
                                                @if (isset($item['datos2']['variables'][0]['unidad']))
                                                    <small
                                                        class="text-muted d-block mt-1">{{ $item['datos2']['variables'][0]['unidad'] }}</small>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Sparklines (Side-by-Side if available) --}}
                                        @if (isset($item['datos1']['tendencia']) &&
                                                count($item['datos1']['tendencia']) > 1 &&
                                                (isset($item['datos2']['tendencia']) && count($item['datos2']['tendencia']) > 1))
                                            <div class="row mt-4 pt-3 border-top justify-content-center">
                                                <div class="col-5 text-center">
                                                    <div class="perfil-tarjeta__sparkline mx-auto"
                                                        id="sparkline-{{ $item['config']->id }}-1"
                                                        data-chart-id="{{ $item['config']->id }}" data-muni="1"></div>
                                                </div>
                                                <div class="col-2 text-center text-muted small">Tendencia Histórica</div>
                                                <div class="col-5 text-center">
                                                    <div class="perfil-tarjeta__sparkline mx-auto"
                                                        id="sparkline-{{ $item['config']->id }}-2"
                                                        data-chart-id="{{ $item['config']->id }}" data-muni="2"></div>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Layout Split/Combined para Gráficos --}}
                                    @else
                                        @if ($hasCombined)
                                            <div class="perfil-tarjeta__chart-wrapper perfil-tarjeta__chart-wrapper--full">
                                                <div class="perfil-tarjeta__skeleton"
                                                    id="skeleton-combined-{{ $item['config']->id }}">
                                                    <div class="spinner-border perfil-tarjeta__spinner" role="status">
                                                        <span class="visually-hidden">Cargando gráfico unificado...</span>
                                                    </div>
                                                </div>
                                                <div class="perfil-tarjeta__chart-box lazy-chart-combined perfil-tarjeta__chart-box--full"
                                                    id="chart-combined-{{ $item['config']->id }}"
                                                    data-chart-id="{{ $item['config']->id }}"></div>
                                            </div>
                                        @else
                                            {{-- Renderizar los dos gráficos separados lado a lado --}}
                                            <div class="row g-4 mt-2">
                                                <div class="col-md-6 border-end">
                                                    <h5 class="text-center small fw-bold text-uppercase text-vino mb-3">
                                                        {{ $municipio1->nombre }}</h5>
                                                    <div
                                                        class="perfil-tarjeta__chart-wrapper perfil-tarjeta__chart-wrapper--half">
                                                        <div class="perfil-tarjeta__skeleton"
                                                            id="skeleton-sep1-{{ $item['config']->id }}">
                                                            <div class="spinner-border perfil-tarjeta__spinner"
                                                                role="status">
                                                                <span class="visually-hidden">Cargando gráfico...</span>
                                                            </div>
                                                        </div>
                                                        <div class="perfil-tarjeta__chart-box lazy-chart-sep perfil-tarjeta__chart-box--half"
                                                            id="chart-sep1-{{ $item['config']->id }}"
                                                            data-chart-id="{{ $item['config']->id }}" data-muni="1">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5
                                                        class="text-center small fw-bold text-uppercase text-secondary mb-3">
                                                        {{ $municipio2->nombre }}</h5>
                                                    <div
                                                        class="perfil-tarjeta__chart-wrapper perfil-tarjeta__chart-wrapper--half">
                                                        <div class="perfil-tarjeta__skeleton"
                                                            id="skeleton-sep2-{{ $item['config']->id }}">
                                                            <div class="spinner-border perfil-tarjeta__spinner"
                                                                role="status">
                                                                <span class="visually-hidden">Cargando...</span>
                                                            </div>
                                                        </div>
                                                        <div class="perfil-tarjeta__chart-box lazy-chart-sep perfil-tarjeta__chart-box--half"
                                                            id="chart-sep2-{{ $item['config']->id }}"
                                                            data-chart-id="{{ $item['config']->id }}" data-muni="2">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection

@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script>
        window.ComparadorConfig = {
            geojsonUrl: "{{ asset('geojson/municipios_puebla_slim.geojson ') }}",
            municipio1: {
                nombre: "{{ strtoupper($municipio1->nombre) }}",
                slug: "{{ $municipio1->slug }}",
                cvegeo: "{{ $municipio1->cvegeo }}"
            },
            municipio2: {
                nombre: "{{ strtoupper($municipio2->nombre) }}",
                slug: "{{ $municipio2->slug }}",
                cvegeo: "{{ $municipio2->cvegeo }}"
            },
            comparativaData: @json($comparativa)
        };
    </script>
    <script src="{{ asset('js/comparar.js') }}?v={{ time() }}"></script>
@endsection
