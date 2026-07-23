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
<script src="{{ asset('js/script-ficha.js') }}?v={{ time() }}"></script>
@endsection

@section('content')
<div class="container-fluid my-4" data-api-url="{{ route('api.data') }}" data-csrf-token="{{ csrf_token() }}"
    data-export-url="{{ route('banco-indicadores.exportar') }}">
    <div class="px-2 px-md-4">
        <div class="row">
            {{-- Columna Lateral: Catálogo (Persistente) --}}
            <div class="col-md-3 catalogo-col">
                <h2 class="h4 mb-3 fw-bold">Banco de Indicadores</h2>

                {{-- Selector de Nivel (Segmented Control) - Persistente --}}
                <div class="level-switcher mb-3">
                    <div class="nav nav-pills nav-fill bg-light p-1 rounded-pill shadow-sm" id="pills-tab-nivel" role="tablist">
                        <button class="nav-link active rounded-pill py-1 px-2 small" id="pill-municipios-tab"
                            data-bs-target="#pane-municipios" type="button" role="tab" data-nivel="municipio">Municipio</button>
                        <button class="nav-link rounded-pill py-1 px-2 small" id="pill-microrregiones-tab"
                            data-bs-target="#pane-regiones" type="button" role="tab" data-nivel="microrregion">Microrregión</button>
                        <button class="nav-link rounded-pill py-1 px-2 small" id="pill-macrorregiones-tab"
                            data-bs-target="#pane-regiones" type="button" role="tab" data-nivel="macrorregion">Macrorregión</button>
                    </div>
                </div>

                {{-- Contenido de Búsqueda y Acordeón --}}
                <div class="tab-content" id="pills-sidebar-content">
                    {{-- Pane Municipal --}}
                    <div class="tab-pane fade show active" id="sidebar-pane-municipios" role="tabpanel">
                        <div class="p-3 border-bottom bg-light rounded-3 mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="search" id="indicador-search" class="form-control border-start-0"
                                    placeholder="Buscar indicador...">
                            </div>
                        </div>
                        <div class="accordion accordion-flush dashboard-accordion" id="accordionDimensions">
                            @foreach ($dimensiones as $dimension)
                            <div class="accordion-item dimension-item">
                                <h2 class="accordion-header" id="heading-dimension-{{ $dimension->id }}">
                                    <button class="accordion-button collapsed dimension-button" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse-dimension-{{ $dimension->id }}"
                                        style="border-left: 4px solid {{ $dimension->color ?? '#6c757d' }};">
                                        {{ $dimension->nombre }}
                                    </button>
                                </h2>
                                <div id="collapse-dimension-{{ $dimension->id }}"
                                    class="accordion-collapse collapse" data-bs-parent="#accordionDimensions">
                                    <div class="accordion-body p-0">
                                        <div class="accordion accordion-flush tematica-accordion" id="accordionTematicas-{{ $dimension->id }}">
                                            @foreach ($dimension->tematicas as $tematica)
                                            <div class="accordion-item tematica-item">
                                                <h2 class="accordion-header" id="heading-tematica-{{ $tematica->id }}">
                                                    <button class="accordion-button collapsed py-2 tematica-button"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse-tematica-{{ $tematica->id }}">
                                                        {{ $tematica->nombre }}
                                                    </button>
                                                </h2>
                                                <div id="collapse-tematica-{{ $tematica->id }}" class="accordion-collapse collapse"
                                                    data-bs-parent="#accordionTematicas-{{ $dimension->id }}">
                                                    <div class="accordion-body py-1 ps-4 pe-2">
                                                        <ul class="list-unstyled indicator-list">
                                                            @foreach ($tematica->indicadores as $indicador)
                                                            <li>
                                                                <a href="#" class="indicador-link d-flex align-items-center py-1"
                                                                    data-indicador-id="{{ $indicador->id }}"
                                                                    data-es-complejo="{{ $indicador->es_complejo ? 'true' : 'false' }}"
                                                                    data-tipo-dato="{{ $indicador->tipo_dato }}">
                                                                    <span class="indicator-name">{{ $indicador->nombre_amigable }}</span>
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

                    {{-- Pane Regional --}}
                    <div class="tab-pane fade" id="sidebar-pane-regiones" role="tabpanel">
                        <div class="p-3 border-bottom bg-light rounded-3 mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="search" id="indicador-search-regions" class="form-control border-start-0"
                                    placeholder="Buscar indicador...">
                            </div>
                        </div>
                        <div class="accordion accordion-flush dashboard-accordion" id="accordionDimensionsRegions">
                            {{-- Clonado vía JS --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Central/Derecha: Contenido y Visualización --}}
            <div class="col-md-9 content-col">
                <div class="tab-content" id="pills-main-content">

                    {{-- Vista Municipal --}}
                    <div class="tab-pane fade show active" id="pane-municipios" role="tabpanel">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <div class="chart-panel__header">
                                    <button class="btn btn-sm btn-outline-secondary toggle-catalogo-btn rounded-circle" data-bs-toggle="tooltip" title="Ocultar catálogo">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <h5 id="chart-title" class="chart-panel__title mb-0 fw-bold text-dark">Selecciona un municipio</h5>
                                    <div class="chart-panel__actions d-flex gap-2">
                                        <div id="resumen-container" style="display: none;">
                                            <a href="{{ route('ficha-municipal.show', ['municipio' => 'ID_PLACEHOLDER']) }}"
                                                id="resumen-btn" class="btn btn-sm btn-outline-info rounded-pill disabled" title="Resumen Municipal">
                                                <i class="fa-solid fa-file-invoice me-1"></i> Resumen
                                            </a>
                                        </div>
                                        <button id="toggle-map-btn" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="fas fa-map me-1"></i> Mapa
                                        </button>
                                        <button id="fullscreen-btn" class="btn btn-sm btn-outline-secondary rounded-pill"
                                            data-bs-toggle="modal" data-bs-target="#chart-fullscreen-modal" style="display: none;">
                                            <i class="fas fa-expand me-1"></i> Expandir
                                        </button>
                                        <button class="btn btn-sm btn-outline-dark rounded-pill" onclick="window.print()">
                                            <i class="fas fa-print me-1"></i> Imprimir
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                {{-- Barra de Filtros Municipal --}}
                                <div class="filter-bar p-2 bg-light mb-3 rounded-4 shadow-sm">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-5">
                                            <div id="municipio-selector-container">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-map-marker-alt text-muted"></i></span>
                                                    <select id="municipio-selector" multiple class="form-control-sm border-start-0">
                                                        @foreach ($municipios as $municipio)
                                                        <option value="{{ $municipio->id }}" data-cvegeo="{{ $municipio->cvegeo }}"
                                                            data-slug="{{ $municipio->slug }}"
                                                            data-orden="{{ $municipio->orden ?? 0 }}">
                                                            {{ $municipio->nombre }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                    <button id="estatal-btn" class="btn btn-outline-secondary" type="button" title="Ver Total Estatal">
                                                        <i class="fas fa-globe-americas"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div id="year-selector-container" style="display: none;">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar-alt text-muted"></i></span>
                                                    <select id="year-selector" multiple class="form-control-sm border-start-0"></select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <button id="consultar-btn" class="btn btn-sm btn-custom-primary flex-grow-1">
                                                    <i class="fas fa-search"></i> Consultar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contenedor de Gráfica --}}
                                <div id="consult-feedback" class="small text-muted mb-2">
                                    Elige un indicador y al menos un municipio para habilitar la consulta.
                                </div>
                                <div id="view-summary" class="alert alert-light border rounded-4 py-2 px-3 small mb-3">
                                    Consulta actual: Aún no has seleccionado un indicador.
                                </div>
                                <div id="view-guidance" class="alert alert-info border-0 rounded-4 py-2 px-3 small mb-3">
                                    Puedes seleccionar hasta 2 municipios. El total estatal solo está disponible para indicadores absolutos.
                                </div>
                                <div class="viz-wrapper position-relative">
                                    <div id="chart-container" style="min-height: 500px; width: 100%;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5">
                                            <i class="fas fa-chart-line fa-4x text-light mb-3"></i>
                                            <p class="text-muted fw-semibold mb-2">Sigue estos pasos para comenzar</p>
                                            <p class="text-muted mb-1">1. Elige un indicador del catálogo.</p>
                                            <p class="text-muted mb-1">2. Selecciona uno o dos municipios.</p>
                                            <p class="text-muted mb-0">3. Presiona Consultar para cargar la gráfica.</p>
                                        </div>
                                    </div>

                                    {{-- Mapa Flotante --}}
                                    <div id="map-container" class="floating-map-overlay shadow-lg" style="display: none;">
                                        <div class="map-overlay-header d-flex justify-content-between align-items-center px-2 py-1 bg-white border-bottom">
                                            <span class="small fw-bold text-muted">Vista Espacial</span>
                                            <button type="button" class="btn-close" style="font-size: 0.6rem;" onclick="document.getElementById('toggle-map-btn').click()"></button>
                                        </div>
                                        <div id="map" style="height: 220px; width: 220px;"></div>
                                        <div id="map-legend" class="p-1 bg-white border-top small text-center"></div>
                                    </div>
                                </div>
                                <div id="chart-note-container" class="mb-3" style="display: none;"></div>

                                <div id="metadata-container" class="metadata-block mt-4 pt-3 border-top" style="display: none;">
                                    {{-- Contenido de metadatos (Definición, Fuente, etc.) --}}
                                    <div class="row g-4">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="fw-bold small text-uppercase text-muted d-block">Definición</label>
                                                <span id="indicator-description" class="text-justify small d-block"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold small text-uppercase text-muted d-block">Método de cálculo</label>
                                                <span id="indicator-method" class="text-justify small d-block"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0 p-3 rounded-4">
                                                <div class="mb-2">
                                                    <label class="fw-bold small text-muted d-block">Fuente</label>
                                                    <span id="indicator-source" class="small"></span>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="fw-bold small text-muted d-block">Años disponibles</label>
                                                    <span id="indicator-available-years" class="small"></span>
                                                </div>
                                                <button id="export-btn" class="btn btn-sm btn-outline-success mt-2 w-100">
                                                    <i class="fas fa-download me-1"></i> Exportar CSV
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Vista Regional --}}
                    <div class="tab-pane fade" id="pane-regiones" role="tabpanel">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <div class="chart-panel__header">
                                    <button class="btn btn-sm btn-outline-secondary toggle-catalogo-btn rounded-circle" data-bs-toggle="tooltip" title="Ocultar catálogo">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <h5 id="chart-title-regions" class="chart-panel__title mb-0 fw-bold text-dark">Selecciona una región</h5>
                                    <div class="chart-panel__actions d-flex gap-2">
                                        <a href="{{ route('regiones.estatal.perfil') }}" class="btn btn-sm btn-outline-success rounded-pill">
                                            <i class="fas fa-globe-americas me-1"></i> Perfil estatal
                                        </a>
                                        <button id="toggle-map-btn-regions" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="fas fa-map me-1"></i> Mapa
                                        </button>
                                        <button id="fullscreen-btn-regions" class="btn btn-sm btn-outline-secondary rounded-pill"
                                            data-bs-toggle="modal" data-bs-target="#chart-fullscreen-modal" style="display: none;">
                                            <i class="fas fa-expand me-1"></i> Expandir
                                        </button>
                                        <button class="btn btn-sm btn-outline-dark rounded-pill" onclick="window.print()">
                                            <i class="fas fa-print me-1"></i> Imprimir
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                {{-- Barra de Filtros Regional --}}
                                <div class="filter-bar p-2 bg-light mb-3 rounded-4 shadow-sm">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-5">
                                            <div id="microrregion-selector-container">
                                                <select id="microrregion-selector" class="form-control-sm">
                                                    @foreach ($microrregiones as $region)
                                                    <option value="{{ $region->id }}">
                                                        {{ $region->nombre }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="macrorregion-selector-container" style="display: none;">
                                                <select id="macrorregion-selector" class="form-control-sm">
                                                    @foreach ($macrorregiones as $region)
                                                    <option value="{{ $region->id }}">
                                                        {{ $region->nombre }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div id="year-selector-container-regions" style="display: none;">
                                                <select id="year-selector-regions" multiple class="form-control-sm"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex gap-1">
                                                <button id="consultar-btn-regions" class="btn btn-sm btn-custom-primary flex-grow-1">Consultar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contenedor de Gráfica (Regiones) --}}
                                <div class="viz-wrapper position-relative">
                                    <div id="consult-feedback-regions" class="small text-muted mb-2">
                                        Elige un indicador y una región para habilitar la consulta.
                                    </div>
                                    <div id="view-summary-regions" class="alert alert-light border rounded-4 py-2 px-3 small mb-3">
                                        Consulta actual: Aún no has seleccionado un indicador regional.
                                    </div>
                                    <div id="view-guidance-regions" class="alert alert-info border-0 rounded-4 py-2 px-3 small mb-3">
                                         La consulta regional utiliza municipios completos; algunas intersecciones oficiales no se muestran porque la información no puede desagregarse por porciones territoriales. <a href="{{ config('regionalizacion.url') }}" target="_blank" rel="noopener noreferrer">Consulta la regionalización oficial vigente</a>.
                                    </div>
                                    <div id="chart-container-regions" style="min-height: 500px; width: 100%;">
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5">
                                            <i class="fas fa-chart-area fa-4x text-light mb-3"></i>
                                            <p class="text-muted fw-semibold mb-2">Sigue estos pasos para comenzar</p>
                                            <p class="text-muted mb-1">1. Elige un indicador del catálogo.</p>
                                            <p class="text-muted mb-1">2. Selecciona una microrregión o macrorregión.</p>
                                            <p class="text-muted mb-0">3. Presiona Consultar para cargar la gráfica.</p>
                                            <p class="text-muted">Selecciona un indicador y una región para comenzar.</p>
                                        </div>
                                    </div>

                                    {{-- Mapa Flotante (Regiones) --}}
                                    <div id="map-container-regions" class="floating-map-overlay shadow-lg" style="display: none;">
                                        <div class="map-overlay-header d-flex justify-content-between align-items-center px-2 py-1 bg-white border-bottom">
                                            <span class="small fw-bold text-muted">Vista Espacial</span>
                                            <button type="button" class="btn-close" style="font-size: 0.6rem;" onclick="document.getElementById('toggle-map-btn-regions').click()"></button>
                                        </div>
                                        <div id="map-regions" style="height: 220px; width: 220px;"></div>
                                        <div id="map-legend-regions" class="p-1 bg-white border-top small text-center"></div>
                                    </div>
                                </div>
                                <div id="chart-note-container-regions" class="mb-3" style="display: none;"></div>

                                <div id="metadata-container-regions" class="metadata-block mt-4 pt-3 border-top" style="display: none;">
                                    <div class="row g-4">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="fw-bold small text-uppercase text-muted d-block">Descripción</label>
                                                <span id="indicator-description-regions" class="text-justify small d-block"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold small text-uppercase text-muted d-block">Método de cálculo</label>
                                                <span id="indicator-method-regions" class="text-justify small d-block"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0 p-3 rounded-4">
                                                <div class="mb-2">
                                                    <label class="fw-bold small text-muted d-block">Fuente</label>
                                                    <span id="indicator-source-regions" class="small"></span>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="fw-bold small text-muted d-block">Años disponibles</label>
                                                    <span id="indicator-available-years-regions" class="small"></span>
                                                </div>
                                                <button id="export-btn-regions" class="btn btn-sm btn-outline-success mt-2 w-100">
                                                    <i class="fas fa-download me-1"></i> Exportar CSV
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

        {{-- Modal Fullscreen --}}
        <div class="modal fade" id="chart-fullscreen-modal" tabindex="-1">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fullscreen-modal-title">Visualización</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div id="chart-fullscreen-container" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
