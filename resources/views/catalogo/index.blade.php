<x-admin-layout>
    @section('title', 'Gestión de Catálogos')
    <!-- local -->
    {{-- HEADER --}}
    <x-page-header
        title="Gestión de Catálogos"
        subtitle="Administra la estructura de Dimensiones, Temáticas, Indicadores y Variables"
        icon="fa-solid fa-layer-group" />

    {{-- SCRIPTS ALERTAS --}}
    @if ($message = Session::get('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ $message }}',
            confirmButtonColor: '#5f1b2d'
        }));
    </script>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger mb-4 shadow-sm border-0 border-start border-danger">
        <i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}
    </div>
    @endif

    <div class="container py-4">

        {{-- BARRA DE ACCIONES SUPERIOR --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">

            {{-- Botón Exportar (Dropdown Estilizado) --}}
            <div class="dropdown">
                <button class="btn btn-white border shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-file-export me-2 text-success"></i>Exportar Catálogos
                </button>
                <ul class="dropdown-menu shadow border-0">
                    <li>
                        <h6 class="dropdown-header text-uppercase small text-muted">Selecciona nivel</h6>
                    </li>
                    <li><a class="dropdown-item" href="{{ route('admin.catalogos.export.dimensiones') }}"><i class="fa-solid fa-ruler-combined me-2 text-muted"></i>Dimensiones</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.catalogos.export.tematicas') }}"><i class="fa-solid fa-layer-group me-2 text-muted"></i>Temáticas</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.catalogos.export.indicadores') }}"><i class="fa-solid fa-chart-pie me-2 text-muted"></i>Indicadores</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.catalogos.export.variables') }}"><i class="fa-solid fa-tags me-2 text-muted"></i>Variables</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item fw-bold text-vino" href="{{ route('admin.datos.export') }}"><i class="fa-solid fa-database me-2"></i>Exportar TODO</a></li>
                </ul>
            </div>

            {{-- Botón Añadir Dimensión --}}
            <button class="btn btn-custom-primary shadow-sm px-4"
                data-bs-toggle="modal" data-bs-target="#catalogModal"
                data-tipo="Dimension" data-template="dimension"
                data-url="{{ route('admin.catalogos.dimensions.store') }}">
                <i class="fa-solid fa-plus me-2"></i>Nueva Dimensión
            </button>
        </div>

        {{-- CONTENEDOR PRINCIPAL --}}
        <div class="card-panel">
            <div class="card-body p-4">

                {{-- PESTAÑAS DE DIMENSIONES --}}
                <ul class="nav nav-tabs nav-tabs-clean mb-4" id="dimensionTab" role="tablist">
                    @foreach ($dimensiones as $dimension)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                            id="tab-{{ $dimension->id }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $dimension->id }}"
                            type="button" role="tab">
                            {{ $dimension->nombre }}
                        </button>
                    </li>
                    @endforeach
                </ul>

                {{-- CONTENIDO DE CADA DIMENSIÓN --}}
                <div class="tab-content" id="dimensionTabContent">
                    @foreach ($dimensiones as $dimension)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pane-{{ $dimension->id }}" role="tabpanel">

                        {{-- Cabecera de la Dimensión --}}
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <span class="badge rounded-pill bg-light text-dark border me-3 px-3 py-2">ID: {{ $dimension->id }}</span>
                                <h4 class="mb-0 text-vino">{{ $dimension->nombre }}</h4>
                                <span class="ms-3 badge" style="background-color: {{ $dimension->color }}; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    Color Etiqueta
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn-icon-square edit" data-bs-toggle="modal"
                                    data-bs-target="#catalogModal" data-nombre="{{ $dimension->nombre }}"
                                    data-nombre-tecnico="{{ $dimension->nombre_tecnico }}"
                                    data-color="{{ $dimension->color }}" data-tipo="Dimension" data-template="dimension"
                                    data-url="{{ route('admin.catalogos.dimensions.update', $dimension) }}">
                                    <i class="fa-solid fa-pen" data-bs-toggle="tooltip" title="Editar Dimensión"></i>
                                </button>
                                <button class="btn-icon-square danger delete-btn"
                                    data-url="{{ route('admin.catalogos.dimensions.destroy', $dimension) }}"
                                    data-name="{{ $dimension->nombre }}">
                                    <i class="fa-solid fa-trash" data-bs-toggle="tooltip" title="Eliminar Dimensión"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Botón Añadir Temática --}}
                        <div class="text-end mb-4">
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#catalogModal" data-tipo="Temática" data-template="tematica"
                                data-parent-id="{{ $dimension->id }}"
                                data-url="{{ route('admin.catalogos.tematicas.store') }}">
                                <i class="fa-solid fa-plus me-2"></i>Añadir Temática a {{ $dimension->nombre }}
                            </button>
                        </div>

                        {{-- LISTADO DE TEMÁTICAS --}}
                        @forelse($dimension->tematicas as $tematica)
                        <div class="theme-box">
                            {{-- Header de Temática --}}
                            <div class="theme-header d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="id-badge">#{{ $tematica->id }}</span>
                                    <h5 class="mb-0 text-secondary fw-bold">{{ $tematica->nombre }}</h5>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <button class="btn btn-link text-decoration-none btn-sm p-0 me-2" data-bs-toggle="modal"
                                        data-bs-target="#catalogModal" data-tipo="Indicador" data-template="indicador"
                                        data-parent-id="{{ $tematica->id }}"
                                        data-url="{{ route('admin.catalogos.indicadores.store') }}">
                                        <i class="fa-solid fa-plus me-1"></i>Indicador
                                    </button>
                                    <div class="vr mx-2"></div>
                                    <button class="btn-icon-square edit sm" style="width: 32px; height: 32px;"
                                        data-bs-toggle="modal" data-bs-target="#catalogModal"
                                        data-tipo="Temática" data-template="tematica"
                                        data-nombre="{{ $tematica->nombre }}"
                                        data-nombre-tecnico="{{ $tematica->nombre_tecnico }}"
                                        data-url="{{ route('admin.catalogos.tematicas.update', $tematica) }}">
                                        <i class="fa-solid fa-pen small"></i>
                                    </button>
                                    <button class="btn-icon-square danger sm delete-btn" style="width: 32px; height: 32px;"
                                        data-name="{{ $tematica->nombre }}"
                                        data-url="{{ route('admin.catalogos.tematicas.destroy', $tematica) }}">
                                        <i class="fa-solid fa-trash small"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Cuerpo: Lista de Indicadores --}}
                            <div class="p-0">
                                @forelse ($tematica->indicadores as $indicador)
                                <div class="indicator-row p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <span class="id-badge">#{{ $indicador->id }}</span>
                                            <div>
                                                <span class="fw-medium text-dark">{{ $indicador->nombre_amigable }}</span>
                                                @if($indicador->es_complejo)
                                                <span class="badge bg-light text-secondary border ms-2" style="font-size: 0.65rem;">Complejo</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            {{-- Botón Collapse Variables --}}
                                            <button class="btn btn-sm btn-light border text-muted" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#variables-for-indicador-{{ $indicador->id }}">
                                                <small>{{ $indicador->variables->count() }} Variables</small>
                                                <i class="fa-solid fa-chevron-down ms-1 small"></i>
                                            </button>

                                            <button class="btn-icon-square edit sm" style="width: 30px; height: 30px;"
                                                data-bs-toggle="modal" data-bs-target="#catalogModal"
                                                data-tipo="Indicador" data-template="indicador"
                                                data-url="{{ route('admin.catalogos.indicadores.update', $indicador) }}"
                                                data-nombre-amigable="{{ $indicador->nombre_amigable }}"
                                                data-nombre-tecnico="{{ $indicador->nombre_tecnico }}"
                                                data-tematica-id="{{ $indicador->tematica_id }}"
                                                data-descripcion="{{ $indicador->descripcion }}"
                                                data-fuente="{{ $indicador->fuente }}"
                                                data-metodo-calculo="{{ $indicador->metodo_calculo }}"
                                                data-tipo-grafico-default="{{ $indicador->tipo_grafico_default }}"
                                                data-solo-resumen="{{ $indicador->solo_resumen ? '1' : '0' }}"
                                                data-es-complejo="{{ $indicador->es_complejo ? '1' : '0' }}"
                                                data-priorizar-total="{{ $indicador->priorizar_total ? '1' : '0' }}">
                                                <i class="fa-solid fa-pen" style="font-size: 0.7rem;"></i>
                                            </button>
                                            <button class="btn-icon-square danger sm delete-btn" style="width: 30px; height: 30px;"
                                                data-name="{{ $indicador->nombre_amigable }}"
                                                data-url="{{ route('admin.catalogos.indicadores.destroy', $indicador) }}">
                                                <i class="fa-solid fa-trash" style="font-size: 0.7rem;"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- VARIABLES (Colapsable) --}}
                                    <div class="collapse mt-3" id="variables-for-indicador-{{ $indicador->id }}">
                                        <div class="variables-container p-3 rounded-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Variables Asociadas</small>
                                                <button class="btn btn-sm btn-outline-success py-0" style="font-size: 0.8rem;"
                                                    data-bs-toggle="modal" data-bs-target="#catalogModal"
                                                    data-tipo="Variable" data-template="variable"
                                                    data-indicador-id="{{ $indicador->id }}"
                                                    data-url="{{ route('admin.catalogos.variables.store') }}">
                                                    <i class="fa-solid fa-plus me-1"></i>Nueva
                                                </button>
                                            </div>

                                            @forelse ($indicador->variables as $variable)
                                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                                <div class="small">
                                                    <span class="text-muted me-2">#{{ $variable->id }}</span>
                                                    <span class="fw-bold text-dark">{{ $variable->nombre_amigable }}</span>
                                                    <span class="text-muted fst-italic ms-1">({{ $variable->nombre_tecnico }})</span>
                                                    @if($variable->es_kpi) <i class="fa-solid fa-star text-warning ms-1" title="KPI"></i> @endif
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-link text-secondary p-0 edit-btn" {{-- <--- AGREGAR "edit-btn" AQUÍ --}}
                                                        data-bs-toggle="modal" data-bs-target="#catalogModal"
                                                        data-tipo="Variable" data-template="variable"
                                                        data-url="{{ route('admin.catalogos.variables.update', $variable) }}"
                                                        data-nombre-amigable="{{ $variable->nombre_amigable }}"
                                                        data-nombre-tecnico="{{ $variable->nombre_tecnico }}"
                                                        data-unidad-medida="{{ $variable->unidad_medida }}"
                                                        data-es-kpi="{{ $variable->es_kpi ? '1' : '0' }}"
                                                        data-es-destacada="{{ $variable->es_destacada ? '1' : '0' }}"
                                                        data-indicador-tipo-dato="{{ $variable->indicador->tipo_dato }}"
                                                        data-indicador-id="{{ $variable->indicador_id }}">
                                                        <i class="fa-solid fa-pen" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <button class="btn btn-link text-danger p-0 delete-btn"
                                                        data-name="{{ $variable->nombre_amigable }}"
                                                        data-url="{{ route('admin.catalogos.variables.destroy', $variable) }}">
                                                        <i class="fa-solid fa-trash" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @empty
                                            <div class="text-muted small fst-italic">Sin variables registradas.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div> {{-- Fin Indicator Row --}}
                                @empty
                                <div class="p-4 text-center text-muted">
                                    <i class="fa-solid fa-folder-open mb-2 opacity-50"></i>
                                    <p class="small mb-0">Esta temática está vacía.</p>
                                </div>
                                @endforelse
                            </div>
                        </div> {{-- Fin Theme Box --}}
                        @empty
                        <div class="text-center py-5">
                            <div class="mb-3"><i class="fa-solid fa-layer-group fa-3x text-muted opacity-25"></i></div>
                            <h5 class="text-muted">No hay temáticas en esta dimensión.</h5>
                            <p class="text-muted small">Comienza añadiendo una con el botón de arriba.</p>
                        </div>
                        @endforelse

                    </div> {{-- Fin Pane Dimensión --}}
                    @endforeach
                </div> {{-- Fin Tab Content --}}

            </div>
        </div>
    </div>

    {{-- MODAL UNIFICADO (Estilo Renovado) --}}
    {{-- MODAL UNIFICADO (Estilo Clean - Igual a Datos Históricos) --}}
    <div class="modal fade" id="catalogModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                {{-- CABECERA: Fondo Blanco + Texto Vino (Igual que Datos Históricos) --}}
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-vino" id="modalTitle">
                        {{-- El contenido se llena con JS --}}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="catalogForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod">
                    <input type="hidden" name="parent_id" id="parentId">

                    <div class="modal-body p-4">
                        {{-- 1. DIMENSIÓN --}}
                        <div id="form-template-dimension" class="form-template" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Nombre de la Dimensión</label>
                                    <input type="text" name="nombre" id="dim_nombre" class="form-control text-vino fw-bold" placeholder="Ej: Social">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Nombre Técnico</label>
                                    <input type="text" name="nombre_tecnico" id="dim_nombre_tecnico" class="form-control font-monospace text-muted" placeholder="Ej: dim_social">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small">Color Identificador</label>
                                    <input type="color" name="color" id="dim_color" class="form-control form-control-color w-100" title="Elige un color">
                                </div>
                            </div>
                        </div>

                        {{-- 2. TEMÁTICA --}}
                        <div id="form-template-tematica" class="form-template" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-secondary small">Nombre de la Temática</label>
                                    <input type="text" name="nombre" id="tem_nombre" class="form-control text-vino fw-bold">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-secondary small">Nombre Técnico</label>
                                    <input type="text" name="nombre_tecnico" id="tem_nombre_tecnico" class="form-control font-monospace text-muted">
                                </div>
                            </div>
                        </div>

                        {{-- 3. INDICADOR --}}
                        <div id="form-template-indicador" class="form-template" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Nombre Amigable</label>
                                    <input type="text" name="nombre_amigable" id="ind_nombre_amigable" class="form-control text-vino fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Nombre Técnico</label>
                                    <input type="text" name="nombre_tecnico" id="ind_nombre_tecnico" class="form-control font-monospace text-muted">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small">Temática</label>
                                    <select name="tematica_id" id="ind_tematica_id" class="form-select text-dark">
                                        <option value="">Automático (Heredar)</option>
                                        @foreach ($tematicas as $t)
                                        <option value="{{ $t->id }}">{{ $t->nombre }} ({{ $t->dimension->nombre }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small">Descripción</label>
                                    <textarea name="descripcion" id="ind_descripcion" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Fuente</label>
                                    <input type="text" name="fuente" id="ind_fuente" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Tipo de Dato</label>
                                    <select name="tipo_dato" id="ind_tipo_dato" class="form-select">
                                        <option value="absoluto">Absoluto</option>
                                        <option value="porcentaje">Porcentaje</option>
                                        <option value="tasa">Tasa</option>
                                        <option value="indice">Índice</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small">Método de Cálculo</label>
                                    <textarea name="metodo_calculo" id="ind_metodo_calculo" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="col-12 bg-light p-3 rounded border mt-2">
                                    <p class="fw-bold small mb-2 text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.5px;">Configuración Avanzada</p>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="solo_resumen" value="1" id="ind_solo_resumen">
                                        <label class="form-check-label small" for="ind_solo_resumen">Solo Resumen</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="es_complejo" value="1" id="ind_es_complejo">
                                        <label class="form-check-label small" for="ind_es_complejo">Complejo</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="priorizar_total" value="1" id="ind_priorizar_total">
                                        <label class="form-check-label small" for="ind_priorizar_total">Priorizar "Total"</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 4. VARIABLE --}}
                        <div id="form-template-variable" class="form-template" style="display: none;">
                            <input type="hidden" name="indicador_id" id="var_indicador_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Nombre Amigable</label>
                                    <input type="text" name="nombre_amigable" id="var_nombre_amigable" class="form-control text-vino fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Nombre Técnico</label>
                                    <input type="text" name="nombre_tecnico" id="var_nombre_tecnico" class="form-control font-monospace text-muted">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small">Unidad de Medida</label>
                                    <input type="text" name="unidad_medida" id="var_unidad_medida" class="form-control">
                                </div>

                                <div class="col-12 bg-light p-3 rounded border mt-2">
                                    <div id="var-es-destacada-container" class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="es_destacada" value="1" id="var_es_destacada">
                                        <label class="form-check-label small" for="var_es_destacada">Variable Destacada</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="es_kpi" value="1" id="var_es_kpi">
                                        <label class="form-check-label small" for="var_es_kpi">Es KPI</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- FOOTER: Gris Claro + Botones Outline (Igual que Datos Históricos) --}}
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-custom-primary px-4">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPTS JS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Inicializar Tooltips de Bootstrap (opcional, para que se vean bonitos)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // 2. Referencias a elementos del DOM
            const modalElement = document.getElementById('catalogModal');
            const catalogModal = new bootstrap.Modal(modalElement);
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('catalogForm');
            const formMethodInput = document.getElementById('formMethod');
            const parentIdInput = document.getElementById('parentId'); // Input oculto genérico

            // 3. Evento: Al abrir el modal
            modalElement.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                // Extraer datos del botón
                const tipo = button.dataset.tipo;
                const template = button.dataset.template;
                const url = button.dataset.url;
                const esModoEditar = button.classList.contains('edit-btn') || button.classList.contains('edit');

                // Limpiar formulario y ocultar todos los templates
                form.reset();
                document.querySelectorAll('.form-template').forEach(tpl => tpl.style.display = 'none');

                // Mostrar el template correcto
                const activeTemplate = document.getElementById(`form-template-${template}`);
                if (activeTemplate) {
                    activeTemplate.style.display = 'block';
                } else {
                    console.error(`Error: No se encontró el template form-template-${template}`);
                    return;
                }

                // --- CORRECCIÓN CLAVE PARA VARIABLES ---
                // Si es una variable, manejamos la lógica de "Variable Destacada" y asignamos IDs
                if (template === 'variable') {
                    const indicadorTipoDato = button.dataset.indicadorTipoDato;
                    const esDestacadaContainer = activeTemplate.querySelector('#var-es-destacada-container');

                    // Mostrar switch de "Destacada" solo si es dato absoluto
                    if (esDestacadaContainer) {
                        if (indicadorTipoDato && indicadorTipoDato.toLowerCase() === 'absoluto') {
                            esDestacadaContainer.style.display = 'block';
                        } else {
                            esDestacadaContainer.style.display = 'none';
                        }
                    }

                    // ASIGNACIÓN DE ID PADRE (INDICADOR):
                    // Buscamos el ID en el botón. Puede venir como data-indicador-id
                    const indicadorId = button.dataset.indicadorId;

                    if (indicadorId) {
                        // 1. Lo ponemos en el input específico de variables
                        const varIndInput = document.getElementById('var_indicador_id');
                        if (varIndInput) varIndInput.value = indicadorId;

                        // 2. IMPORTANTE: Lo ponemos también en el input genérico parent_id
                        // (Esto suele solucionar el error "El campo elemento padre es obligatorio")
                        if (parentIdInput) parentIdInput.value = indicadorId;
                    }
                }

                // Configurar Título y Acción del Formulario
                form.action = url;
                modalTitle.innerHTML = esModoEditar ?
                    `<i class="fa-solid fa-pen-to-square me-2 text-dorado"></i>Editar ${tipo}` :
                    `<i class="fa-solid fa-plus me-2 text-dorado"></i>Añadir ${tipo}`;

                formMethodInput.value = esModoEditar ? 'PUT' : 'POST';

                // Llenar campos si es Edición
                if (esModoEditar) {
                    activeTemplate.querySelectorAll('input[name], select[name], textarea[name]').forEach(input => {
                        const fieldName = input.name;
                        // Convertir snake_case a camelCase (ej. nombre_tecnico -> nombreTecnico)
                        const camelCaseFieldName = fieldName.replace(/_([a-z])/g, g => g[1].toUpperCase());

                        // Si el dato existe en el botón, lo asignamos
                        if (button.dataset[camelCaseFieldName] !== undefined) {
                            if (input.type === 'checkbox') {
                                input.checked = button.dataset[camelCaseFieldName] == '1';
                            } else {
                                input.value = button.dataset[camelCaseFieldName];
                            }
                        }
                    });
                } else {
                    // Si es Crear Nuevo, asignar parent_id genérico si viene en el botón (ej. para Temáticas o Indicadores)
                    // (Para variables ya lo manejamos arriba explícitamente)
                    if (button.dataset.parentId) {
                        parentIdInput.value = button.dataset.parentId;
                    }
                }
            });

            // 4. Evento: Enviar Formulario (AJAX)
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Identificar qué template está visible
                const activeTemplate = form.querySelector('.form-template[style*="block"]');
                if (!activeTemplate) return;

                const formData = new FormData();

                // Agregar campos del template visible
                activeTemplate.querySelectorAll('input, select, textarea').forEach(input => {
                    if (input.type === 'checkbox') {
                        if (input.checked) formData.append(input.name, input.value);
                    } else {
                        formData.append(input.name, input.value);
                    }
                });

                // Agregar campos ocultos globales
                formData.append('_method', formMethodInput.value);
                formData.append('parent_id', parentIdInput.value);

                // REFUERZO: Asegurar que indicador_id vaya en el request si es una variable
                if (activeTemplate.id === 'form-template-variable') {
                    const varIndId = document.getElementById('var_indicador_id').value;
                    // Si existe un valor, lo agregamos explícitamente (por si acaso el loop anterior no lo agarró)
                    if (varIndId) formData.append('indicador_id', varIndId);
                }

                // Petición Fetch
                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: new URLSearchParams(formData)
                    })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (ok) {
                            // --- PRESERVAR ESTADO ANTES DE RECARGAR ---
                            const activeTabId = document.querySelector('#dimensionTab .nav-link.active')?.id;
                            const openAccordions = Array.from(document.querySelectorAll('.indicator-row .collapse.show'))
                                .map(el => el.id);

                            sessionStorage.setItem('catalog_active_tab', activeTabId);
                            sessionStorage.setItem('catalog_open_accordions', JSON.stringify(openAccordions));

                            catalogModal.hide();
                            Swal.fire({
                                icon: 'success',
                                title: '¡Operación Exitosa!',
                                confirmButtonColor: '#5f1b2d',
                                timer: 1000,
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        } else {
                            // Manejo de errores de validación
                            const errorMessages = Object.values(data.errors || {}).map(error => `<li>${error[0]}</li>`).join('');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de Validación',
                                html: `<ul class="text-start small text-danger">${errorMessages}</ul>`,
                                confirmButtonColor: '#af1731'
                            });
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire('Error', 'Ocurrió un error inesperado al procesar la solicitud.', 'error');
                    });
            });

            // 5. Evento: Eliminar (SweetAlert)
            document.body.addEventListener('click', function(e) {
                // Usamos closest para detectar clicks en el icono dentro del botón
                const deleteButton = e.target.closest('.delete-btn');
                if (deleteButton) {
                    Swal.fire({
                        title: `¿Eliminar "${deleteButton.dataset.name}"?`,
                        text: "Se eliminarán también todos los elementos dependientes. ¡No hay vuelta atrás!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#af1731', // Tu color rojo
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(deleteButton.dataset.url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            }).then(response => {
                                if (response.ok) {
                                    // ELIMINACIÓN SIN RECARGA (UI SMOOTH) - Excepto para Dimensiones
                                    const isDimension = deleteButton.dataset.url.includes('dimensions');

                                    if (isDimension) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Dimensión Eliminada!',
                                            confirmButtonColor: '#5f1b2d',
                                            timer: 1000,
                                            showConfirmButton: false
                                        }).then(() => window.location.reload());
                                        return;
                                    }

                                    const row = deleteButton.closest('.indicator-row') ||
                                        deleteButton.closest('.theme-box') ||
                                        deleteButton.closest('.border-bottom.border-light') ||
                                        deleteButton.closest('.tab-pane');

                                    if (row) {
                                        row.style.transition = 'all 0.5s ease';
                                        row.style.opacity = '0';
                                        row.style.transform = 'translateX(20px)';
                                        setTimeout(() => row.remove(), 500);
                                    }

                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Eliminado!',
                                        text: 'El elemento ha sido eliminado correctamente.',
                                        confirmButtonColor: '#5f1b2d',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire('Error', 'No se pudo eliminar el elemento. Puede tener datos asociados.', 'error');
                                }
                            });
                        }
                    });
                }
            });

            // 6. RESTAURAR ESTADO (Pestaña y Acordeones)
            const savedTabId = sessionStorage.getItem('catalog_active_tab');
            const savedAccordions = JSON.parse(sessionStorage.getItem('catalog_open_accordions') || '[]');

            if (savedTabId) {
                const tabEl = document.getElementById(savedTabId);
                if (tabEl) {
                    const tab = new bootstrap.Tab(tabEl);
                    tab.show();
                }
                sessionStorage.removeItem('catalog_active_tab');
            }

            if (savedAccordions.length > 0) {
                savedAccordions.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        const collapse = new bootstrap.Collapse(el, {
                            toggle: false
                        });
                        collapse.show();
                    }
                });
                sessionStorage.removeItem('catalog_open_accordions');
            }
        });
    </script>
</x-admin-layout>