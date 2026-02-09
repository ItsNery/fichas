@extends('layouts.plantilla')

@section('title', 'Inicio')
@section('meta-description', 'Página principal del Portal de Información Municipal y Regional del Estado de Puebla')
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/flickity@2/dist/flickity.min.css">
@endsection
@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js"></script>
@endsection

@section('content')
    {{-- Hero Section --}}
    <section class="hero-section text-white text-center">
        <div class="hero-overlay"></div>
        <div class="container d-flex flex-column justify-content-center h-100">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <h1 class="display-4 fw-bold">Portal de Información Municipal y Regional del Estado de Puebla</h1>
                    <p class="lead my-4">Explora, compara y descarga
                        información clave de tu municipio o región.</p>

                    {{-- Botón de Llamada a la Acción Principal --}}
                    <a href="{{ route('fichas.index') }}" class="btn btn-custom-primary btn-lg px-5 py-3 mb-4">
                        <i class="fas fa-chart-line me-2"></i> Iniciar Exploración
                    </a>

                    {{-- Búsqueda Rápida de Municipios --}}
                    <div class="quick-search-container mx-auto">
                        <label for="municipio-quick-search" class="form-label mb-2">O busca directamente un
                            municipio:</label>
                        <select id="municipio-quick-search" placeholder="Escribe el nombre de un municipio..."></select>
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
        // buscador de municipios
        const quickSearch = document.getElementById('municipio-quick-search');

        if (quickSearch) {
            new TomSelect(quickSearch, {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                maxItems: 1,
                create: true,
                // Activa la búsqueda remota
                load: function(query, callback) {
                    if (!query.length) return callback();

                    fetch(`{{ route('api.municipios.search') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(json => {
                            callback(json);
                        }).catch(() => {
                            callback();
                        });
                },
                // Redirige al usuario cuando selecciona un municipio
                onChange: function(value) {
                    if (value) {
                        // Reemplaza 'fichas.resumen' con el nombre de tu ruta de resumen municipal
                        let url =
                            "{{ route('fichas.resumen', ['municipio' => 'ID_PLACEHOLDER']) }}";
                        window.location.href = url.replace('ID_PLACEHOLDER', value);
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

            const options = {
                series: [{
                    data: seriesData
                }],
                chart: {
                    type: 'line',
                    height: 80,
                    sparkline: {
                        enabled: true
                    },
                },
                colors: ['#0c312d'],
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                tooltip: {
                    fixed: {
                        enabled: false
                    },
                    x: {
                        show: false
                    },
                    y: {
                        title: {
                            formatter: (seriesName) => ''
                        },
                        formatter: (value) => new Intl.NumberFormat().format(value)
                    },
                    marker: {
                        show: false
                    }
                }
            };

            const chart = new ApexCharts(chartEl, options);
            chart.render();
        });
    });
</script>
