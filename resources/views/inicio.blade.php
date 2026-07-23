@extends('layouts.plantilla')

@section('title', 'Inicio')
@section('meta-description', 'Página principal del Portal de Información Municipal y Regional del Estado de Puebla')
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/flickity@2/dist/flickity.min.css">
@endsection
@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js"></script>
@endsection

@section('content')
    {{-- Hero Section --}}
    <section class="hero-section text-white text-center">
        <div class="hero-overlay"></div>
        <div class="container d-flex flex-column justify-content-center h-100">
            <h1 class="display-4 fw-bold mb-3">Portal de Información Municipal y Regional del Estado de Puebla</h1>
            <p class="lead mb-5">Información estadística y geográfica para la toma de decisiones.</p>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 rounded-4 shadow-lg p-4" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                        <h5 class="fw-bold mb-2 text-white">
                            <i class="fas fa-search me-2"></i>Explora Puebla en Datos
                        </h5>
                        <p class="text-white-50 small mb-3">
                            Busca municipios, indicadores estadísticos o regiones del estado.
                        </p>
                        <div class="omnisearch-container mx-auto w-100">
                            <select id="omnisearch-input" placeholder="Ej: Puebla, Población total, Sierra Norte..."></select>
                        </div>

                        {{-- Quick access links --}}
                        <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
                            <a href="{{ route('ficha-municipal.index') }}" class="omnisearch-quicklink">
                                <i class="fas fa-map-marker-alt me-1"></i>Municipios
                            </a>
                            <a href="{{ route('banco-indicadores.index') }}" class="omnisearch-quicklink">
                                <i class="fas fa-chart-line me-1"></i>Banco de Indicadores
                            </a>
                            <a href="{{ route('datos-abiertos.index') }}" class="omnisearch-quicklink">
                                <i class="fas fa-download me-1"></i>Datos Abiertos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- Sección de Funcionalidades --}}
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Descubre el Poder de los Datos</h2>
                <p class="lead text-muted">Visualiza, compara y utiliza la información a tu favor.</p>
            </div>
            <div class="row text-center g-4">
                {{-- Columna 1: Visualiza --}}
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-4">
                            <i class="fas fa-chart-pie fa-3x custom-text-primary mb-3"></i>
                            <h3 class="card-title h5 fw-bold">Visualiza</h3>
                            <p class="card-text text-muted">
                                Explora decenas de indicadores a través de gráficos interactivos y mapas temáticos para
                                entender la realidad de cada municipio.
                            </p>
                        </div>
                    </div>
                </div>
                {{-- Columna 2: Compara --}}
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-4">
                            <i class="fas fa-layer-group fa-3x custom-text-primary mb-3"></i>
                            <h3 class="card-title h5 fw-bold">Compara</h3>
                            <p class="card-text text-muted">
                                Analiza tendencias y compara datos entre diferentes municipios o regiones para obtener una
                                perspectiva única y contextualizada.
                            </p>
                        </div>
                    </div>
                </div>
                {{-- Columna 3: Exporta --}}
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-4">
                            <i class="fas fa-download fa-3x custom-text-primary mb-3"></i>
                            <h3 class="card-title h5 fw-bold">Exporta</h3>
                            <p class="card-text text-muted">
                                Descarga la información que necesitas en formatos abiertos (CSV) para tus propios análisis,
                                reportes o investigaciones.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- Sección de Datos Destacados --}}
    {{-- Sección Carrusel de Indicadores Destacados --}}
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Puebla en Cifras</h2>
                <p class="lead text-muted">Un vistazo rápido a los datos más relevantes del estado.</p>
            </div>

            {{-- Contenedor principal del carrusel --}}
            <div class="main-carousel" data-flickity='{ "autoPlay": true }'>
                @foreach ($indicadoresDestacados as $indicador)
                    {{-- Cada "slide" del carrusel --}}
                    <div class="carousel-cell">
                        <div class="card text-center shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">{{ $indicador['titulo'] }}
                                    ({{ $indicador['anio'] }})
                                </h6>
                                <p class="card-title display-5 fw-bold">{{ $indicador['valor'] }}</p>

                                {{-- Contenedor para el mini-gráfico --}}
                                <div class="sparkline-chart" data-series="{{ json_encode($indicador['sparkline']) }}">
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pb-3">
                                <a href="{{ $indicador['link'] }}" class="btn btn-sm btn-outline-primary">
                                    Explorar indicador
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // --- OMNISEARCH: Buscador unificado ---
        const omniInput = document.getElementById('omnisearch-input');

        if (omniInput) {
            new TomSelect(omniInput, {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                maxItems: 1,
                create: false,
                // Renderizado custom con íconos y badges de tipo
                render: {
                    option: function(data, escape) {
                        const typeColors = {
                            'Municipio':     '#861e34',
                            'Indicador':     '#0c312d',
                            'Microrregión':  '#c5a059',
                            'Macrorregión':  '#2c5f2d',
                            'Estado':        '#5f1b2d',
                        };
                        const color = typeColors[data.type] || '#666';
                        return `<div class="d-flex align-items-center gap-2 py-1 px-1">
                            <span class="omnisearch-icon" style="background: ${color};">
                                <i class="fas ${escape(data.icon)}"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size: 0.9rem;">${escape(data.text)}</div>
                            </div>
                            <span class="omnisearch-type-badge" style="color: ${color}; border-color: ${color};">${escape(data.type)}</span>
                        </div>`;
                    },
                    item: function(data, escape) {
                        return `<div><i class="fas ${escape(data.icon)} me-1"></i>${escape(data.text)} <small class="text-muted">(${escape(data.type)})</small></div>`;
                    },
                    no_results: function() {
                        return '<div class="no-results p-3 text-center text-muted"><i class="fas fa-search me-1"></i>Sin resultados. Intenta con otro término.</div>';
                    }
                },
                load: function(query, callback) {
                    if (query.length < 2) return callback();

                    fetch(`{{ route('api.omnisearch') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(json => callback(json))
                        .catch(() => callback());
                },
                onChange: function(value) {
                    if (!value) return;
                    const item = this.options[value];
                    if (item && item.url) {
                        window.location.href = item.url;
                    }
                }
            });
        }

        // Carrusel de indicadores
        const carouselElem = document.querySelector('.main-carousel');
        if (carouselElem) {
            const flkty = new Flickity(carouselElem, {
                cellAlign: 'left',
                contain: true,
                pageDots: false, // Opcional: quita los puntos de navegación
                wrapAround: true, // Opcional: hace el carrusel infinito
                autoPlay: true,
            });
        }

        // --- 2. INICIALIZAR LOS MINI-GRÁFICOS (SPARKLINES) ---
        const sparklineCharts = document.querySelectorAll('.sparkline-chart');
        sparklineCharts.forEach(chartEl => {
            const seriesData = JSON.parse(chartEl.dataset.series);
            chartEl.style.height = '80px';
            chartEl.style.width = '100%';

            const chart = echarts.init(chartEl);
            const options = {
                grid: {
                    left: 0,
                    right: 0,
                    top: 10,
                    bottom: 0
                },
                xAxis: {
                    type: 'category',
                    show: false
                },
                yAxis: {
                    type: 'value',
                    show: false,
                    min: 'dataMin'
                },
                tooltip: {
                    trigger: 'axis',
                    formatter: function(params) {
                        return new Intl.NumberFormat().format(params[0].value);
                    }
                },
                series: [{
                    data: seriesData,
                    type: 'line',
                    smooth: 0.3,
                    symbol: 'none',
                    lineStyle: {
                        color: '#0c312d',
                        width: 2
                    },
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                            offset: 0,
                            color: '#0c312d44'
                        }, {
                            offset: 1,
                            color: 'transparent'
                        }])
                    }
                }]
            };
            chart.setOption(options);
            window.addEventListener('resize', function() {
                chart.resize();
            });
        });
    });
</script>
