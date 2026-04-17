@extends('layouts.plantilla')
@php
// Definimos valores específicos para el Banco de Indicadores
$pageTitle = 'Banco de Indicadores Municipales y Regionales';
$pageDescription =
'Consulta el banco completo de indicadores estadísticos del Estado de Puebla. Datos demográficos, económicos, sociales y más.';
$currentUrl = url()->current();
@endphp
<!-- local -->
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
    data-export-url="{{ route('fichas.exportar') }}">
    <div class="px-5">

        <h2>Banco de Indicadores</h2>

        <ul class="nav nav-pills nav-fill mb-4" id="pills-tab-nivel" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pill-municipios-tab" data-bs-toggle="pill"
                    data-bs-target="#pane-municipios" type="button" role="tab" data-nivel="municipio">Por
                    Municipio</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pill-microrregiones-tab" data-bs-toggle="pill"
                    data-bs-target="#pane-regiones" type="button" role="tab" data-nivel="microrregion">Por
                    Microrregión</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pill-macrorregiones-tab" data-bs-toggle="pill"
                    data-bs-target="#pane-regiones" type="button" role="tab" data-nivel="macrorregion">Por
                    Macrorregión</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent-nivel">

            <div class="tab-pane fade show active" id="pane-municipios" role="tabpanel">
                <div class="row">
                    <div class="col-md-3 catalogo-col">
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
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse-dimension-{{ $dimension->id }}"
                                        style="background-color: {{ $dimension->color ?? '#6c757d' }};">
                                        {{ $dimension->nombre }}
                                    </button>
                                </h2>
                                <div id="collapse-dimension-{{ $dimension->id }}"
                                    class="accordion-collapse collapse" data-bs-parent="#accordionDimensions">
                                    <div class="accordion-body p-0">

                                        <div class="accordion accordion-flush"
                                            id="accordionTematicas-{{ $dimension->id }}">
                                            @foreach ($dimension->tematicas as $tematica)
                                            <div class="accordion-item">
                                                <h2 class="accordion-header"
                                                    id="heading-tematica-{{ $tematica->id }}">
                                                    <button class="accordion-button collapsed py-2"
                                                        type="button" data-bs-toggle="collapse"
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
                    <div class="col-md-9 content-col">
                        <button class="btn btn-sm btn-outline-secondary mb-2 toggle-catalogo-btn"
                            data-bs-toggle="tooltip" title="Ocultar catálogo">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="card shadow-sm">
                            <div class="card-header bg-light py-3">
                                <h5 id="chart-title" class="mb-0">Selecciona un indicador</h5>

                                <button id="fullscreen-btn" class="btn btn-sm btn-outline-secondary my-2"
                                    data-bs-toggle="modal" data-bs-target="#chart-fullscreen-modal"
                                    style="display: none;">
                                    <i class="fas fa-expand me-1"></i> Pantalla Completa
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Panel de Control</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3 align-items-end">

                                            {{-- Columna para el Selector de Municipio y Botón Estatal --}}
                                            <div class="col-lg-6">
                                                <div id="municipio-selector-container">
                                                    <label for="municipio-selector"
                                                        class="form-label fw-bold">Ubicación:</label>
                                                    <div class="input-group">
                                                        <select id="municipio-selector" multiple>
                                                            {{-- La opción 'estatal' se controla por JS, no es necesaria aquí --}}
                                                            @foreach ($municipios as $municipio)
                                                            <option value="{{ $municipio->id }}"
                                                                data-cvegeo="{{ $municipio->cvegeo }}"
                                                                data-orden="1">
                                                                {{ $municipio->nombre }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <button id="estatal-btn" class="btn btn-outline-secondary"
                                                            type="button" title="Ver Total Estatal">
                                                            <i class="fas fa-globe-americas"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                {{-- Interruptor de Comparación Estatal --}}
                                                <!-- <div class="mt-3 form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="compare-state-switch">
                                                    <label class="form-check-label" for="compare-state-switch">
                                                        Comparar con <strong>Total Estatal</strong>
                                                    </label>
                                                </div> -->
                                            </div>

                                            {{-- Columna para el Selector de Años --}}
                                            <div class="col-lg-3">
                                                <div id="year-selector-container" style="display: none;">
                                                    <label for="year-selector" class="form-label fw-bold">Año(s)
                                                        disponibles:</label>
                                                    <select id="year-selector" multiple></select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="input-group">
                                                    <button id="consultar-btn" class="btn btn-custom-primary w-75"
                                                        type="button">
                                                        <i class="fas fa-search me-1"></i> Consultar
                                                    </button>
                                                    <button id="share-btn" class="btn btn-outline-secondary w-25"
                                                        type="button" title="Compartir Vista">
                                                        <i class="fas fa-share-alt"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            {{-- Fila para el botón de Resumen, que aparece cuando es necesario --}}
                                            <div class="col-12 mt-3">
                                                <a href="{{ route('fichas.resumen', ['municipio' => 'ID_PLACEHOLDER']) }}"
                                                    id="resumen-btn" class="btn btn-link btn-sm p-0 disabled"
                                                    style="display: none; text-decoration: none;">
                                                    <i class="fa-solid fa-circle-info me-1"></i>
                                                    Ver Ficha de Resumen Municipal
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-lg-9">
                                        <div id="chart-container" style="min-height: 400px;">
                                            <p class="text-muted text-center pt-5">Selecciona un indicador y un
                                                municipio para
                                                comenzar.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div id="map-container" style="display: none;">
                                            <div id="map"
                                                style="height: 400px; width: 100%; border-radius: .25rem;"></div>
                                            <div id="map-legend" class="mt-2 text-center"></div>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div id="chart-container" style="min-height: 400px;">
                                        <p class="text-muted text-center pt-5">Selecciona un indicador y un municipio para
                                            comenzar.
                                        </p>
                                    </div> --}}
                                <div id="chart-note-container" class="alert alert-info mt-3" role="alert"
                                    style="display: none; font-size: 0.9rem;">
                                </div>
                                <div id="metadata-container" class="metadata-block mt-4 pt-3 border-top"
                                    style="display: none; font-size: 0.9rem;">
                                    <div class="row align-items-start">

                                        <div class="col-md-7">
                                            <h6>Detalles del Indicador</h6>
                                            <p class="mb-1"><strong>Definición:</strong> <span
                                                    id="indicator-description" class="text-justify"></span></p>
                                            <p class="mb-1"><strong>Método de cálculo:</strong> <span
                                                    id="indicator-method" class="text-justify"></span></p>
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

            <div class="tab-pane fade" id="pane-regiones" role="tabpanel">
                <div class="row">

                    <div class="col-lg-3 catalogo-col">
                        {{-- El mismo acordeón se usa aquí, pero será filtrado por JS --}}
                        <h4>Catálogo de Indicadores</h4>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="search" id="indicador-search-regions" class="form-control"
                                placeholder="Buscar en esta pestaña...">
                        </div>
                        <div class="accordion" id="accordionDimensionsRegions">
                            {{-- Clonaremos el acordeón original aquí con JS --}}
                        </div>

                    </div>
                    <div class="col-lg-9 content-col">
                        <button class="btn btn-sm btn-outline-secondary mb-2 toggle-catalogo-btn"
                            data-bs-toggle="tooltip" title="Ocultar catálogo">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="card shadow-sm">
                            <div class="card-header bg-light py-3">
                                <h5 id="chart-title-regions" class="mb-0">Selecciona un indicador y una región</h5>
                                <button id="fullscreen-btn-regions" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#chart-fullscreen-modal"
                                    style="display: none;">
                                    <i class="fas fa-expand me-1"></i> Pantalla Completa
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 align-items-center border-bottom pb-3 mb-3">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Panel de Control
                                                Regional</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3 align-items-end">

                                                {{-- Columna para los Selectores de Región --}}
                                                <div class="col-lg-6">
                                                    {{-- Contenedor para Microrregiones (visible por defecto) --}}
                                                    <div id="microrregion-selector-container">
                                                        <label for="microrregion-selector"
                                                            class="form-label fw-bold">Microrregión:</label>
                                                        <select id="microrregion-selector">
                                                            @foreach ($microrregiones as $region)
                                                            <option value="{{ $region->id }}">
                                                                {{ $region->nombre }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    {{-- Contenedor para Macrorregiones (oculto por defecto) --}}
                                                    <div id="macrorregion-selector-container" style="display: none;">
                                                        <label for="macrorregion-selector"
                                                            class="form-label fw-bold">Macrorregión:</label>
                                                        <select id="macrorregion-selector">
                                                            @foreach ($macrorregiones as $region)
                                                            <option value="{{ $region->id }}">
                                                                {{ $region->nombre }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                {{-- Columna para el Selector de Años de Regiones --}}
                                                <div class="col-lg-3">
                                                    <div id="year-selector-container-regions" style="display: none;">
                                                        <label for="year-selector-regions"
                                                            class="form-label fw-bold">Año(s):</label>
                                                        <select id="year-selector-regions" multiple></select>
                                                    </div>
                                                </div>

                                                {{-- Columna para el Botón "Consultar" --}}
                                                <div class="col-lg-3">
                                                    <div class="input-group">
                                                        <button id="consultar-btn-regions"
                                                            class="btn btn-custom-primary w-75" type="button">
                                                            <i class="fas fa-search me-1"></i> Consultar
                                                        </button>
                                                        <button id="share-btn-regions"
                                                            class="btn btn-outline-secondary w-25" type="button"
                                                            title="Compartir Vista">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                {{-- <div class="col-lg-3">
                                                        <button id="consultar-btn-regions"
                                                            class="btn btn-custom-primary w-100">
                                                            <i class="fas fa-search me-1"></i> Consultar
                                                        </button>
                                                    </div> --}}

                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        {{-- Columna para el Gráfico --}}
                                        <div class="col-lg-9">
                                            <div id="chart-container-regions" style="min-height: 400px;">
                                                <p class="text-muted text-center pt-5">Selecciona un indicador y una
                                                    región para comenzar.</p>
                                            </div>
                                        </div>

                                        {{-- Columna para el Mapa --}}
                                        <div class="col-lg-3">
                                            {{-- Contenedor del Mapa (el que faltaba) --}}
                                            <div id="map-container-regions" style="display: none;">
                                                <div id="map-regions"
                                                    style="height: 400px; width: 100%; border-radius: .25rem;"></div>
                                                <div id="map-legend-regions" class="mt-2 text-center"></div>

                                                <a href="#" id="ver-municipios-btn"
                                                    class="btn btn-link btn-sm mt-2" data-bs-toggle="modal"
                                                    data-bs-target="#municipios-modal"
                                                    style="display: none; text-decoration: none;">
                                                    <i class="fas fa-list-ul me-1"></i> Ver municipios de esta región
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="chart-note-container-regions" class="alert alert-info mt-3"
                                        role="alert" style="display: none; font-size: 0.9rem;"></div>
                                    <div id="metadata-container-regions" class="metadata-block mt-4 pt-3 border-top"
                                        style="display: none; font-size: 0.9rem;">
                                        <div class="row align-items-start">

                                            <div class="col-md-8">
                                                <h6>Detalles del Indicador</h6>
                                                <p class="mb-1"><strong>Descripción:</strong> <span
                                                        id="indicator-description-regions"
                                                        class="text-justify"></span>
                                                </p>
                                                <p class="mb-1"><strong>Método de cálculo:</strong> <span
                                                        id="indicator-method-regions" class="text-justify"></span></p>
                                                <p class="mb-1"><strong>Fuente:</strong> <span
                                                        id="indicator-source-regions" class="text-justify"></span></p>
                                                <p class="mb-0"><strong>Años de información disponible:</strong>
                                                    <span id="indicator-available-years-regions"
                                                        class="text-justify"></span>
                                                </p>
                                            </div>

                                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                                <h6 class="d-none d-md-block">Acciones</h6>
                                                <button id="export-btn-regions" class="btn btn-sm btn-outline-success"
                                                    style="display: none;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                        height="16" fill="currentColor"
                                                        class="bi bi-download me-1" viewBox="0 0 16 16">
                                                        <path
                                                            d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                                                        <path
                                                            d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z" />
                                                    </svg>
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
    <div class="modal fade" id="municipios-modal" tabindex="-1" aria-labelledby="municipios-modal-title"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="municipios-modal-title">Municipios</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="municipios-modal-body" style="background-color: #f8f9fa;">
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
                    <p class="titulo-documento-compacta">Proyecciones de población 1990-2040 para el estado de Puebla</p> <a
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
<script src="{{ asset('js/script-ficha.js?v=' . filemtime(public_path('js/script-ficha.js'))) }}" defer></script>
<script src="{{ asset('js/buscador-indicadores.js?v=' . filemtime(public_path('js/buscador-indicadores.js'))) }}"
    defer></script>
{{-- <script src="{{ asset('js/mapa-dashboard.js') }}" defer></script> --}}
@endsection