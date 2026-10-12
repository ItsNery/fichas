<x-admin-layout>
    @section('title', $indicador ? 'Editar Indicador' : 'Crear Indicador')

    <x-page-header :title="$indicador ? 'Editar Indicador' : 'Crear Indicador'" :subtitle="$indicador
        ? 'Modifica los datos del indicador y sus variables'
        : 'Nuevo indicador con gestión completa de variables'" icon="fa-solid fa-chart-simple" />

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
        <div class="alert alert-danger mb-4 shadow-sm border-0 border-start border-danger"><i
                class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
    @endif

    <style>
        .indicator-workspace {
            --indicator-wine: #651b2c;
            --indicator-wine-soft: #f8eef0;
            --indicator-ink: #27313a;
        }

        .indicator-intro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            color: #fff;
            background: linear-gradient(125deg, #4f1423 0%, #792238 58%, #9d6b31 140%);
            border-radius: 1.25rem;
            box-shadow: 0 18px 45px rgba(79, 20, 35, .16);
        }

        .indicator-intro__eyebrow { font-size: .72rem; letter-spacing: .12em; text-transform: uppercase; opacity: .72; }
        .indicator-intro__title { margin: .2rem 0 0; font-size: 1.15rem; font-weight: 700; }
        .indicator-intro__hint { max-width: 32rem; margin: 0; font-size: .86rem; opacity: .84; }
        .indicator-sidebar { position: sticky; top: 1rem; padding: 1.15rem; background: #f7f5f2; border: 1px solid #e5e0dc; border-radius: 1.25rem; }
        .indicator-step { display: flex; align-items: center; gap: .75rem; padding: .85rem; color: #59616a; text-decoration: none; border-radius: .85rem; }
        .indicator-step:hover { color: var(--indicator-wine); background: #fff; }
        .indicator-step__number { display: grid; flex: 0 0 1.8rem; width: 1.8rem; height: 1.8rem; place-items: center; color: var(--indicator-wine); background: #fff; border-radius: 50%; font-size: .72rem; font-weight: 800; }
        .indicator-step strong, .indicator-step small { display: block; }
        .indicator-step small { margin-top: .1rem; font-size: .68rem; color: #7b838a; }
        .indicator-status { padding-top: 1rem; margin-top: 1rem; border-top: 1px solid #ded9d5; }
        .indicator-status__title { margin-bottom: .75rem; color: var(--indicator-ink); font-size: .75rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .indicator-section { overflow: hidden; border: 0; border-radius: 1.25rem; box-shadow: 0 6px 20px rgba(39, 49, 58, .06); }
        .indicator-section .card-header { padding: 1rem 1.5rem; }
        .indicator-section .card-body { padding: 1.5rem; }
        .indicator-section-title { margin: 0; color: var(--indicator-ink); font-size: 1rem; font-weight: 700; }
        .indicator-section-lead { margin: .2rem 0 0; color: #747c84; font-size: .82rem; }
        .indicator-actions { position: sticky; bottom: 0; z-index: 4; display: flex; justify-content: flex-end; gap: .75rem; padding: 1rem 0; margin-top: 1rem; background: linear-gradient(transparent, #fff 25%); }

        @media (max-width: 991.98px) {
            .indicator-sidebar { position: static; }
            .indicator-steps { display: flex; gap: .35rem; overflow-x: auto; }
            .indicator-step { flex: 1 0 max-content; }
        }

        @media (max-width: 767.98px) {
            .indicator-intro { align-items: flex-start; flex-direction: column; }
            .indicator-section .card-body { padding: 1.25rem; }
            .indicator-step small { display: none; }
        }
    </style>

    <form method="POST"
        action="{{ $indicador ? route('admin.catalogos.indicadores.actualizar', $indicador) : route('admin.catalogos.indicadores.guardar') }}"
        id="indicadorForm">
        @csrf
        @if ($indicador)
            @method('PUT')
        @endif

        <div class="container py-4 pb-5 indicator-workspace">
            <section class="indicator-intro">
                <div>
                    <div class="indicator-intro__eyebrow">Catálogo de indicadores</div>
                    <p class="indicator-intro__title">{{ $indicador?->nombre_amigable ?? 'Nuevo indicador' }}</p>
                </div>
                <p class="indicator-intro__hint"><i class="fa-solid fa-circle-info me-2"></i>Configura primero los datos generales, después las variables y, cuando aplique, guarda para calcular sus resultados.</p>
            </section>

            <div class="row g-4">
                <aside class="col-lg-3">
                    <div class="indicator-sidebar">
                        <nav class="indicator-steps" aria-label="Secciones del formulario">
                            <a class="indicator-step" href="#indicator-details">
                                <span class="indicator-step__number">1</span>
                                <span><strong>Información</strong><small>Datos generales</small></span>
                            </a>
                            <a class="indicator-step" href="#indicator-variables">
                                <span class="indicator-step__number">2</span>
                                <span><strong>Variables</strong><small>Valores y fórmulas</small></span>
                            </a>
                            <a class="indicator-step" href="#generationPanel">
                                <span class="indicator-step__number">3</span>
                                <span><strong>Aplicar fórmula</strong><small>Previsualizar y generar</small></span>
                            </a>
                        </nav>

                        <div class="indicator-status">
                            <div class="indicator-status__title">Publicación y opciones</div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="visible_en_ficha" value="1"
                                    id="ind_visible_en_ficha" {{ $indicador->visible_en_ficha ?? true ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="ind_visible_en_ficha">Visible en fichas públicas</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="solo_resumen" value="1"
                                    id="ind_solo_resumen" {{ $indicador->solo_resumen ?? false ? 'checked' : '' }}>
                                <label class="form-check-label small" for="ind_solo_resumen">Solo resumen</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="priorizar_total" value="1"
                                    id="ind_priorizar_total" {{ $indicador->priorizar_total ?? false ? 'checked' : '' }}>
                                <label class="form-check-label small" for="ind_priorizar_total">Priorizar &quot;Total&quot;</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="es_complejo" value="1"
                                    id="ind_es_complejo" {{ $indicador->es_complejo ?? false ? 'checked' : '' }}>
                                <label class="form-check-label small" for="ind_es_complejo">Indicador complejo</label>
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="col-lg-9">
        {{-- PANEL 1: DATOS DEL INDICADOR --}}
        <div class="card-panel indicator-section mb-4" id="indicator-details">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-vino" style="width:2.25rem; height:2.25rem;"><i class="fa-solid fa-info-circle"></i></span>
                <div>
                    <h5 class="indicator-section-title">Información del indicador</h5>
                    <p class="indicator-section-lead">Nombre, clasificación, fuente y criterios de lectura.</p>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Nombre Amigable <span
                                class="text-danger">*</span></label>
                        <input type="text" name="nombre_amigable" id="indicador_nombre_amigable"
                            class="form-control text-vino fw-bold"
                            value="{{ $indicador->nombre_amigable ?? old('nombre_amigable') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Nombre Técnico <span
                                class="text-danger">*</span></label>
                        <input type="text" name="nombre_tecnico" id="indicador_nombre_tecnico"
                            class="form-control font-monospace text-muted"
                            value="{{ $indicador->nombre_tecnico ?? old('nombre_tecnico') }}"
                            data-auto-gen="{{ $indicador ? 'false' : 'true' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Temática <span
                                class="text-danger">*</span></label>
                        <select name="tematica_id" class="form-select" required>
                            <option value="">Selecciona...</option>
                            @foreach ($tematicas as $t)
                                <option value="{{ $t->id }}"
                                    {{ ($indicador->tematica_id ?? old('tematica_id', $tematicaId ?? null)) == $t->id ? 'selected' : '' }}>
                                    {{ $t->nombre }} ({{ $t->dimension->nombre }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Tipo de Dato</label>
                        <select name="tipo_dato" class="form-select">
                            <option value="absoluto"
                                {{ ($indicador->tipo_dato ?? '') == 'absoluto' ? 'selected' : '' }}>Absoluto</option>
                            <option value="porcentaje"
                                {{ ($indicador->tipo_dato ?? '') == 'porcentaje' ? 'selected' : '' }}>Porcentaje
                            </option>
                            <option value="tasa" {{ ($indicador->tipo_dato ?? '') == 'tasa' ? 'selected' : '' }}>
                                Tasa</option>
                            <option value="indice" {{ ($indicador->tipo_dato ?? '') == 'indice' ? 'selected' : '' }}>
                                Índice</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Polaridad</label>
                        <select name="polaridad" class="form-select">
                            <option value="neutro" {{ ($indicador->polaridad ?? '') == 'neutro' ? 'selected' : '' }}>
                                Neutro</option>
                            <option value="asendente"
                                {{ ($indicador->polaridad ?? '') == 'asendente' ? 'selected' : '' }}>Ascendente
                            </option>
                            <option value="descendente"
                                {{ ($indicador->polaridad ?? '') == 'descendente' ? 'selected' : '' }}>Descendente
                            </option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-secondary small">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2">{{ $indicador->descripcion ?? old('descripcion') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Fuente</label>
                        <input type="text" name="fuente" class="form-control"
                            value="{{ $indicador->fuente ?? old('fuente') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Tipo gráfico default</label>
                        <select name="tipo_grafico_default" class="form-select">
                            <option value="">Automático</option>
                            <option value="Barras"
                                {{ ($indicador->tipo_grafico_default ?? '') == 'Barras' ? 'selected' : '' }}>Barras
                            </option>
                            <option value="Lineal"
                                {{ ($indicador->tipo_grafico_default ?? '') == 'Lineal' ? 'selected' : '' }}>Lineal
                            </option>
                            <option value="Piramide"
                                {{ ($indicador->tipo_grafico_default ?? '') == 'Piramide' ? 'selected' : '' }}>
                                Pirámide</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Orden</label>
                        <input type="number" name="orden" class="form-control"
                            value="{{ $indicador->orden ?? old('orden', 0) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-secondary small">Método de Cálculo</label>
                        <textarea name="metodo_calculo" class="form-control" rows="2">{{ $indicador->metodo_calculo ?? old('metodo_calculo') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- PANEL 2: VARIABLES --}}
        <div class="card-panel indicator-section mb-4" id="indicator-variables">
            <div
                class="card-header bg-white border-bottom d-flex justify-content-between align-items-center gap-3 py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <span
                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-vino"
                        style="width:2.25rem; height:2.25rem;">
                        <i class="fa-solid fa-tags"></i>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="indicator-section-title">Variables</h5>
                            <span class="badge rounded-pill text-bg-light border text-secondary"
                                id="variablesCount">{{ $indicador?->variables?->count() ?? 0 }}</span>
                        </div>
                        <p class="mb-0 small text-muted">Define cómo se captura, muestra o calcula cada valor del
                            indicador.</p>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-custom-primary text-nowrap" id="addVariableBtn"><i
                        class="fa-solid fa-plus me-1"></i>Añadir variable</button>
            </div>
            <div class="card-body p-4">
                <div id="variablesContainer">
                    @forelse (($indicador->variables ?? []) as $var)
                        @include('catalogo._variable-row', [
                            'var' => $var,
                            'index' => $loop->index,
                            'variables' => $variables,
                        ])
                    @empty
                        <div class="text-center text-muted py-5 px-3 rounded border border-2 bg-light"
                            id="noVariablesMsg" style="border-style: dashed !important;">
                            <i class="fa-solid fa-tags fa-2x mb-3 text-vino opacity-50"></i>
                            <p class="fw-semibold text-dark mb-1">Aún no has agregado variables</p>
                            <p class="small mb-0">Añade una para registrar un dato o definir una operación calculada.
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Template para nueva variable --}}
                <template id="variableRowTemplate">
                    @include('catalogo._variable-row', [
                        'var' => null,
                        'index' => '__INDEX__',
                        'variables' => $variables,
                    ])
                </template>
            </div>
        </div>

        {{-- PANEL 3: APLICAR FÓRMULA --}}
        <div class="card-panel indicator-section mb-4" id="generationPanel"
            style="{{ $indicador && $indicador->variables->contains('es_construida', true) ? '' : 'display:none;' }}">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-3 py-3 px-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 text-info" style="width:2.25rem; height:2.25rem;">
                    <i class="fa-solid fa-bolt"></i>
                </span>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="indicator-section-title">Aplicar fórmula</h5>
                        <span class="badge rounded-pill text-bg-light border text-secondary">Paso 3</span>
                    </div>
                    <p class="mb-0 small text-muted">Previsualiza el resultado y genera los datos históricos de una variable construida.</p>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap rounded-3 border border-info bg-info bg-opacity-10 p-3 mb-4" id="generationSaveNotice">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-circle-info text-info mt-1"></i>
                        <div>
                            <p class="fw-semibold text-dark mb-1">Primero guarda la fórmula</p>
                            <p class="small text-muted mb-0">La previsualización y generación usan la configuración guardada. Al guardar, volverás a esta pantalla para continuar.</p>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-custom-primary btn-sm text-nowrap" id="saveFormulaBtn">
                        <i class="fa-solid fa-save me-1"></i>Guardar cambios
                    </button>
                </div>
                <div class="d-flex gap-3 align-items-end flex-wrap">
                    <div>
                        <label for="generationVariableSelect" class="form-label fw-bold text-secondary small">Variable construida</label>
                    <select id="generationVariableSelect" class="form-select" style="max-width:300px;">
                        <option value="">Selecciona variable...</option>
                    </select>
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm" id="previewBtn" disabled><i
                            class="fa-solid fa-eye me-1"></i>Previsualizar</button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="generateBtn" disabled><i
                            class="fa-solid fa-bolt me-1"></i>Generar datos</button>
                </div>
                <div id="previewResults" class="mt-3 d-none">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Municipio</th>
                                    <th>Año</th>
                                    <th id="preview-col1">Valor numerador</th>
                                    <th id="preview-col2">Valor denominador</th>
                                    <th class="fw-bold text-info">Resultado</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>
                    <p class="mt-2 mb-0 small text-muted" id="previewTotal"></p>
                </div>
            </div>
        </div>

        {{-- BOTONES --}}
        <div class="indicator-actions">
            <a href="{{ route('admin.catalogos.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
            <button type="submit" class="btn btn-custom-primary px-4 shadow-sm"><i
                    class="fa-solid fa-save me-1"></i>{{ $indicador ? 'Guardar cambios' : 'Crear indicador' }}</button>
        </div>
                </main>
            </div>
        </div>
    </form>

    {{-- MODAL PREVISUALIZACIÓN --}}
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-vino"><i
                            class="fa-solid fa-eye me-2 text-info"></i>Previsualización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Municipio</th>
                                    <th>Año</th>
                                    <th id="modalPreviewCol1">Valor numerador</th>
                                    <th id="modalPreviewCol2">Valor denominador</th>
                                    <th class="fw-bold text-info">Resultado</th>
                                </tr>
                            </thead>
                            <tbody id="modalPreviewBody"></tbody>
                        </table>
                    </div>
                    <p class="mt-2 mb-0 small text-muted" id="modalPreviewTotal"></p>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary px-4"
                        data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-custom-primary px-4" id="confirmGenerateBtn"><i
                            class="fa-solid fa-bolt me-1"></i>Generar datos</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css"
            rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let varIndex = {{ $indicador?->variables?->count() ?? 0 }};
                const container = document.getElementById('variablesContainer');
                const noMsg = document.getElementById('noVariablesMsg');
                const variablesCount = document.getElementById('variablesCount');
                const generationPanel = document.getElementById('generationPanel');
                const genSelect = document.getElementById('generationVariableSelect');
                const generationSaveNotice = document.getElementById('generationSaveNotice');
                const previewBtn = document.getElementById('previewBtn');
                const generateBtn = document.getElementById('generateBtn');
                let generationNeedsSave = false;
                let previewVariableId = null;
                let previewData = [];
                const previewUrlTemplate = @json(route('admin.catalogos.variables.preview', ['variable' => '__variable__']));
                const generateUrlTemplate = @json(route('admin.catalogos.variables.generar', ['variable' => '__variable__']));
                const variableUrl = (template, variableId) => template.replace('__variable__', encodeURIComponent(
                    variableId));

                function slugify(text) {
                    return text.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_]+/g, '_')
                        .replace(/^-+|-+$/g, '')
                        .replace(/__+/g, '_');
                }

                const indicadorNombreAmigable = document.getElementById('indicador_nombre_amigable');
                const indicadorNombreTecnico = document.getElementById('indicador_nombre_tecnico');
                indicadorNombreAmigable?.addEventListener('input', function() {
                    if (indicadorNombreTecnico?.dataset.autoGen !== 'true') return;
                    indicadorNombreTecnico.value = slugify(this.value);
                });

                indicadorNombreTecnico?.addEventListener('focus', function() {
                    this.dataset.autoGen = 'false';
                });

                function initTomSelectsOnCard(card) {
                    card.querySelectorAll('.tom-select-variable').forEach(el => {
                        if (el.tomselect) el.tomselect.destroy();
                        new TomSelect(el, {
                            placeholder: 'Selecciona...',
                            allowEmptyOption: true,
                            dropdownParent: 'body',
                        });
                    });
                }

                function updateVariablesCount() {
                    if (variablesCount) {
                        variablesCount.textContent = container.querySelectorAll('.variable-card').length;
                    }
                }

                function markGenerationNeedsSave() {
                    generationNeedsSave = true;
                    actualizarSelectGeneracion();
                }

                const formulaHelp = {
                    division: {
                        title: 'División',
                        summary: 'Divide el numerador entre el denominador y aplica el multiplicador.',
                        detail: 'Calcula (numerador / denominador) × multiplicador para los registros que coinciden en municipio y año.'
                    },
                    tasa_crecimiento: {
                        title: 'Tasa de crecimiento',
                        summary: 'Compara cada valor con el periodo anterior disponible.',
                        detail: 'Calcula ((valor actual - valor anterior) / valor anterior) × multiplicador para cada municipio.'
                    },
                    tasa_crecimiento_inegi_2025: {
                        title: 'Tasa de crecimiento INEGI 2025',
                        summary: 'Calcula la tasa media anual entre los dos periodos disponibles.',
                        detail: 'Aplica [(Px / P0)^(1 / t) - 1] × 100, donde t es la diferencia entre los años. El multiplicador es fijo.'
                    },
                    sumatoria: {
                        title: 'Sumatoria de variables',
                        summary: 'Suma las variables seleccionadas por municipio y año.',
                        detail: 'Agrupa y suma los valores disponibles de las variables seleccionadas que coinciden en municipio y año.'
                    }
                };

                function setFormulaSectionState(section, isVisible) {
                    if (!section) return;

                    section.hidden = !isVisible;
                    section.style.display = isVisible ? '' : 'none';
                    section.classList.toggle('d-none', !isVisible);
                    section.querySelectorAll('input, select, textarea').forEach(control => {
                        control.disabled = !isVisible;
                        if (control.tomselect) {
                            isVisible ? control.tomselect.enable() : control.tomselect.disable();
                        }
                    });
                }

                function updateFormulaFields(card) {
                    const check = card.querySelector('.es-construida-check');
                    const formulaSection = card.querySelector('.formula-section');
                    const select = card.querySelector('.formula-tipo-select');
                    const isConstructed = Boolean(check?.checked);

                    if (!formulaSection || !select) return;

                    formulaSection.hidden = !isConstructed;
                    formulaSection.style.display = isConstructed ? 'block' : 'none';
                    formulaSection.classList.toggle('d-none', !isConstructed);
                    select.disabled = !isConstructed;

                    const type = select.value || 'division';
                    const isGrowthRate = ['tasa_crecimiento', 'tasa_crecimiento_inegi_2025'].includes(type);
                    const isInegi = type === 'tasa_crecimiento_inegi_2025';

                    setFormulaSectionState(card.querySelector('.formula-division-fields'),
                        isConstructed && type === 'division');
                    setFormulaSectionState(card.querySelector('.formula-tasa-fields'),
                        isConstructed && isGrowthRate);
                    setFormulaSectionState(card.querySelector('.formula-sumatoria-fields'),
                        isConstructed && type === 'sumatoria');

                    const multiplier = card.querySelector('.formula-tasa-multiplicador');
                    if (multiplier) {
                        multiplier.readOnly = isInegi;
                        if (isInegi) multiplier.value = 100;
                    }

                    const inegiHelp = card.querySelector('.formula-inegi-help');
                    if (inegiHelp) {
                        inegiHelp.hidden = !isInegi;
                        inegiHelp.style.display = isInegi ? '' : 'none';
                        inegiHelp.classList.toggle('d-none', !isInegi);
                    }

                    const description = card.querySelector('.formula-type-description');
                    if (description) description.textContent = formulaHelp[type]?.summary || '';
                }

                // --- AÑADIR VARIABLE ---
                document.getElementById('addVariableBtn').addEventListener('click', function() {
                    const tmpl = document.getElementById('variableRowTemplate').innerHTML;
                    const html = tmpl.replace(/__INDEX__/g, varIndex).replace(/__DISPLAY_INDEX__/g, varIndex +
                        1).replace(/__ID__/g, '');
                    if (noMsg) noMsg.style.display = 'none';
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    const card = wrapper.firstElementChild;
                    container.appendChild(card);
                    initTomSelectsOnCard(card);
                    updateFormulaFields(card);
                    updateValueMapping(card);
                    varIndex++;
                    updateVariablesCount();
                    markGenerationNeedsSave();
                    actualizarSelectGeneracion();
                });

                // --- ELIMINAR VARIABLE ---
                container.addEventListener('click', function(e) {
                    const btn = e.target.closest('.remove-variable-btn');
                    if (!btn) return;

                    Swal.fire({
                        title: '¿Eliminar esta variable?',
                        text: 'La variable se eliminará al guardar los cambios del indicador.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger px-4 me-2',
                            cancelButton: 'btn btn-outline-secondary px-4'
                        }
                    }).then(result => {
                        if (!result.isConfirmed) return;

                        const card = btn.closest('.variable-card');
                        card.querySelectorAll('.tom-select-variable').forEach(el => {
                            if (el.tomselect) el.tomselect.destroy();
                        });
                        card.remove();
                        if (container.querySelectorAll('.variable-card').length === 0 && noMsg) noMsg
                            .style
                            .display = 'block';
                        updateVariablesCount();
                        markGenerationNeedsSave();
                        actualizarSelectGeneracion();
                    });
                });

                // --- TOGGLE FORMULA ---
                container.addEventListener('change', function(e) {
                    const check = e.target.closest('.es-construida-check');
                    if (check) {
                        const card = check.closest('.variable-card');
                        updateFormulaFields(card);
                        actualizarSelectGeneracion();
                    }
                });

                // Changes to a variable must be persisted before applying its formula.
                container.addEventListener('input', markGenerationNeedsSave);
                container.addEventListener('change', markGenerationNeedsSave);

                // --- TOGGLE TIPO FORMULA ---
                container.addEventListener('change', function(e) {
                    const sel = e.target.closest('.formula-tipo-select');
                    if (sel) {
                        const card = sel.closest('.variable-card');
                        updateFormulaFields(card);
                    }
                });

                // --- AYUDA DEL TIPO DE FÓRMULA ---
                container.addEventListener('click', function(e) {
                    const button = e.target.closest('.formula-type-help-btn');
                    if (!button) return;

                    const card = button.closest('.variable-card');
                    const type = card.querySelector('.formula-tipo-select')?.value || 'division';
                    const help = formulaHelp[type];

                    Swal.fire({
                        icon: 'info',
                        title: help.title,
                        text: help.detail,
                        confirmButtonText: 'Entendido',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-custom-primary px-4'
                        }
                    });
                });

                // --- TOGGLE MAPEO VALORES (ocultar si numérica) ---
                container.addEventListener('change', function(e) {
                    const sel = e.target.closest('.tipo-valor-select');
                    if (sel) {
                        const wrapper = sel.closest('.variable-card').querySelector('.mapeo-valores-wrapper');
                        if (wrapper) {
                            wrapper.style.display = sel.value === 'numerica' ? 'none' : '';
                        }
                    }
                });

                // --- AUTO-GENERAR NOMBRE TÉCNICO ---
                container.addEventListener('input', function(e) {
                    const input = e.target.closest('.var-nombre-amigable');
                    if (!input) return;
                    const card = input.closest('.variable-card');
                    const tecInput = card.querySelector('.var-nombre-tecnico');
                    if (!tecInput || tecInput.dataset.autoGen !== 'true') return;

                    const indTec = document.getElementById('indicador_nombre_tecnico')?.value || '';
                    const varName = input.value;
                    let slug = slugify(varName);
                    if (indTec) slug = slugify(indTec) + '_' + slug;
                    tecInput.value = slug;
                });

                // --- DETENER AUTO-GEN SI EL USUARIO EDITA MANUALMENTE ---
                container.addEventListener('focus', function(e) {
                    const input = e.target.closest('.var-nombre-tecnico');
                    if (input) input.dataset.autoGen = 'false';
                }, true);

                function updateValueMapping(card) {
                    const tipoValor = card.querySelector('.tipo-valor-select');
                    const mapeoWrapper = card.querySelector('.mapeo-valores-wrapper');
                    if (tipoValor && mapeoWrapper) {
                        mapeoWrapper.style.display = tipoValor.value === 'numerica' ? 'none' : '';
                    }
                }

                // Attach events to existing rows
                document.querySelectorAll('.variable-card').forEach(card => {
                    initTomSelectsOnCard(card);
                    updateFormulaFields(card);
                    updateValueMapping(card);
                });

                // --- ACTUALIZAR SELECT DE GENERACIÓN ---
                function actualizarSelectGeneracion() {
                    const cards = container.querySelectorAll('.variable-card');
                    let hasBuilt = false;
                    let hasSavedBuilt = false;
                    genSelect.innerHTML = '<option value="">Selecciona variable...</option>';
                    cards.forEach(card => {
                        const name = card.querySelector('input[name$="[nombre_amigable]"]')?.value ||
                            'Variable';
                        const isBuilt = card.querySelector('.es-construida-check')?.checked;
                        if (isBuilt) {
                            hasBuilt = true;
                            const id = card.querySelector('input[name$="[id]"]')?.value || '';
                            if (id) {
                                hasSavedBuilt = true;
                                genSelect.innerHTML += `<option value="${id}">${name}</option>`;
                            }
                        }
                    });
                    generationPanel.style.display = hasBuilt ? '' : 'none';
                    genSelect.disabled = generationNeedsSave || !hasSavedBuilt;
                    generationSaveNotice.classList.toggle('d-none', !generationNeedsSave);
                    previewBtn.disabled = generationNeedsSave || !genSelect.value;
                    generateBtn.disabled = generationNeedsSave || !genSelect.value;
                }

                // --- PREVISUALIZAR ---
                document.getElementById('previewBtn').addEventListener('click', function() {
                    const varId = genSelect.value;
                    if (!varId) return;
                    previewVariableId = varId;

                    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
                    document.getElementById('modalPreviewBody').innerHTML =
                        '<tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>';
                    modal.show();

                    fetch(variableUrl(previewUrlTemplate, varId), {
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.error) {
                                document.getElementById('modalPreviewBody').innerHTML =
                                    `<tr><td colspan="5" class="text-center text-danger">${data.error}</td></tr>`;
                                return;
                            }
                            previewData = data.rows || [];
                            const isTasa = previewData.length && previewData[0].valor_numerador ===
                                undefined;
                            document.getElementById('modalPreviewCol1').textContent = isTasa ?
                                'Valor anterior' : 'Valor numerador';
                            document.getElementById('modalPreviewCol2').textContent = isTasa ?
                                'Valor actual' : 'Valor denominador';
                            let html = '';
                            previewData.forEach(r => {
                                const periodo = r.anio_anterior ? `${r.anio_anterior} - ${r.anio}` :
                                    (r.anio || '—');
                                html +=
                                    `<tr><td>${r.municipio||'—'}</td><td>${periodo}</td><td>${r.valor_numerador??r.valor_anterior??'—'}</td><td>${r.valor_denominador??r.valor_actual??'—'}</td><td class="fw-bold text-info">${r.valor??'—'}</td></tr>`;
                            });
                            document.getElementById('modalPreviewBody').innerHTML = html ||
                                '<tr><td colspan="5" class="text-center text-muted">Sin datos</td></tr>';
                            document.getElementById('modalPreviewTotal').textContent =
                                `Total: ${data.total || 0} registros.`;
                        });
                });

                // --- GENERAR DESDE MODAL ---
                document.getElementById('confirmGenerateBtn').addEventListener('click', function() {
                    if (!previewVariableId) return;
                    fetch(variableUrl(generateUrlTemplate, previewVariableId), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Datos generados',
                                    text: data.message,
                                    confirmButtonColor: '#5f1b2d'
                                });
                                bootstrap.Modal.getInstance(document.getElementById('previewModal'))
                                    ?.hide();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.error || 'Error al generar.',
                                    confirmButtonColor: '#af1731'
                                });
                            }
                        });
                });

                // --- GENERAR DIRECTO ---
                document.getElementById('generateBtn').addEventListener('click', function() {
                    const varId = genSelect.value;
                    if (!varId) return;
                    Swal.fire({
                        title: '¿Generar datos?',
                        text: 'Se calcularán y guardarán los datos históricos.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#5f1b2d',
                        confirmButtonText: 'Sí, generar',
                        cancelButtonText: 'Cancelar'
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch(variableUrl(generateUrlTemplate, varId), {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) Swal.fire({
                                        icon: 'success',
                                        title: 'Datos generados',
                                        text: data.message,
                                        confirmButtonColor: '#5f1b2d'
                                    });
                                    else Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.error || 'Error.',
                                        confirmButtonColor: '#af1731'
                                    });
                                });
                        }
                    });
                });

                // Enable/disable buttons based on selection
                genSelect.addEventListener('change', function() {
                    previewBtn.disabled = generationNeedsSave || !this.value;
                    generateBtn.disabled = generationNeedsSave || !this.value;
                });

                actualizarSelectGeneracion();
                updateVariablesCount();
            });
        </script>
    @endpush
</x-admin-layout>
