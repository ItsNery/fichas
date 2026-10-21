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
<style>
    /* Banco de Indicadores: tokens and components are scoped to this view. */
    #banco-indicadores {
        --brand-burgundy-700: #872341;
        --brand-burgundy-600: #961638;
        --brand-gold-500: #b88b5b;
        --brand-green-900: #0d312d;
        --brand-green-700: #256259;
        --brand-sand-300: #dcb993;
        --brand-neutral-900: #262626;
        --brand-neutral-400: #afafaf;
        --brand-neutral-100: #eaeaea;
        --brand-white: #ffffff;
        --color-primary: var(--brand-burgundy-700);
        --color-surface: var(--brand-white);
        --color-canvas: var(--brand-neutral-100);
        color: var(--brand-neutral-900);
        font-family: "Gilroy", "Avenir Next", "Helvetica Neue", Arial, sans-serif;
    }


    #banco-indicadores .bi-eyebrow,
    #banco-indicadores .bi-section-label,
    #banco-indicadores .metadata-block label {
        color: var(--brand-green-700);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    #banco-indicadores .bi-layout { align-items: flex-start; }

    #banco-indicadores .catalogo-col { position: sticky; top: 1rem; }
    #banco-indicadores .bi-explorer {
        background: var(--color-surface);
        border: 1px solid var(--brand-neutral-100);
        border-top: .3rem solid var(--brand-burgundy-700);
        padding: 1.25rem;
    }
    #banco-indicadores .bi-explorer-heading { font-size: 1.25rem; font-weight: 700; margin: .15rem 0 .35rem; }
    #banco-indicadores .bi-explorer-copy { color: var(--brand-neutral-900); font-size: .9375rem; margin: 0 0 1.1rem; }
    #banco-indicadores #pills-tab-nivel { background: var(--brand-neutral-100) !important; border-radius: 0 !important; box-shadow: none !important; gap: .15rem; }
    #banco-indicadores .level-switcher .nav-link { border-radius: 0; color: var(--brand-neutral-900); font-weight: 700; min-height: 2.5rem; }
    #banco-indicadores .level-switcher .nav-link.active { background: var(--brand-burgundy-700); color: var(--brand-white); }
    #banco-indicadores .level-switcher .nav-link:disabled { color: var(--brand-neutral-900); opacity: .7; text-decoration: line-through; }
    #banco-indicadores .bi-search { background: var(--brand-neutral-100) !important; border: 0 !important; border-radius: 0 !important; padding: .85rem !important; }
    #banco-indicadores .bi-search .input-group { border: 1px solid var(--brand-neutral-400); background: var(--brand-white); }
    #banco-indicadores .bi-search .form-control,
    #banco-indicadores .bi-search .input-group-text { border: 0; box-shadow: none; }
    #banco-indicadores .dashboard-accordion .accordion-item { border-color: var(--brand-neutral-100); }
    #banco-indicadores .dashboard-accordion .accordion-button { color: var(--brand-neutral-900); font-size: .9375rem; font-weight: 700; box-shadow: none; }
    #banco-indicadores .dashboard-accordion .accordion-button:not(.collapsed) { background: var(--brand-white); color: var(--brand-burgundy-700); }
    #banco-indicadores .tematica-button { font-size: .875rem !important; }
    #banco-indicadores .indicador-link { color: var(--brand-neutral-900); font-size: .875rem; line-height: 1.35; text-decoration: none; }
    #banco-indicadores .indicador-link:hover { color: var(--brand-burgundy-700); text-decoration: underline; }

    #banco-indicadores .bi-workspace { min-width: 0; }
    #banco-indicadores .bi-results-card { border: 1px solid var(--brand-neutral-100) !important; border-radius: 0 !important; box-shadow: none !important; }
    #banco-indicadores .bi-results-card .card-header { border-bottom: 1px solid var(--brand-neutral-100) !important; }
    #banco-indicadores .chart-panel__header { align-items: center; display: flex; gap: .75rem; justify-content: space-between; }
    #banco-indicadores .chart-panel__title { color: var(--brand-green-900) !important; flex: 1; font-size: 1.15rem; }
    #banco-indicadores .chart-panel__actions { flex-wrap: wrap; justify-content: flex-end; }
    #banco-indicadores .btn-custom-primary { background: var(--brand-burgundy-700); border-color: var(--brand-burgundy-700); color: var(--brand-white); font-weight: 700; }
    #banco-indicadores .btn-custom-primary:hover { background: var(--brand-burgundy-600); border-color: var(--brand-burgundy-600); color: var(--brand-white); }
    #banco-indicadores .filter-bar { background: var(--brand-neutral-100) !important; border: 1px solid var(--brand-neutral-100); border-radius: 0 !important; box-shadow: none !important; padding: 1rem !important; }
    #banco-indicadores .filter-bar .form-control-sm,
    #banco-indicadores .filter-bar .ts-control { min-height: 2.45rem; }
    #banco-indicadores .filter-bar .bi-filter-field { width: 100%; }
    #banco-indicadores .filter-bar .bi-filter-label { min-height: 1.15rem; }
    #banco-indicadores .filter-bar .bi-filter-action { min-height: 2.45rem; }
    #banco-indicadores .consultation-context { background: var(--brand-white); border: 1px solid var(--brand-green-700) !important; border-left: .3rem solid var(--brand-green-700) !important; border-radius: 0 !important; color: var(--brand-neutral-900); font-size: .9375rem; line-height: 1.5; }
    #banco-indicadores .consultation-guidance { background: var(--brand-sand-300); border-left: .3rem solid var(--brand-gold-500); border-radius: 0 !important; color: var(--brand-neutral-900); }
    #banco-indicadores .viz-wrapper { background: var(--brand-white); border: 1px solid var(--brand-neutral-100); min-height: 31.25rem; }
    #banco-indicadores #chart-container,
    #banco-indicadores #chart-container-regions { min-height: 31.25rem !important; }
    #banco-indicadores .metadata-block { border-color: var(--brand-neutral-400) !important; }
    #banco-indicadores .metadata-block .card { background: var(--brand-neutral-100) !important; border-radius: 0 !important; }
    #banco-indicadores .floating-map-overlay { border: 1px solid var(--brand-green-700); border-radius: 0 !important; }
    #banco-indicadores .print-header { display: none; }
    #banco-indicadores :focus-visible { outline: 3px solid var(--brand-gold-500); outline-offset: 2px; }

    @media (max-width: 767.98px) {
        #banco-indicadores { margin-top: 1rem !important; }
        #banco-indicadores .catalogo-col { position: static; margin-bottom: 1rem; }
        #banco-indicadores .toggle-catalogo-btn { display: none !important; }
        #banco-indicadores .bi-explorer { padding: 1rem; }
        #banco-indicadores .chart-panel__header { align-items: flex-start; flex-wrap: wrap; }
        #banco-indicadores .chart-panel__actions { justify-content: flex-start; width: 100%; }
        #banco-indicadores .chart-panel__actions .btn { flex: 1 1 auto; }
        #banco-indicadores .filter-bar .row > div { margin-bottom: .5rem; }
        #banco-indicadores .viz-wrapper,
        #banco-indicadores #chart-container,
        #banco-indicadores #chart-container-regions { min-height: 22rem !important; }
        #banco-indicadores .viz-wrapper.viz-wrapper--compact-mobile,
        #banco-indicadores #chart-container.chart-container--compact-mobile,
        #banco-indicadores #chart-container-regions.chart-container--compact-mobile { min-height: 19rem !important; }
    }

    @media (prefers-reduced-motion: reduce) {
        #banco-indicadores *, #banco-indicadores *::before, #banco-indicadores *::after { scroll-behavior: auto !important; transition: none !important; }
    }

    @media print {
        @page { margin: 12mm; }

        header.site-header {
            display: none !important;
        }

        #banco-indicadores { margin: 0 !important; }
        #banco-indicadores > .px-2 { padding: 0 !important; }
        #banco-indicadores .public-section-header,
        #banco-indicadores .catalogo-col,
        #banco-indicadores .filter-bar,
        #banco-indicadores .chart-panel__actions,
        #banco-indicadores .toggle-catalogo-btn,
        #banco-indicadores .consultation-guidance,
        #banco-indicadores [id^="consult-feedback"],
        #banco-indicadores .floating-map-overlay,
        #banco-indicadores [id^="export-btn"] {
            display: none !important;
        }

        #banco-indicadores .bi-layout,
        #banco-indicadores .content-col { display: block !important; width: 100% !important; }
        #banco-indicadores .print-header { align-items: center; border-bottom: 1px solid #262626; display: flex; gap: 1.5rem; justify-content: space-between; margin-bottom: 1rem; padding-bottom: .75rem; }
        #banco-indicadores .print-header img { height: 2.25rem; max-width: 48%; object-fit: contain; width: auto; }
        #banco-indicadores #pills-main-content > .tab-pane:not(.active) { display: none !important; }
        #banco-indicadores .bi-results-card { display: block; overflow: visible !important; border: 0 !important; box-shadow: none !important; }
        #banco-indicadores #view-summary,
        #banco-indicadores #view-summary-regions { display: none !important; }
        #banco-indicadores .card-header { padding: 0 0 1rem !important; }
        #banco-indicadores .viz-wrapper { border: 0; min-height: 0; }
        #banco-indicadores #chart-container,
        #banco-indicadores #chart-container-regions { min-height: 22rem !important; }
        #banco-indicadores .metadata-block { break-inside: auto; page-break-inside: auto; }
        #banco-indicadores .metadata-block::after { color: #687078; content: "Impreso el " attr(data-fecha-impresion); display: block; font-size: .75rem; margin-top: 1rem; }
    }
</style>
@endsection
@section('jss')
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="{{ asset('js/script-ficha.js') }}?v={{ time() }}"></script>
@endsection

@section('content')
<div id="banco-indicadores" class="container-fluid my-4" data-api-url="{{ route('api.data') }}" data-csrf-token="{{ csrf_token() }}"
    data-export-url="{{ route('banco-indicadores.exportar') }}">
    <div class="px-2 px-md-4">
        <x-public-section-header
            title="Banco de Indicadores"
            description="Consulta, compara y visualiza información estadística municipal, regional y estatal."
            eyebrow="Consulta estadística" />

        <div class="row g-4 bi-layout">
            {{-- Columna Lateral: Catálogo (Persistente) --}}
            <aside class="col-md-3 catalogo-col" aria-label="Explorador de indicadores">
                <div class="bi-explorer">
                <div class="bi-section-label">Explorador</div>
                <h2 class="bi-explorer-heading">Selecciona un indicador</h2>
                <p class="bi-explorer-copy">Elige el nivel territorial y navega por tema para iniciar tu consulta.</p>

                {{-- Selector de Nivel (Segmented Control) - Persistente --}}
                    <div class="level-switcher mb-3">
                        <div class="nav nav-pills nav-fill bg-light rounded-sm p-1 shadow-sm" id="pills-tab-nivel" role="tablist">
                        <button class="nav-link active py-1 px-2 small" id="pill-municipios-tab"
                            data-bs-target="#pane-municipios" type="button" role="tab" data-nivel="municipio"
                            aria-controls="pane-municipios" aria-selected="true" tabindex="0">Municipio</button>
                        <button class="nav-link py-1 px-2 small" id="pill-microrregiones-tab"
                            data-bs-target="#pane-regiones" type="button" role="tab" data-nivel="microrregion"
                            aria-controls="pane-regiones" aria-selected="false" tabindex="-1">Microrregión</button>
                        <button class="nav-link py-1 px-2 small" id="pill-macrorregiones-tab"
                            data-bs-target="#pane-regiones" type="button" role="tab" data-nivel="macrorregion"
                            aria-controls="pane-regiones" aria-selected="false" tabindex="-1">Macrorregión</button>
                        <button class="nav-link py-1 px-2 small" id="pill-estatal-tab"
                            data-bs-target="#pane-regiones" type="button" role="tab" data-nivel="estatal"
                            aria-controls="pane-regiones" aria-selected="false" tabindex="-1">Estatal</button>
                    </div>
                </div>

                {{-- Contenido de Búsqueda y Acordeón --}}
                <div class="tab-content" id="pills-sidebar-content">
                    {{-- Pane Municipal --}}
                    <div class="tab-pane fade show active" id="sidebar-pane-municipios" role="tabpanel" aria-hidden="false">
                        <div class="bi-search p-3 border-bottom bg-light rounded-3 mb-3">
                            <label class="visually-hidden" for="indicador-search">Buscar indicador municipal</label>
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
                                                                     data-tipo-dato="{{ $indicador->tipo_dato }}"
                                                                     data-disponible-estatal="{{ $indicador->disponible_estatal ? 'true' : 'false' }}">
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
                    <div class="tab-pane fade" id="sidebar-pane-regiones" role="tabpanel" aria-hidden="true">
                        <div class="bi-search p-3 border-bottom bg-light rounded-3 mb-3">
                            <label class="visually-hidden" for="indicador-search-regions">Buscar indicador regional</label>
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
            </aside>

            {{-- Columna Central/Derecha: Contenido y Visualización --}}
            <section class="col-md-9 content-col bi-workspace" aria-label="Resultados de la consulta">
                <div class="print-header">
                    <img src="{{ asset('img/Logos-SPFA_.png') }}" alt="Gobierno de Puebla">
                    <img src="{{ asset('img/logo-sei.png') }}" alt="Sistema Estatal de Información">
                </div>
                <div class="tab-content" id="pills-main-content">

                    {{-- Vista Municipal --}}
                    <div class="tab-pane fade show active" id="pane-municipios" role="tabpanel" tabindex="0"
                        aria-labelledby="pill-municipios-tab" aria-hidden="false">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bi-results-card">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <div class="chart-panel__header">
                                    <button class="btn btn-sm btn-outline-secondary toggle-catalogo-btn rounded-circle" data-bs-toggle="tooltip" title="Ocultar catálogo">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <h5 id="chart-title" class="chart-panel__title mb-0 fw-bold text-dark">Selecciona un municipio</h5>
                                    <div class="chart-panel__actions d-flex gap-2">
                                        <div id="resumen-container" style="display: none;">
                                            <a href="{{ route('municipios.show', ['municipio' => 'ID_PLACEHOLDER']) }}"
                                                id="resumen-btn" class="btn btn-sm btn-outline-info rounded-sm disabled" title="Resumen Municipal">
                                                <i class="fa-solid fa-file-invoice me-1"></i> Resumen
                                            </a>
                                        </div>
                                        <button id="toggle-map-btn" class="btn btn-sm btn-outline-primary rounded-sm">
                                            <i class="fas fa-map me-1"></i> Mapa
                                        </button>
                                        <button id="fullscreen-btn" class="btn btn-sm btn-outline-secondary rounded-sm"
                                            data-bs-toggle="modal" data-bs-target="#chart-fullscreen-modal" style="display: none;">
                                            <i class="fas fa-expand me-1"></i> Expandir
                                        </button>
                                        <button id="print-btn" type="button" class="btn btn-sm btn-outline-dark rounded-sm" style="display: none;">
                                            <i class="fas fa-print me-1"></i> Imprimir
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                {{-- Barra de Filtros Municipal --}}
                                <div class="filter-bar p-2 bg-light mb-3 rounded-4 shadow-sm">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5 d-flex">
                                                <div id="municipio-selector-container" class="bi-filter-field">
                                                    <label class="bi-section-label bi-filter-label d-block mb-1" for="municipio-selector">Territorio</label>
                                             <select id="municipio-selector" multiple class="form-control-sm">
                                                         @foreach ($municipios as $municipio)
                                                        <option value="{{ $municipio->id }}" data-cvegeo="{{ $municipio->cvegeo }}"
                                                            data-slug="{{ $municipio->slug }}"
                                                            data-orden="{{ $municipio->orden ?? 0 }}">
                                                            {{ $municipio->nombre }}
                                                        </option>
                                                        @endforeach
                                             </select>
                                          <p id="municipio-required-hint" class="municipio-required-hint mb-0" hidden>
                                              Selecciona al menos un municipio para consultar este indicador.
                                          </p>
                                      </div>
                                         </div>
                                        <div class="col-md-4 d-flex">
                                            <div id="year-selector-container" class="bi-filter-field" style="display: none;">
                                                <label class="bi-section-label bi-filter-label d-block mb-1" for="year-selector">Años</label>
                                                <select id="year-selector" multiple class="form-control-sm"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-flex">
                                            <div class="bi-filter-field">
                                                <span class="bi-section-label bi-filter-label d-block mb-1" aria-hidden="true">Acción</span>
                                                <button id="consultar-btn" class="btn btn-sm btn-custom-primary bi-filter-action w-100">
                                                    <i class="fas fa-search"></i> Consultar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contenedor de Gráfica --}}
                                <div id="consult-feedback" class="small text-muted mb-2 text-corra-regular" role="status" aria-live="polite" hidden>
                                    Elige un indicador y al menos un municipio para habilitar la consulta.
                                </div>
                                <div id="view-summary" class="consultation-context alert alert-light border rounded-4 py-2 px-3 small mb-3" role="status" aria-live="polite">
                                    Consulta actual: Aún no has seleccionado un indicador.
                                </div>
                                <div id="view-guidance" class="consultation-guidance alert alert-info border-0 rounded-4 py-2 px-3 small mb-3" hidden>
                                     Puedes seleccionar hasta 2 municipios. El nivel estatal está disponible para indicadores absolutos.
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
                                                <div class="d-flex align-items-center gap-2">
                                                    <button id="map-fullscreen-btn" type="button" class="btn btn-sm btn-link p-0 text-secondary" aria-label="Ver mapa a pantalla completa" title="Pantalla completa">
                                                        <i class="fas fa-expand" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="btn-close" style="font-size: 0.6rem;" aria-label="Cerrar mapa" onclick="document.getElementById('toggle-map-btn').click()"></button>
                                                </div>
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
                    <div class="tab-pane fade" id="pane-regiones" role="tabpanel" tabindex="0"
                        aria-hidden="true">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bi-results-card">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <div class="chart-panel__header">
                                    <button class="btn btn-sm btn-outline-secondary toggle-catalogo-btn rounded-circle" data-bs-toggle="tooltip" title="Ocultar catálogo">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <h5 id="chart-title-regions" class="chart-panel__title mb-0 fw-bold text-dark">Selecciona una región</h5>
                                    <div class="chart-panel__actions d-flex gap-2">
                                        <a href="{{ route('regiones.estatal.perfil') }}" class="btn btn-sm btn-outline-success rounded-sm">
                                            <i class="fas fa-globe-americas me-1"></i> Perfil estatal
                                        </a>
                                        <button id="toggle-map-btn-regions" class="btn btn-sm btn-outline-primary rounded-sm">
                                            <i class="fas fa-map me-1"></i> Mapa
                                        </button>
                                        <button id="fullscreen-btn-regions" class="btn btn-sm btn-outline-secondary rounded-sm"
                                            data-bs-toggle="modal" data-bs-target="#chart-fullscreen-modal" style="display: none;">
                                            <i class="fas fa-expand me-1"></i> Expandir
                                        </button>
                                        <button id="print-btn-regions" type="button" class="btn btn-sm btn-outline-dark rounded-sm" style="display: none;">
                                            <i class="fas fa-print me-1"></i> Imprimir
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                {{-- Barra de Filtros Regional --}}
                                <div class="filter-bar p-2 bg-light mb-3 rounded-4 shadow-sm">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5 d-flex">
                                            <div id="estatal-selector-container" style="display: none;">
                                                <span class="bi-section-label bi-filter-label d-block mb-1" aria-hidden="true">Territorio</span>
                                                <div class="form-control-sm bg-white border rounded px-3 py-2 text-muted">
                                                    <i class="fas fa-globe-americas me-2"></i>Estado de Puebla
                                                </div>
                                            </div>
                                            <div id="microrregion-selector-container" class="bi-filter-field">
                                                <label class="bi-section-label bi-filter-label d-block mb-1" for="microrregion-selector">Territorio</label>
                                                <select id="microrregion-selector" class="form-control-sm">
                                                    @foreach ($microrregiones as $region)
                                                    <option value="{{ $region->id }}">
                                                        {{ $region->nombre }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="macrorregion-selector-container" class="bi-filter-field" style="display: none;">
                                                <label class="bi-section-label bi-filter-label d-block mb-1" for="macrorregion-selector">Territorio</label>
                                                <select id="macrorregion-selector" class="form-control-sm">
                                                    @foreach ($macrorregiones as $region)
                                                    <option value="{{ $region->id }}">
                                                        {{ $region->nombre }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex">
                                            <div id="year-selector-container-regions" class="bi-filter-field" style="display: none;">
                                                <label class="bi-section-label bi-filter-label d-block mb-1" for="year-selector-regions">Años</label>
                                                <select id="year-selector-regions" multiple class="form-control-sm"></select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-flex">
                                            <div class="bi-filter-field">
                                                <span class="bi-section-label bi-filter-label d-block mb-1" aria-hidden="true">Acción</span>
                                                <button id="consultar-btn-regions" class="btn btn-sm btn-custom-primary bi-filter-action w-100">Consultar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contenedor de Gráfica (Regiones) --}}
                                <div class="viz-wrapper position-relative">
                                    <div id="consult-feedback-regions" class="small text-muted mb-2" role="status" aria-live="polite" hidden>
                                        Elige un indicador y una región para habilitar la consulta.
                                    </div>
                                    <div id="view-summary-regions" class="consultation-context alert alert-light border rounded-sm py-2 px-3 small mb-3 text-corra-regular" role="status" aria-live="polite">
                                        Consulta actual: Aún no has seleccionado un indicador regional.
                                    </div>
                                    <div id="view-guidance-regions" class="consultation-guidance alert alert-info border-0 rounded-sm py-2 px-3 small mb-3 text-corra-regular" hidden>
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
                                                <div class="d-flex align-items-center gap-2">
                                                    <button id="map-fullscreen-btn-regions" type="button" class="btn btn-sm btn-link p-0 text-secondary" aria-label="Ver mapa a pantalla completa" title="Pantalla completa">
                                                        <i class="fas fa-expand" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="btn-close" style="font-size: 0.6rem;" aria-label="Cerrar mapa" onclick="document.getElementById('toggle-map-btn-regions').click()"></button>
                                                </div>
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
            </section>
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
