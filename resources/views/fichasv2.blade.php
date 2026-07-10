@extends('layouts.plantilla')
@php
    // Definimos valores específicos para el Banco de Indicadores
    $pageTitle = 'Banco de Indicadores Municipales y Regionales';
    $pageDescription =
        'Consulta el banco completo de indicadores estadísticos del Estado de Puebla. Datos demográficos, económicos, sociales y más.';
    $currentUrl = url()->current();
@endphp

@section('title', $pageTitle)
@section('meta-description', $pageDescription)
@section('canonical-url', $currentUrl)

{{-- Open Graph --}}
@section('og-title', "{$pageTitle} - Gobierno del Estado de Puebla")
@section('og-description', $pageDescription)
@section('og:url', $currentUrl)
@section('og:image', asset('img/mapa_puebla.png'))

{{-- Twitter --}}
@section('twitter-title', "{$pageTitle} - Gobierno de Puebla")
@section('twitter-description', $pageDescription)
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection
@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
@endsection

@section('content')
    <div class="container-fluid my-4" data-api-url="{{ route('api.data') }}" data-csrf-token="{{ csrf_token() }}"
        data-export-url="{{ route('banco-indicadores.exportar') }}">
        <div class="px-5">

            <h2>Banco de Indicadores</h2>

            <div class="row">

                <div class="col-md-3">
                    <h4>Catálogo de Indicadores</h4>
                    <div class="p-3 border-bottom bg-light">
                        <div class="input-group">
                            <span class="input-group-text" id="search-addon"><i class="fas fa-search"></i></span>
                            <input type="search" id="indicador-search" class="form-control"
                                placeholder="Buscar indicador por nombre..." aria-label="Buscar indicador"
                                aria-describedby="search-addon">
                        </div>
                    </div>

                    <div class="accordion" id="accordionDimensions">
                        @foreach ($dimensiones as $dimension)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-dimension-{{ $dimension->id }}">
                                    <button class="accordion-button collapsed text-white" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapse-dimension-{{ $dimension->id }}"
                                        style="background-color: {{ $dimension->color ?? '#6c757d' }};">
                                        {{ $dimension->nombre }}
                                    </button>
                                </h2>
                                <div id="collapse-dimension-{{ $dimension->id }}" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionDimensions">
                                    <div class="accordion-body p-0">
                                        <div class="accordion accordion-flush" id="accordionTematicas-{{ $dimension->id }}">
                                            @foreach ($dimension->tematicas as $tematica)
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading-tematica-{{ $tematica->id }}">
                                                        <button class="accordion-button collapsed py-2" type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#collapse-tematica-{{ $tematica->id }}">
                                                            {{ $tematica->nombre }}
                                                        </button>
                                                    </h2>
                                                    <div id="collapse-tematica-{{ $tematica->id }}"
                                                        class="accordion-collapse collapse"
                                                        data-bs-parent="#accordionTematicas-{{ $dimension->id }}">
                                                        <div class="accordion-body py-1 px-3">
                                                            <ul class="list-unstyled">
                                                                @foreach ($tematica->indicadores as $indicador)
                                                                    <li>
                                                                        <a href="#"
                                                                            class="indicador-link d-block py-1 text-black"
                                                                            data-indicador-id="{{ $indicador->id }}"
                                                                            data-tipo-dato="{{ $indicador->tipo_dato }}"
                                                                            data-dimension-target="#collapse-dimension-{{ $dimension->id }}"
                                                                            data-tematica-target="#collapse-tematica-{{ $tematica->id }}"
                                                                            data-es-complejo="{{ $indicador->es_complejo ? 'true' : 'false' }}">
                                                                            {{ $indicador->nombre_amigable }}
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-9">

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Panel de Control</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-pills nav-fill mb-4" id="pills-tab-nivel" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pill-municipios-tab" data-bs-toggle="pill"
                                        data-bs-target="#pane-control-municipio" type="button" role="tab"
                                        data-nivel="municipio">Por
                                        Municipio</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pill-microrregiones-tab" data-bs-toggle="pill"
                                        data-bs-target="#pane-control-micro" type="button" role="tab"
                                        data-nivel="microrregion">Por
                                        Microrregión</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pill-macrorregiones-tab" data-bs-toggle="pill"
                                        data-bs-target="#pane-control-macro" type="button" role="tab"
                                        data-nivel="macrorregion">Por
                                        Macrorregión</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="pills-tabContent-control">

                                <div class="tab-pane fade show active" id="pane-control-municipio" role="tabpanel">
                                    <label for="municipio-selector" class="form-label fw-bold">Ubicación:</label>
                                    <div class="input-group">
                                        <select id="municipio-selector" multiple>
                                            @foreach ($municipios as $municipio)
                                                <option value="{{ $municipio->id }}"
                                                    data-cvegeo="{{ $municipio->cvegeo }}" data-orden="1">
                                                    {{ $municipio->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button id="estatal-btn" class="btn btn-outline-secondary" type="button"
                                            title="Ver Total Estatal">
                                            <i class="fas fa-globe-americas"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="pane-control-micro" role="tabpanel">
                                    <div id="microrregion-selector-container">
                                        <label for="microrregion-selector"
                                            class="form-label fw-bold">Microrregión:</label>
                                        <select id="microrregion-selector">
                                            @foreach ($microrregiones as $region)
                                                <option value="{{ $region->id }}">
                                                    {{ $region->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="pane-control-macro" role="tabpanel">
                                    <div id="macrorregion-selector-container">
                                        <label for="macrorregion-selector"
                                            class="form-label fw-bold">Macrorregión:</label>
                                        <select id="macrorregion-selector">
                                            @foreach ($macrorregiones as $region)
                                                <option value="{{ $region->id }}">
                                                    {{ $region->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="row g-3 align-items-end">
                                <div class="col-lg-6">
                                    <div id="year-selector-container" style="display: none;">
                                        <label for="year-selector" class="form-label fw-bold">Año(s) disponibles:</label>
                                        <select id="year-selector" multiple></select>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <a href="{{ route('ficha-municipal.show', ['municipio' => 'ID_PLACEHOLDER']) }}"
                                        id="resumen-btn" class="btn btn-link btn-sm p-0 disabled"
                                        style="display: none; text-decoration: none;">
                                        <i class="fa-solid fa-circle-info me-1"></i>
                                        Ver Ficha de Resumen Municipal
                                    </a>
                                </div>
                                <div class="col-lg-3">
                                    <button id="consultar-btn" class="btn btn-custom-primary w-100">
                                        <i class="fas fa-search me-1"></i> Consultar
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 id="chart-title" class="mb-0">Selecciona un indicador</h5>
                            <button id="fullscreen-btn" class="btn btn-sm btn-outline-secondary my-2"
                                data-bs-toggle="modal" data-bs-target="#chart-fullscreen-modal" style="display: none;">
                                <i class="fas fa-expand me-1"></i> Pantalla Completa
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mt-3">
                                <div class="col-lg-8">
                                    <div id="chart-container" style="min-height: 400px;">
                                        <p class="text-muted text-center pt-5">Selecciona un indicador y una
                                            ubicación para comenzar.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div id="map-container" style="display: none;">
                                        <div id="map" style="height: 400px; width: 100%; border-radius: .25rem;">
                                        </div>
                                        <div id="map-legend" class="mt-2 text-center"></div>
                                    </div>
                                </div>
                            </div>

                            <div id="chart-note-container" class="alert alert-info mt-3" role="alert"
                                style="display: none; font-size: 0.9rem;">
                            </div>

                            <div id="metadata-container" class="metadata-block mt-4 pt-3 border-top"
                                style="display: none; font-size: 0.9rem;">
                                <div class="row align-items-start">
                                    <div class="col-md-7">
                                        <h6>Detalles del Indicador</h6>
                                        <p class="mb-1"><strong>Definición:</strong> <span id="indicator-description"
                                                class="text-justify"></span></p>
                                        <p class="mb-1"><strong>Método de cálculo:</strong> <span id="indicator-method"
                                                class="text-justify"></span></p>
                                        <p class="mb-1"><strong>Fuente:</strong> <span id="indicator-source"
                                                class="text-justify"></span>
                                        </p>
                                        <p class="mb-0"><strong>Años de información disponible:</strong> <span
                                                id="indicator-available-years" class="text-justify"></span></p>
                                    </div>
                                    <div class="col-md-5 text-md-start mt-3 mt-md-0">
                                        <h6 class="d-none d-md-block">Acciones</h6>
                                        <button id="export-btn" class="btn btn-sm btn-outline-success"
                                            style="display: none;">
                                            <i class="fa-solid fa-file-arrow-down me-1"></i>
                                            Exportar (CSV)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="modal fade" id="chart-fullscreen-modal" tabindex="-1">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fullscreen-modal-title">Gráfico en Pantalla Completa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- El gráfico clonado se renderizará aquí --}}
                        <div id="fullscreen-chart-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section class="archivos-com">
        <div class="container my-3">
            <h2 class="py-5">Archivos complementarios</h2>
            <div class="container-fluid row">
                <div class="mx-auto px-4 col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="contenedor-tarjetadocs-compacta">
                        <p class="titulo-documento-compacta">Proyecciones 1990-2040 para el estado de Puebla</p> <a
                            href="{{ asset('documentos/Proyecciones19902040Puebla.zip') }}"
                            class="boton-descargar-compacta" download="" rel="noopener"> <i
                                class="fas fa-file-zipper"></i>
                            Descargar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="{{ asset('js/script-ficha.js') }}" defer></script>
    <script src="{{ asset('js/buscador-indicadores.js') }}" defer></script>
    {{-- <script src="{{ asset('js/mapa-dashboard.js') }}" defer></script> --}}
@endsection
