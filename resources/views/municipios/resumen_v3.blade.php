@extends('layouts.plantilla')
@section('title', 'Ficha Municipal: ' . $municipio->nombre)
@section('meta-description', 'Resumen ejecutivo de indicadores para el municipio de ' . $municipio->nombre)
@section('canonical-url', url()->current())

@section('css')
@endsection

@section('content')

    {{-- 1. HERO SECTION --}}
    <section class="hero-ficha"
        style="background-image: url('{{ $municipio->banner_image_url ?? asset(config('regionalizacion.fallback_hero')) }}')">
        <div class="hero-ficha__capa-gradiente"></div>

        <div class="container hero-ficha__contenedor">

            <div class="container d-flex justify-content-center align-items-center">
                <nav aria-label="breadcrumb hero-ficha__navegacion">
                    <ol class="hero-ficha__ruta breadcrumb">
                        <li class="hero-ficha__ruta-item breadcrumb-item">
                            <a href="{{ url('/') }}" class="hero-ficha__ruta-enlace text-white">
                                Inicio
                            </a>
                        </li>
                        <li class="hero-ficha__ruta-item breadcrumb-item">
                            <a href="{{ url('/banco-indicadores/directorio') }}" class="hero-ficha__ruta-enlace text-white">
                                Directorio
                            </a>
                        </li>
                        <li class="hero-ficha__ruta-item breadcrumb-item text-white active" aria-current="page">
                            {{ $municipio->nombre ?? 'Municipio' }}
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="row align-items-center">

                <div class="col-lg-7">
                    <span class="hero-ficha__etiqueta badge mb-3 px-3 py-2 text-uppercase ">
                        Ficha Municipal
                    </span>
                    <a href="{{ route('ficha-municipal.v3.pdf', $municipio->slug) }}"
                       class="btn btn-outline-light btn-sm fw-bold px-3 py-1 rounded-pill ms-2"
                       target="_blank">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </a>

                    <h1 class="hero-ficha__titulo">{{ $municipio->nombre }}</h1>

                    <p class="hero-ficha__subtitulo">
                        {{ $municipio->microrregion->macrorregion->nombre }} | {{ $municipio->microrregion->nombre }}
                    </p>

                    <div class="hero-ficha__datos-gobierno mt-5 d-flex gap-4">
                        <div class="hero-ficha__dato">
                            <span class="hero-ficha__dato-etiqueta">Cabecera Municipal</span>
                            <span class="hero-ficha__dato-valor">{{ $municipio->cabecera ?? $municipio->nombre }}</span>
                        </div>

                        <div class="hero-ficha__dato hero-ficha__dato--separador">
                            <span class="hero-ficha__dato-etiqueta">Clima predominante</span>
                            <span class="hero-ficha__dato-valor">{{ $municipio->clima ?? 'Información no disponible' }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 d-none d-lg-block">
                    <div class="hero-ficha__mapa-contenedor">
                        <div id="hero-map" class="hero-ficha__mapa-visual" data-cvegeo="{{ $municipio->cvegeo }}">
                            {{-- Inyectaremos silueta del mapa aquí --}}
                        </div>
                    </div>
                </div>
                <div class="col-md-12 hero-ficha__descripcion">
                    @if ($wikiSummary)
                        <p class="hero-ficha__extracto">
                            {{ $wikiSummary['extract'] }}
                        </p>
                        <div class="d-flex align-items-center">
                            <a href="{{ $wikiSummary['content_urls']['desktop']['page'] }}" target="_blank"
                                class="hero-ficha__fuente-wiki">
                                <i class="fab fa-wikipedia-w"></i> Conoce más en Wikipedia
                            </a>
                            <span class="hero-ficha__info-disclaimer"
                                title="Este extracto es informativo y se obtiene automáticamente de Wikipedia.">
                                <i class="fas fa-info-circle"></i>
                            </span>
                        </div>
                    @endif
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

    {{-- 2. ICON BAR --}}
    <section class="barra-indicadores shadow-lg">
        <div class="container">
            <div class="row">

                <div class="col-md-3 barra-indicadores__item">
                    <i class="fas fa-users barra-indicadores__icono"></i>
                    <span class="barra-indicadores__valor">{{ number_format($poblacionTotal) }}</span>
                    <span class="barra-indicadores__etiqueta">Población Total</span>
                </div>

                <div class="col-md-3 barra-indicadores__item">
                    <i class="fas fa-chart-line barra-indicadores__icono"></i>
                    <span class="barra-indicadores__valor">{{ $gradoMarginacion }}</span>
                    <span class="barra-indicadores__etiqueta">Grado de Marginación</span>
                </div>

                <div class="col-md-3 barra-indicadores__item">
                    <i class="fas fa-coins barra-indicadores__icono"></i>
                    <span class="barra-indicadores__valor">${{ number_format($presupuestoTotal, 2) }}k</span>
                    <span class="barra-indicadores__etiqueta">Presupuesto {{ $anioPresupuesto }} <br> (FORTAMUN +
                        FAISMUN)</span>
                </div>

                <div class="col-md-3 barra-indicadores__item">
                    <i class="fas fa-expand-arrows-alt barra-indicadores__icono"></i>
                    <span class="barra-indicadores__valor">
                        {{ $superficieKm2 > 0 ? number_format($superficieKm2, 2) : '--' }} km²
                    </span>
                    <span class="barra-indicadores__etiqueta">Superficie Territorial</span>
                </div>

            </div>
        </div>
    </section>

    <section class="container py-4">
        <div class="row">
            {{-- SIDEBAR NAVIGATION (DIMENSIONES) --}}
            <div class="col-md-4 d-none d-md-block">
                <aside class="barra-lateral-dimensiones">
                    <h5 class="text-uppercase letter-spacing-2">Dimensiones</h5>
                    <nav id="sidebar-nav" class="nav flex-column">
                        @foreach ($datosAgrupados as $dimensionData)
                            <a class="nav-link d-flex align-items-center" href="#dim-{{ $dimensionData['slug'] }}">
                                <span class="nav-dot"></span>
                                {{ $dimensionData['nombre'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="mt-5 p-4 rounded-4 bg-light shadow-sm">
                        <p class="small text-muted mb-0">Esta ficha se actualiza automáticamente con los últimos datos
                            validados por el sistema.</p>
                    </div>
                </aside>
            </div>

            {{-- MAIN CONTENT --}}
            <div class="col-md-8">
                <div class="main-content-wrapper">

                    @foreach ($datosAgrupados as $dimensionData)
                        <div id="dim-{{ $dimensionData['slug'] }}" class="dimension-bloque mb-4">
                            {{-- DIMENSION BANNER --}}
                            <header class="banner-dimension"
                                style="background-image: url('{{ asset('img/fondos/' . $dimensionData['slug'] . '.webp') }}')">
                                <h2 class="banner-dimension__titulo">{{ $dimensionData['nombre'] }}</h2>
                            </header>

                            {{-- SUB-NAV FOR TEMATICAS --}}
                            <nav class="sub-navegacion sticky-top py-2 mb-3">
                                <div class="container-fluid">
                                    <ul class="nav nav-pills" id="nav-tem-{{ $dimensionData['slug'] }}">
                                        @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
                                            <li class="nav-item">
                                                <a class="nav-link py-1 px-3 small nav-link-tematica"
                                                    href="#tem-{{ Str::slug($tematica) }}"
                                                    data-parent-dim="dim-{{ $dimensionData['slug'] }}">
                                                    {{ $tematica }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </nav>

                            @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
                                <section id="tem-{{ Str::slug($tematica) }}" class="seccion-editorial border-bottom"
                                    data-dim-slug="{{ $dimensionData['slug'] }}">
                                    <div class="row align-items-center">
                                        <div class="col-lg-5 mb-4 mb-lg-0">
                                            <h3 class="seccion-editorial__titulo">{{ $tematica }}</h3>
                                            <div class="seccion-editorial__divisor"></div>
                                            <div class="seccion-editorial__narrativa">
                                                @foreach ($kpis as $kpi)
                                                    @if (!empty($kpi['narrativa']))
                                                        <p>{!! $kpi['narrativa'] !!}</p>
                                                    @else
                                                        <p class="text-muted small">No hay resumen narrativo configurado
                                                            para el indicador {{ $kpi['nombre'] }}.</p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <div class="seccion-editorial__grafica-contenedor">
                                                <div id="main-chart-{{ Str::slug($tematica) }}"
                                                    style="width: 100%; height: 100%;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- BENTO GRID --}}
                                    <div class="row g-4 mt-4">
                                        @foreach ($kpis as $kpi)
                                            <div class="col-md-6 col-lg-4">
                                                @include('municipios.components.kpi-card', [
                                                    'kpi' => $kpi,
                                                    'dimensionData' => $dimensionData,
                                                ])
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </section>


@endsection

@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 1. DUAL TRACKING via Intersection Observer ---

            // Tracking Dimensions (Sidebar)
            const dimensionGroups = document.querySelectorAll('.dimension-bloque');
            const sidebarLinks = document.querySelectorAll('#sidebar-nav .nav-link');

            // Tracking Temáticas (Sub-Navs)
            const thematicSections = document.querySelectorAll('.seccion-editorial');

            const observerOptions = {
                root: null,
                rootMargin: '-150px 0px -70% 0px', // Precise activation threshold
                threshold: 0
            };

            const sectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');

                        // --- Dimension Tracking ---
                        if (entry.target.classList.contains('dimension-bloque')) {
                            sidebarLinks.forEach(link => {
                                link.classList.remove('active');
                                if (link.getAttribute('href') === `#${id}`) {
                                    link.classList.add('active');
                                }
                            });
                        }

                        // --- Temática Tracking ---
                        if (entry.target.classList.contains('seccion-editorial')) {
                            const activeThematicLink = document.querySelector(
                                `.nav-link-tematica[href="#${id}"]`);
                            if (activeThematicLink) {
                                const parentNav = activeThematicLink.closest('.nav-pills');
                                parentNav.querySelectorAll('.nav-link-tematica').forEach(l => l
                                    .classList.remove('active'));
                                activeThematicLink.classList.add('active');
                            }
                        }
                    }
                });
            }, observerOptions);

            dimensionGroups.forEach(dg => sectionObserver.observe(dg));
            thematicSections.forEach(ts => sectionObserver.observe(ts));

            // --- 2. RENDER MAIN CHARTS ---

            // --- RENDER MAIN CHARTS FOR EACH TEMATICA ---
            @foreach ($datosAgrupados as $dimensionData)
                @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
                    (function() {
                        var chartDom = document.getElementById('main-chart-{{ Str::slug($tematica) }}');
                        var myChart = echarts.init(chartDom);

                        // Elegir un KPI para la gráfica principal o usar datos agregados
                        // prettier-ignore
                        var kpi = {!! json_encode($kpis[0] ?? null) !!};
                        if (!kpi) return;

                        if (kpi.tipo_visual === 'scatter' && kpi.echarts) {
                            var scatterOption = {
                                title: {
                                    text: kpi.nombre,
                                    left: 'center',
                                    textStyle: { fontSize: 14 }
                                },
                                tooltip: { trigger: 'item' },
                                grid: { top: 60, left: 60, right: 20, bottom: 50 },
                                xAxis: {
                                    type: 'value',
                                    name: kpi.echarts.eje_x ? kpi.echarts.eje_x.titulo : 'Eje X',
                                    nameLocation: 'middle',
                                    nameGap: 30
                                },
                                yAxis: {
                                    type: 'value',
                                    name: kpi.echarts.eje_y ? kpi.echarts.eje_y.titulo : 'Eje Y',
                                    nameLocation: 'middle',
                                    nameGap: 45
                                },
                                series: kpi.echarts.series || []
                            };
                            myChart.setOption(scatterOption);
                            return;
                        }

                        if (!kpi.historial) return;

                        var historyData = JSON.parse(kpi.historial);
                        var xData = historyData.map(d => d.anio);
                        var yData = historyData.map(d => parseFloat(d.valor));

                        var option = {
                            title: {
                                text: kpi.nombre,
                                left: 'center',
                                textStyle: {
                                    fontSize: 14
                                }
                            },
                            tooltip: {
                                trigger: 'axis'
                            },
                            grid: {
                                top: 60,
                                left: 50,
                                right: 20,
                                bottom: 40
                            },
                            xAxis: {
                                type: 'category',
                                data: xData
                            },
                            yAxis: {
                                type: 'value'
                            },
                            series: [{
                                data: yData,
                                type: 'bar',
                                itemStyle: {
                                    color: '{{ $dimensionData['color'] ?? '#c5a059' }}',
                                    borderRadius: [5, 5, 0, 0]
                                },
                                emphasis: {
                                    itemStyle: {
                                        color: '#1a1a1a'
                                    }
                                }
                            }]
                        };
                        myChart.setOption(option);
                        window.addEventListener('resize', function() {
                            myChart.resize();
                        });
                    })();
                @endforeach
            @endforeach

            // --- MAPA HERO ---
            (function() {
                const chartDom = document.getElementById('hero-map');
                if (!chartDom) return;

                // Forzar dimensiones si es necesario
                chartDom.style.width = chartDom.parentElement.clientWidth + 'px';

                const myChart = echarts.init(chartDom);
                const cvegeo = chartDom.getAttribute('data-cvegeo');

                console.log("Iniciando carga de mapa para cvegeo:", cvegeo);

                fetch("{{ asset('geojson/municipios_puebla_slim.geojson') }}")
                    .then(response => {
                        if (!response.ok) throw new Error("Error al cargar GeoJSON");
                        return response.json();
                    })
                    .then(usaJson => {
                        console.log("GeoJSON cargado correctamente");
                        // ECharts necesita que cada feature tenga una propiedad 'name' para emparejar con 'data'
                        usaJson.features.forEach(f => {
                            f.properties.name = f.properties.nomgeo;
                        });

                        echarts.registerMap('puebla', usaJson);

                        // Filtrar solo el municipio actual para destacarlo
                        const feature = usaJson.features.find(f => String(f.properties.cvegeo) == String(
                            cvegeo));
                        console.log("Municipio encontrado:", feature ? feature.properties.name :
                            "NO ENCONTRADO");

                        const option = {

                            backgroundColor: 'transparent',
                            series: [{
                                type: 'map',
                                map: 'puebla',
                                roam: false,
                                layoutCenter: ['50%', '50%'],
                                layoutSize: '120%',
                                label: {
                                    show: false
                                },
                                itemStyle: {
                                    areaColor: 'rgba(255, 255, 255, 0.25)',
                                    borderColor: 'rgba(255, 255, 255, 0.6)',
                                    borderWidth: 1
                                },
                                emphasis: {
                                    disabled: false,
                                    itemStyle: {
                                        areaColor: 'rgba(255, 255, 255, 0.15)',
                                        borderColor: 'rgba(255, 255, 255, 0.4)'
                                    }
                                },
                                selectedMode: 'single',
                                data: [{
                                    name: feature ? feature.properties.name : '',
                                    selected: true,
                                    itemStyle: {
                                        areaColor: '#c5a059',
                                        opacity: 1,
                                        shadowBlur: 30,
                                        shadowColor: 'rgba(197, 160, 89, 0.8)',
                                        shadowOffsetX: 0,
                                        shadowOffsetY: 10,
                                        borderColor: '#fff',
                                        borderWidth: 2
                                    }
                                }]

                            }]
                        };
                        myChart.setOption(option);
                    });
            })();

            // --- GAUGES ---
            document.querySelectorAll('.gauge-chart').forEach(function(el) {
                var chart = echarts.init(el);
                var value = parseFloat(el.getAttribute('data-value')) || 0;
                var color = el.getAttribute('data-color');
                chart.setOption({
                    series: [{
                        type: 'gauge',
                        startAngle: 180,
                        endAngle: 0,
                        min: 0,
                        max: 100,
                        pointer: {
                            show: false
                        },
                        progress: {
                            show: true,
                            roundCap: true,
                            itemStyle: {
                                color: color
                            }
                        },
                        axisLine: {
                            lineStyle: {
                                width: 8,
                                color: [
                                    [1, '#f0f0f0']
                                ]
                            }
                        },
                        splitLine: {
                            show: false
                        },
                        axisTick: {
                            show: false
                        },
                        axisLabel: {
                            show: false
                        },
                        data: [{
                            value: value,
                            detail: {
                                fontSize: 18,
                                fontWeight: '700',
                                formatter: function(value) {
                                    return value.toFixed(1) + '%';
                                },
                                offsetCenter: [0, '20%'],
                                color: '#333'
                            }
                        }]
                    }]
                });
            });

            // --- SPARKINES ---
            document.querySelectorAll('.sparkline-chart').forEach(function(el) {
                var chart = echarts.init(el);
                var rawData = el.getAttribute('data-history');
                if (!rawData || rawData === '[]') return;
                var historyData = JSON.parse(rawData);
                var color = el.getAttribute('data-color');
                chart.setOption({
                    grid: {
                        left: 0,
                        right: 0,
                        top: 10,
                        bottom: 0
                    },
                    xAxis: {
                        type: 'category',
                        data: historyData.map(d => d.anio),
                        show: false
                    },
                    yAxis: {
                        type: 'value',
                        show: false,
                        min: 'dataMin'
                    },
                    series: [{
                        data: historyData.map(d => parseFloat(d.valor)),
                        type: 'line',
                        smooth: 0.3,
                        symbol: 'none',
                        lineStyle: {
                            color: color,
                            width: 2
                        },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                                offset: 0,
                                color: color + '44'
                            }, {
                                offset: 1,
                                color: 'transparent'
                            }])
                        }
                    }]
                });
            });

            if (window.isPdfExport) {
                window.__pdfReady = true;
            }
        });
    </script>
@endsection
