@extends('layouts.plantilla')

@section('title', 'Perfil de ' . $tipoRegion . ' de ' . $region->nombre)

@section('content')
{{-- 1. HERO SECTION --}}
<section class="hero-ficha" style="background-image: url('https://picsum.photos/id/1016/1920/1080?blur=2')">
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

                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="badge bg-gold px-3 py-2 text-uppercase hero-ficha__badge m-0">PERFIL REGIONAL</span>
                    @php
                        $pdfRoute = $tipoRegion === 'Macrorregión' ? route('regiones.macro.pdf', $region->slug) : route('regiones.micro.pdf', $region->slug);
                        $excelRoute = $tipoRegion === 'Macrorregión' ? route('regiones.macro.excel', $region->slug) : route('regiones.micro.excel', $region->slug);
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
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Municipios que la conforman</small>
                        <span class="h5 fw-bold">{{ $municipios->count() }} municipios</span>
                    </div>
                    <div class="hero-info-item">
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Población Total</small>
                        <span class="h5 fw-bold d-block">{{ number_format($poblacionTotal) }} hab.</span>
                    </div>
                    <div class="hero-info-item">
                        <small class="d-block text-white-50 text-uppercase small letter-spacing-1">Superficie</small>
                        <span class="h5 fw-bold d-block">{{ number_format($superficieTotal, 2) }} km²</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                {{-- Opcional: Mostrar un mapa o ilustración representativa aquí --}}
                <div class="card border-0 bg-transparent text-white mt-4">
                    <div class="card-body">
                        <h5 class="fw-bold text-gold mb-3">Municipios:</h5>
                        <div class="d-flex flex-wrap gap-2" style="max-height: 200px; overflow-y: auto; scrollbar-width: thin;">
                            @foreach($municipios as $muni)
                                <a href="{{ route('ficha-municipal.perfil', $muni->slug) }}" class="badge bg-white bg-opacity-10 text-white text-decoration-none border border-white border-opacity-25" style="transition: all 0.2s;" title="Ver ficha de {{ $muni->nombre }}">
                                    {{ $muni->nombre }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
    @foreach($perfil as $seccion => $items)
    @if($seccion != 'general')
    <section id="section-{{ Str::slug($seccion) }}" class="section-perfil mb-5 pb-5">
        <div class="dimension-header shadow-sm">
            <h2 class="display-4 fw-bold mb-0">{{ ucwords(str_replace('_', ' ', $seccion)) }}</h2>
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
                                <a href="{{ route('banco-indicadores.index', ['indicador_id' => $item['config']->indicador->id, 'nivel' => $tipoRegion === 'Macrorregión' ? 'macrorregion' : 'microrregion', 'region_id' => $region->id]) }}"
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
                                @if(isset($item['datos']['metodo_calculo']) || isset($item['datos']['fuente']))
                                <i class="fa-solid fa-circle-info info-tooltip-trigger perfil-tarjeta__info-icon mb-0"
                                    data-bs-toggle="popover"
                                    data-bs-trigger="hover focus"
                                    title="Metodología y Fuente"
                                    data-bs-content="<strong>Método:</strong> {{ $item['datos']['metodo_calculo'] ?? 'No especificado' }}<br><strong>Fuente:</strong> {{ $item['datos']['fuente'] ?? 'No especificada' }}"
                                    data-bs-html="true"></i>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-4 align-items-center">
                            <div class="col-md-4 text-center border-end">
                                <h5 class="text-uppercase small fw-bold text-muted mb-2">Valor Regional</h5>
                                <h4 class="perfil-tarjeta__kpi-value text-vino" style="font-size: 2.5rem;">
                                    {{ $item['datos']['valor_actual'] ?? $item['datos']['total'] ?? 0 }}
                                </h4>
                                @if(isset($item['datos']['variables'][0]['unidad']))
                                <p class="perfil-tarjeta__kpi-unit">{{ $item['datos']['variables'][0]['unidad'] }}</p>
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

                        @if(!empty($item['datos']['echarts']['series']))
                        {{-- Gráfico de Ranking Interno --}}
                        <div class="perfil-tarjeta__chart-wrapper perfil-tarjeta__chart-wrapper--full">
                            <div class="perfil-tarjeta__skeleton" id="skeleton-{{ $item['config']->id }}">
                                <div class="spinner-border perfil-tarjeta__spinner" role="status">
                                    <span class="visually-hidden">Cargando gráfico...</span>
                                </div>
                            </div>
                            <div class="perfil-tarjeta__chart-box lazy-chart perfil-tarjeta__chart-box--full" id="chart-{{ $item['config']->id }}" data-chart-id="{{ $item['config']->id }}" style="height: 400px;"></div>
                        </div>
                        @endif

                    </div>
                    @if(isset($item['datos']['fuente']))
                    <div class="perfil-tarjeta__footer">
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
        municipioNombre: "Regional",
        perfilData: @json($perfil)
    };
    
    // Sobrescribir fetch de geojson ya que no necesitamos el mapa aquí
    // pero mantenemos la lógica de perfil.js para cargar los lazy-charts
    document.addEventListener("DOMContentLoaded", function () {
        setupLazyCharts();
        setupScrollSpy();
        
        // Inicializar popovers directamente
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });

    // Pequeño script modificado de perfil.js solo para renderizar la cola localmente
    const renderQueue = [];
    let processingQueue = false;

    function processNextInQueue() {
        if (renderQueue.length === 0) {
            processingQueue = false;
            return;
        }
        processingQueue = true;
        const task = renderQueue.shift();
        
        renderMainChart(task.itemData);
        task.chartElement.style.opacity = "1";

        const skeleton = document.getElementById("skeleton-" + task.chartId);
        if (skeleton) {
            skeleton.style.transition = "opacity 0.3s ease";
            skeleton.style.opacity = "0";
            setTimeout(() => skeleton.remove(), 300);
        }

        setTimeout(() => requestAnimationFrame(processNextInQueue), 40);
    }

    function setupLazyCharts() {
        const lazyCharts = document.querySelectorAll(".lazy-chart");
        const chartObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const chartElement = entry.target;
                        const chartId = chartElement.getAttribute("data-chart-id");
                        const itemData = findChartDataById(chartId);

                        if (itemData) {
                            renderQueue.push({ itemData, chartElement, chartId });
                            if (!processingQueue) processNextInQueue();
                        }
                        observer.unobserve(chartElement);
                    }
                });
            },
            { root: null, rootMargin: "150px 0px 150px 0px", threshold: 0.05 }
        );
        lazyCharts.forEach((chart) => chartObserver.observe(chart));
    }

    function findChartDataById(id) {
        if (!window.FichaConfig || !window.FichaConfig.perfilData) return null;
        let found = null;
        Object.values(window.FichaConfig.perfilData).forEach((items) => {
            const match = items.find((item) => String(item.config.id) === String(id));
            if (match) found = match;
        });
        return found;
    }

    function renderMainChart(itemData) {
        var chartDom = document.getElementById("chart-" + itemData.config.id);
        if (!chartDom) return;
        var myChart = echarts.getInstanceByDom(chartDom) || echarts.init(chartDom);
        var echartsData = itemData.datos.echarts;

        if (echartsData && echartsData.type === "bar-horizontal") {
            let option = {
                tooltip: {
                    trigger: "axis",
                    axisPointer: { type: "shadow" },
                    confine: true,
                    formatter: function (params) {
                        if (!params || params.length === 0) return "";
                        let str = `<strong>${params[0].name}</strong><br/>`;
                        let total = 0;
                        let hasMultiple = params.length > 1;
                        let unidad = echartsData.unidad || "";
                        
                        params.forEach(p => {
                            let val = p.value;
                            if (val > 0) {
                                let formattedVal = Number(val).toLocaleString("es-MX");
                                str += `${p.marker}${p.seriesName}: <strong>${formattedVal} ${unidad}</strong><br/>`;
                                total += Number(val);
                            }
                        });
                        
                        if (hasMultiple && total > 0) {
                            str += `<div style="border-top:1px solid #ccc; margin-top:5px; padding-top:5px;">Total: <strong>${Number(total).toLocaleString("es-MX")} ${unidad}</strong></div>`;
                        }
                        
                        return str;
                    },
                },
                legend: { bottom: 0 },
                grid: { top: 30, bottom: 60, left: 150, right: 30 },
                xAxis: {
                    type: "value",
                    splitLine: { show: true, lineStyle: { type: "dashed" } },
                    axisLabel: { formatter: (value) => Number(value).toLocaleString("es-MX") },
                },
                yAxis: {
                    type: "category",
                    data: echartsData.eje_y ? echartsData.eje_y.categorias : [],
                    inverse: true,
                    axisTick: { show: false },
                    axisLabel: {
                        formatter: function (value) {
                            if (!value) return "";
                            if (value.length > 17) return value.substring(0, 17) + "...";
                            return value;
                        },
                    },
                },
                series: (echartsData.series || []).map((s) => {
                    s.type = "bar";
                    return s;
                }),
            };
            myChart.setOption(option);
            window.addEventListener('resize', () => myChart.resize());
        }
    }
    
    function setupScrollSpy() {
        const sections = document.querySelectorAll(".section-perfil");
        const navLinks = document.querySelectorAll(".sticky-nav__link");

        window.onscroll = () => {
            let current = "";
            sections.forEach((s) => {
                const top = s.offsetTop;
                if (pageYOffset >= top - 150) {
                    current = s.getAttribute("id");
                }
            });

            navLinks.forEach((link) => {
                link.classList.remove("active");
                if (current && link.getAttribute("href").includes(current)) {
                    link.classList.add("active");
                }
            });
        };
    }
</script>
@endsection
