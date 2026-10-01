@extends('layouts.plantilla')

@section('title', 'Inicio')
@section('meta-description', 'Página principal del Portal de Información Municipal y Regional del Estado de Puebla')
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection
@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
@endsection

@section('content')
    @php
        $indicadoresSecundarios = array_slice($indicadoresDestacados, 0, 4);
    @endphp

    <section class="editorial-hero editorial-hero--landscape text-white">
        <div class="editorial-hero__overlay"></div>
        <div class="container editorial-hero__content text-center">
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">
                    <span class="editorial-hero__eyebrow">
                        Puebla, territorio en cifras
                    </span>
                    <h1>
                        Portal de Información Municipal y Regional
                    </h1>
                    <p class="editorial-hero__lead">Información estadística y geográfica para conocer, comparar y tomar
                        decisiones sobre el estado.</p>
                    <div id="explora" class="editorial-hero__search-panel">
                        <p class="editorial-hero__search-label"><i class="fas fa-search me-2" aria-hidden="true"></i>Explora
                            Puebla en datos</p>
                        <div class="omnisearch-container w-100">
                            <label for="omnisearch-input" class="visually-hidden">Busca municipios, indicadores o
                                regiones</label>
                            <select id="omnisearch-input" placeholder="Busca municipios, indicadores o regiones"></select>
                        </div>
                        <div class="hero-search-examples" aria-label="Búsquedas sugeridas">
                            <span>Prueba con:</span>
                            <span class="hero-search-suggestions">
                                <button type="button" class="hero-search-chip" data-search-term="Puebla">Puebla</button>
                                <button type="button" class="hero-search-chip" data-search-term="Población total">Población
                                    total</button>
                                <button type="button" class="hero-search-chip" data-search-term="Sierra Norte">Sierra
                                    Norte</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="editorial-links-section">
        <div class="container">
            <nav class="editorial-links" aria-label="Explora el portal">
                <a href="{{ route('ficha-municipal.index') }}"><i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    Municipios</a>
                <a href="{{ route('banco-indicadores.index') }}"><i class="fas fa-chart-line" aria-hidden="true"></i>
                    Indicadores</a>
                <a href="{{ route('regiones.estatal.perfil') }}"><i class="fas fa-map" aria-hidden="true"></i> Perfiles
                    regionales</a>
                <a href="{{ route('datos-abiertos.index') }}"><i class="fas fa-download" aria-hidden="true"></i> Datos
                    abiertos</a>
            </nav>
        </div>
    </section>

    <section class="editorial-data-section">
        <div class="container">
            <div class="editorial-section-heading">
                <div>
                    <span class="section-eyebrow">PUEBLA HOY</span>
                    <h2>Indicadores para entender el presente</h2>
                </div>
                <a href="{{ route('banco-indicadores.index') }}">Explorar todos <i class="fas fa-arrow-right ms-1"
                        aria-hidden="true"></i></a>
            </div>

            @if (count($indicadoresSecundarios))
                <div class="row g-0 editorial-data-grid">
                    @foreach ($indicadoresSecundarios as $indicador)
                        <article class="col-sm-6 col-lg-3 editorial-data-card">
                            <a href="{{ $indicador['link'] }}">
                                <p class="editorial-data-card__title">{{ $indicador['titulo'] }}</p>
                                <p class="editorial-data-card__value">{{ $indicador['valor'] }}</p>
                                <p class="editorial-data-card__year">{{ $indicador['anio'] }}</p>
                                <div class="sparkline-chart" data-series="{{ json_encode($indicador['sparkline']) }}"
                                    aria-hidden="true"></div>
                            </a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="featured-empty-state text-center mx-auto">
                    <i class="fas fa-chart-line fa-2x custom-text-primary mb-3" aria-hidden="true"></i>
                    <h3 class="h5 fw-bold">Próximamente habrá indicadores destacados</h3>
                    <p class="text-muted mb-3">Consulta el banco para explorar la información disponible.</p>
                    <a href="{{ route('banco-indicadores.index') }}" class="btn btn-custom-primary btn-sm">Ir al Banco de
                        Indicadores</a>
                </div>
            @endif
        </div>
    </section>

    <section class="editorial-guides-section">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-5">
                    <span class="section-eyebrow">EXPLORA EL TERRITORIO</span>
                    <h2>De la cifra al contexto local.</h2>
                    <p>Consulta perfiles municipales y regionales para comparar realidades, identificar tendencias y
                        profundizar en cada territorio.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-6"><a href="{{ route('ficha-municipal.index') }}"
                                class="editorial-guide-link"><i class="fas fa-city"
                                    aria-hidden="true"></i><span><strong>Perfiles municipales</strong>Conoce cada
                                    municipio</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
                        <div class="col-md-6"><a href="{{ route('regiones.estatal.perfil') }}"
                                class="editorial-guide-link"><i class="fas fa-draw-polygon"
                                    aria-hidden="true"></i><span><strong>Perfiles regionales</strong>Observa el
                                    territorio</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="territory-section">
        <div class="container">
            <div class="editorial-section-heading">
                <div>
                    <span class="section-eyebrow">COBERTURA TERRITORIAL</span>
                    <h2>Una misma realidad, cuatro escalas.</h2>
                </div>
                <p>Encuentra información desde el estado hasta sus regiones.</p>
            </div>
            <div class="row g-0 territory-grid">
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('regiones.estatal.perfil') }}" class="territory-card">
                        <span class="territory-card__number">01</span>
                        <strong>Estado</strong>
                        <span>Panorama general de Puebla</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('ficha-municipal.index') }}" class="territory-card">
                        <span class="territory-card__number">02</span>
                        <strong>Municipios</strong>
                        <span>Consulta cada uno de los 217 </span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="#explora" class="territory-card">
                        <span class="territory-card__number">03</span>
                        <strong>Microrregiones</strong>
                        <span>Encuéntralas en el buscador</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="#explora" class="territory-card">
                        <span class="territory-card__number">04</span>
                        <strong>Macrorregiones</strong>
                        <span>Explora su información</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="analysis-section">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-4">
                    <span class="section-eyebrow">HERRAMIENTAS DE ANALISIS</span>
                    <h2>Convierte datos en perspectiva.</h2>
                    <p>El portal reúne recursos para observar tendencias y relacionar la información con el territorio.</p>
                </div>
                <div class="col-lg-8">
                    <div class="analysis-list">
                        <a href="{{ route('banco-indicadores.index') }}"><span>01</span><strong>Mapas temáticos</strong><small>Visualiza indicadores sobre el territorio.</small><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        <a href="{{ route('banco-indicadores.index') }}"><span>02</span><strong>Series históricas</strong><small>Identifica cambios a través del tiempo.</small><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        <a href="{{ route('ficha-municipal.index') }}"><span>03</span><strong>Comparación territorial</strong><small>Consulta perfiles y contextos municipales.</small><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        <a href="{{ route('datos-abiertos.index') }}"><span>04</span><strong>Exportación de datos</strong><small>Reutiliza información para tus análisis.</small><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="open-data-section">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span>DATOS ABIERTOS</span>
                    <h2>La información pública también se puede reutilizar.</h2>
                    <p>Consulta y descarga los datos disponibles para investigación, análisis y proyectos propios.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('datos-abiertos.index') }}">Ir a datos abiertos <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </section>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // --- OMNISEARCH: Buscador unificado ---
        const omniInput = document.getElementById('omnisearch-input');

        if (omniInput) {
            const loadSearchResults = (query) => fetch(
                `{{ route('api.omnisearch') }}?q=${encodeURIComponent(query)}`
            ).then((response) => response.json());

            const tomSelect = new TomSelect(omniInput, {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                maxItems: 1,
                create: false,
                // Renderizado custom con íconos y badges de tipo
                render: {
                    option: function(data, escape) {
                        const typeColors = {
                            'Municipio': '#861e34',
                            'Indicador': '#0c312d',
                            'Microrregión': '#c5a059',
                            'Macrorregión': '#2c5f2d',
                            'Estado': '#5f1b2d',
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

                    loadSearchResults(query)
                        .then((results) => callback(results))
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

            const suggestionContainer = document.querySelector('.hero-search-suggestions');
            const searchSuggestions = [
                'Puebla', 'Población total', 'Sierra Norte', 'Tehuacán',
                'Viviendas', 'Angelópolis', 'Educación', 'Salud',
            ];
            let suggestionStart = 0;

            const renderSuggestions = () => {
                const visibleSuggestions = Array.from({
                        length: 3
                    }, (_, index) =>
                    searchSuggestions[(suggestionStart + index) % searchSuggestions.length]
                );

                suggestionContainer.classList.add('is-leaving');

                window.setTimeout(() => {
                    suggestionContainer.replaceChildren(...visibleSuggestions.map((suggestion) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'hero-search-chip';
                        button.dataset.searchTerm = suggestion;
                        button.textContent = suggestion;
                        return button;
                    }));
                    suggestionContainer.classList.remove('is-leaving');
                }, 180);
            };

            suggestionContainer.addEventListener('click', (event) => {
                const button = event.target.closest('[data-search-term]');
                if (button) {
                    const searchTerm = button.dataset.searchTerm;
                    tomSelect.setTextboxValue(searchTerm);
                    tomSelect.clearOptions();
                    tomSelect.loading++;
                    tomSelect.wrapper.classList.add(tomSelect.settings.loadingClass);
                    tomSelect.focus();

                    loadSearchResults(searchTerm)
                        .then((results) => tomSelect.addOptions(results))
                        .catch(() => {})
                        .finally(() => {
                            tomSelect.loading--;
                            tomSelect.wrapper.classList.remove(tomSelect.settings.loadingClass);
                            tomSelect.refreshOptions(true);
                        });
                }
            });

            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                window.setInterval(() => {
                    suggestionStart = (suggestionStart + 3) % searchSuggestions.length;
                    renderSuggestions();
                }, 5000);
            }
        }

        // Inicializar los mini-gráficos de los indicadores.
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
