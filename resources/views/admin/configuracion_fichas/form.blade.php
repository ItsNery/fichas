<x-admin-layout>
    @section('title', $configuracion->exists ? 'Editar Configuración' : 'Nueva Configuración')

    <x-page-header title="{{ $configuracion->exists ? 'Editar Configuración' : 'Nueva Configuración' }}"
        subtitle="Personaliza el contenido y la visualización del indicador en la ficha municipal"
        icon="fa-solid fa-pen-to-square" />

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <style>
        .config-workspace {
            --config-wine: #651b2c;
            --config-wine-soft: #f7eef0;
            --config-green: #1f5b51;
            --config-gold: #b88a3b;
            --config-ink: #27313a;
        }
        .config-intro {
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
        .config-intro__eyebrow { font-size: .72rem; letter-spacing: .12em; text-transform: uppercase; opacity: .72; }
        .config-intro__title { margin: .2rem 0 0; font-size: 1.15rem; font-weight: 700; }
        .config-intro__hint { max-width: 34rem; margin: 0; font-size: .86rem; opacity: .82; }
        .config-form-card { border-radius: 1.25rem; }
        .config-sidebar {
            min-height: 100%;
            padding: 1.5rem 1.15rem;
            background: #f7f5f2;
        }
        .config-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .85rem;
            margin-bottom: .55rem;
            color: #59616a;
            text-align: left;
            border-radius: .85rem;
        }
        .config-sidebar .nav-link.active { color: #fff; background: var(--config-wine); box-shadow: 0 8px 20px rgba(101, 27, 44, .2); }
        .step-number {
            display: grid;
            flex: 0 0 1.8rem;
            width: 1.8rem;
            height: 1.8rem;
            place-items: center;
            font-size: .72rem;
            font-weight: 800;
            color: var(--config-wine);
            background: #fff;
            border-radius: 50%;
        }
        .step-copy strong, .step-copy small { display: block; }
        .step-copy small { margin-top: .1rem; font-size: .68rem; opacity: .72; }
        .config-status-card { padding: 1rem; margin-top: 2rem; background: #fff; border: 1px solid #e5e0dc; border-radius: 1rem; }
        .config-status-card .form-check-label { font-size: .76rem; letter-spacing: .04em; }
        .config-main { padding: 2rem; }
        .form-section-title { display: flex; align-items: center; gap: .65rem; margin-bottom: .35rem; color: var(--config-ink); font-size: 1.15rem; }
        .form-section-title i { color: var(--config-wine); }
        .section-lead { margin-bottom: 1.6rem; color: #747c84; font-size: .87rem; }
        .field-block { padding: 1rem; background: #fbfbfa; border: 1px solid #e9e6e2; border-radius: .9rem; }
        .field-block + .field-block { margin-top: 1rem; }
        .field-help { display: flex; gap: .45rem; margin-top: .45rem; color: #737b83; font-size: .78rem; line-height: 1.45; }
        .field-help i { margin-top: .18rem; color: var(--config-gold); }
        .vis-option { min-height: 112px; padding: 1rem .65rem; border: 1px solid #dedbd7; border-radius: 1rem; cursor: pointer; transition: .18s ease; }
        .vis-option:hover { border-color: #b88a3b; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(39, 49, 58, .08); }
        .vis-option.active { color: var(--config-wine); background: var(--config-wine-soft); border: 2px solid var(--config-wine); }
        .vis-option > i { display: block; margin-bottom: .5rem; font-size: 1.3rem; }
        .vis-option span { display: block; font-size: .78rem; font-weight: 700; }
        .vis-option small { display: block; margin-top: .3rem; color: #777; font-size: .65rem; line-height: 1.25; }
        .check-mark { position: absolute; top: .45rem; right: .55rem; opacity: 0; }
        .vis-option { position: relative; }
        .vis-option.active .check-mark { opacity: 1; }
        .correlation-panel { margin-top: 1rem; padding: 1.1rem; background: linear-gradient(135deg, #eef6f4, #fff); border: 1px solid #bcd8d2; border-radius: 1rem; }
        .scatter-assistant { margin-top: .9rem; padding: 1.15rem; color: #263b37; background: #f1f7f5; border: 1px solid #c6dcd6; border-radius: 1rem; }
        .scatter-assistant__intro { display: flex; align-items: flex-start; gap: .8rem; margin-bottom: 1rem; }
        .scatter-assistant__icon { display: grid; flex: 0 0 2.3rem; width: 2.3rem; height: 2.3rem; place-items: center; color: #fff; background: var(--config-green); border-radius: .7rem; }
        .axis-step { height: 100%; padding: 1rem; background: #fff; border: 1px solid #d9e5e1; border-radius: .85rem; }
        .axis-step__number { display: inline-grid; width: 1.5rem; height: 1.5rem; margin-right: .35rem; place-items: center; color: #fff; background: var(--config-wine); border-radius: 50%; font-size: .68rem; font-weight: 800; }
        .axis-step__question { min-height: 2.5rem; margin: .45rem 0 .7rem; color: #707a76; font-size: .75rem; line-height: 1.4; }
        .axis-summary { display: flex; align-items: center; gap: .45rem; margin-top: .65rem; color: #66716d; font-size: .7rem; }
        .scatter-assistant__tip { padding: .75rem .9rem; margin-top: .9rem; color: #6d592d; background: #fff8e9; border-left: 3px solid var(--config-gold); border-radius: .5rem; font-size: .75rem; }
        .correlation-panel__header { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
        .correlation-results { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: 1rem; }
        .correlation-metric { padding: .85rem; background: #fff; border: 1px solid #dce8e5; border-radius: .8rem; }
        .correlation-metric__label { color: #69736f; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .correlation-metric__value { margin: .2rem 0; color: var(--config-green); font-size: 1.45rem; font-weight: 800; }
        .correlation-metric__reading { color: #59645f; font-size: .72rem; }
        .correlation-meta { padding-top: .8rem; margin-top: .8rem; color: #59645f; font-size: .75rem; border-top: 1px dashed #c9d9d5; }
        .advanced-panel { overflow: hidden; border: 1px dashed #cfcac4; border-radius: .9rem; }
        .advanced-panel summary { padding: .9rem 1rem; color: #606870; background: #f8f7f5; cursor: pointer; font-weight: 700; }
        .advanced-panel__body { padding: 1rem; }
        .config-actions { position: sticky; bottom: 0; z-index: 5; display: flex; justify-content: space-between; gap: 1rem; padding: 1rem 0 0; margin-top: 2rem; background: linear-gradient(transparent, #fff 25%); }
        .preview-card { padding: 1.1rem; background: #f7f5f2; border-radius: 1.25rem; }
        .preview-sticky { position: sticky; top: 1rem; }
        .preview-caption { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; color: #697078; font-size: .7rem; font-weight: 800; letter-spacing: .08em; }
        @media (max-width: 767.98px) {
            .config-intro { align-items: flex-start; flex-direction: column; }
            .config-sidebar { padding: 1rem; }
            .config-sidebar .nav { flex-direction: row !important; gap: .35rem; }
            .config-sidebar .nav-link { flex: 1; margin: 0; }
            .step-copy small { display: none; }
            .config-main { padding: 1.25rem; }
            .correlation-results { grid-template-columns: 1fr; }
        }
    </style>

    <div class="container py-4 pb-5 config-workspace">
        <section class="config-intro">
            <div>
                <div class="config-intro__eyebrow">Constructor de ficha municipal</div>
                <p class="config-intro__title">Configura qué dato se muestra, cómo se interpreta y cómo lo verá la ciudadanía.</p>
            </div>
            <p class="config-intro__hint"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Define primero la visualización, después la explicación editorial y finalmente el indicador y sus variables. La vista previa se actualiza mientras editas.</p>
        </section>
        <form
            action="{{ $configuracion->exists ? route('admin.configuracion-fichas.update', $configuracion->id) : route('admin.configuracion-fichas.store') }}"
            method="POST" id="configForm">
            @csrf
            @if ($configuracion->exists)
                @method('PUT')
            @endif

            <div class="row g-4">
                {{-- Formulario Principal --}}
                <div class="col-lg-8">
                    <div class="card card-panel config-form-card border-0 shadow-sm overflow-hidden">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                {{-- Sidebar de Tabs --}}
                                <div class="col-md-3 config-sidebar border-end">
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                                        <button class="nav-link active" id="tab-visual-tab" data-bs-toggle="pill"
                                            data-bs-target="#tab-visual" type="button" role="tab">
                                            <span class="step-number">1</span><span class="step-copy"><strong>Visualización</strong><small>Cómo se verá</small></span>
                                        </button>
                                        <button class="nav-link" id="tab-contenido-tab" data-bs-toggle="pill"
                                            data-bs-target="#tab-contenido" type="button" role="tab">
                                            <span class="step-number">2</span><span class="step-copy"><strong>Editorial</strong><small>Cómo se explicará</small></span>
                                        </button>
                                        <button class="nav-link" id="tab-datos-tab" data-bs-toggle="pill"
                                            data-bs-target="#tab-datos" type="button" role="tab">
                                            <span class="step-number">3</span><span class="step-copy"><strong>Datos</strong><small>Qué se mostrará</small></span>
                                        </button>
                                    </div>

                                    <div class="config-status-card">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="activo"
                                                name="activo"
                                                {{ old('activo', $configuracion->activo ?? true) ? 'checked' : '' }}>
                                             <label class="form-check-label fw-bold" for="activo">Visible en la ficha</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="mostrar_comparativa"
                                                name="mostrar_comparativa"
                                                {{ old('mostrar_comparativa', $configuracion->mostrar_comparativa ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold small"
                                                 for="mostrar_comparativa">Comparar con región y estado</label>
                                        </div>
                                        <div class="mt-3 pt-2 border-top" id="benchmark_mode_group"
                                            style="display: {{ old('mostrar_comparativa', $configuracion->mostrar_comparativa ?? false) ? 'block' : 'none' }};">
                                            <label for="benchmark_mode" class="form-label fw-bold small mb-1">Forma de comparar</label>
                                            @php
                                                $currentMode = old(
                                                    'benchmark_mode',
                                                    $configuracion->ajustes_visuales['benchmark_mode'] ?? 'avg',
                                                );
                                            @endphp
                                            <select id="benchmark_mode" name="benchmark_mode"
                                                class="form-select form-select-sm">
                                                <option value="avg" {{ $currentMode == 'avg' ? 'selected' : '' }}>
                                                    Promedio de los municipios</option>
                                                <option value="sum" {{ $currentMode == 'sum' ? 'selected' : '' }}>
                                                    Suma regional o estatal</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contenido de Tabs --}}
                                <div class="col-md-9 config-main bg-white">
                                    <div class="tab-content" id="v-pills-tabContent">
                                        {{-- Tab 3: Datos --}}
                                        <div class="tab-pane fade" id="tab-datos" role="tabpanel">
                                            <h4 class="form-section-title"><i class="fa-solid fa-database"></i>Origen de datos</h4>
                                            <p class="section-lead">Elige el indicador y las variables que alimentarán esta tarjeta. La dimensión de la ficha se asigna automáticamente.</p>

                                            <div class="field-block">
                                                <label for="indicador_id" class="form-label">Indicador</label>
                                                <select id="indicador_id" name="indicador_id" required>
                                                    <option value="">Buscar indicador...</option>
                                                    @foreach ($indicadores as $indicador)
                                                        <option value="{{ $indicador->id }}"
                                                            data-nombre="{{ $indicador->nombre_amigable }}"
                                                            {{ old('indicador_id', $configuracion->indicador_id ?? '') == $indicador->id ? 'selected' : '' }}>
                                                            {{ $indicador->nombre_amigable }}
                                                            ({{ $indicador->tematica->nombre }})
                                                        </option>
                                                    @endforeach
                                                    </select>
                                                <div class="field-help"><i class="fa-solid fa-circle-info"></i><span>El indicador define el tema, la fuente y la sección donde aparecerá la tarjeta.</span></div>
                                            </div>

                                            <div class="field-block">
                                                <label for="variables_ids" class="form-label">Variables a mostrar</label>
                                                <select id="variables_ids" name="variables_ids[]" multiple
                                                    class="@error('variables_ids') is-invalid @enderror"
                                                    placeholder="Selecciona variables específicas...">
                                                    @if (isset($variablesIndicador))
                                                        @php $seleccionadas = old('variables_ids', isset($configuracion) && $configuracion->variables ? $configuracion->variables->pluck('id')->toArray() : []); @endphp
                                                        @foreach ($variablesIndicador as $var)
                                                            <option value="{{ $var->id }}"
                                                                {{ in_array($var->id, $seleccionadas) ? 'selected' : '' }}>
                                                                {{ $var->nombre_amigable }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <div id="scatter-assistant" class="scatter-assistant d-none">
                                                    <div class="scatter-assistant__intro">
                                                        <span class="scatter-assistant__icon"><i class="fa-solid fa-route"></i></span>
                                                        <div>
                                                            <div class="fw-bold">Construye la pregunta de correlación</div>
                                                            <div class="small text-muted">Elige primero el posible factor explicativo y después el resultado que quieres contrastar. El sistema comprobará automáticamente si existen datos comparables.</div>
                                                        </div>
                                                    </div>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="axis-step">
                                                                <label for="scatter_variable_x" class="form-label fw-bold mb-0"><span class="axis-step__number">1</span>Variable del eje X</label>
                                                                <p class="axis-step__question">¿Qué condición, recurso o característica podría explicar cambios?</p>
                                                                <select id="scatter_variable_x" class="form-select">
                                                                    <option value="">Selecciona el posible factor...</option>
                                                                </select>
                                                                <div class="axis-summary"><i class="fa-solid fa-arrow-right"></i><span>Se interpreta como variable explicativa.</span></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="axis-step">
                                                                <label for="scatter_variable_y" class="form-label fw-bold mb-0"><span class="axis-step__number">2</span>Variable del eje Y</label>
                                                                <p class="axis-step__question">¿Qué resultado social, económico o territorial quieres observar?</p>
                                                                <select id="scatter_variable_y" class="form-select" disabled>
                                                                    <option value="">Primero selecciona el eje X...</option>
                                                                </select>
                                                                <div class="axis-summary"><i class="fa-solid fa-arrow-up"></i><span>Se interpreta como variable de resultado.</span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="scatter-assistant__tip"><i class="fa-solid fa-scale-balanced me-1"></i><strong>Compara medidas equivalentes:</strong> porcentajes con porcentajes, tasas con tasas o valores normalizados. Evita cruzar totales que solo reflejen el tamaño del municipio.</div>
                                                </div>
                                                @error('variables_ids')
                                                    <div class="text-danger small mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>
                                                @enderror
                                                <div class="field-help" id="variables-help"><i class="fa-solid fa-filter"></i><span>Si no seleccionas variables se usarán todas las del indicador. Para una correlación debes elegir exactamente dos: primero el eje X y después el eje Y.</span></div>
                                                <div id="correlation-panel" class="correlation-panel d-none" aria-live="polite">
                                                    <div class="correlation-panel__header">
                                                        <div>
                                                            <div class="fw-bold text-dark"><i class="fa-solid fa-chart-scatter me-2 text-success"></i>Diagnóstico de correlación</div>
                                                            <div class="small text-muted mt-1" id="correlation-status">Selecciona exactamente dos variables para analizar su relación.</div>
                                                        </div>
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" id="incluir_spearman">
                                                            <label class="form-check-label small fw-semibold" for="incluir_spearman">Calcular Spearman</label>
                                                        </div>
                                                    </div>
                                                    <div id="correlation-results" class="correlation-results d-none">
                                                        <div class="correlation-metric">
                                                            <div class="correlation-metric__label">Pearson · relación lineal</div>
                                                            <div class="correlation-metric__value" id="pearson-value">—</div>
                                                            <div class="correlation-metric__reading" id="pearson-reading"></div>
                                                        </div>
                                                        <div class="correlation-metric d-none" id="spearman-metric">
                                                            <div class="correlation-metric__label">Spearman · relación monótona</div>
                                                            <div class="correlation-metric__value" id="spearman-value">—</div>
                                                            <div class="correlation-metric__reading" id="spearman-reading"></div>
                                                        </div>
                                                    </div>
                                                    <div id="correlation-meta" class="correlation-meta d-none"></div>
                                                </div>
                                            </div>

                                            <div class="row g-3 mt-1">
                                                <div class="col-md-12">
                                                    <label for="orden" class="form-label">Prioridad (Orden)</label>
                                                    <input type="number" class="form-control" id="orden"
                                                        name="orden"
                                                        value="{{ old('orden', $configuracion->orden ?? 0) }}"
                                                        required>
                                                    <div class="field-help"><i class="fa-solid fa-arrow-down-1-9"></i><span>Los números menores aparecen primero dentro de su dimensión. Ejemplo: 10 se muestra antes que 20.</span></div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Tab 1: Visualización --}}
                                        <div class="tab-pane fade show active" id="tab-visual" role="tabpanel">
                                            <h4 class="form-section-title"><i class="fa-solid fa-chart-simple"></i>Presentación visual</h4>
                                            <p class="section-lead">Escoge el formato que comunica mejor el dato. El formulario te avisará si el formato requiere una selección especial.</p>

                                            <label class="form-label mb-3">Formato de Visualización</label>
                                            <div class="row g-3 mb-4">
                                                @php
                                                    $icons = [
                                                        'kpi' => 'fas fa-stopwatch-20',
                                                        'piramide' => 'fas fa-align-center',
                                                        'treemap' => 'fas fa-th-large',
                                                        'barras' => 'fas fa-chart-bar',
                                                        'lineas' => 'fas fa-chart-line',
                                                        'mapa' => 'fas fa-map-marked-alt',
                                                        'scatter' => 'fas fa-project-diagram',
                                                    ];
                                                    $visDescriptions = [
                                                        'kpi' => 'Una cifra destacada',
                                                        'piramide' => 'Estructura por edades',
                                                        'treemap' => 'Proporciones por área',
                                                        'barras' => 'Comparar categorías',
                                                        'lineas' => 'Evolución en el tiempo',
                                                        'mapa' => 'Comparación territorial',
                                                        'scatter' => 'Relacionar dos variables',
                                                    ];
                                                @endphp
                                                @foreach ($visualizaciones as $vis)
                                                    <div class="col-4">
                                                        <div class="vis-option {{ old('tipo_visualizacion', $configuracion->tipo_visualizacion ?? '') == $vis ? 'active' : '' }}"
                                                            data-value="{{ $vis }}">
                                                            <i class="{{ $icons[$vis] ?? 'fas fa-chart-area' }}"></i>
                                                            <span>{{ $vis == 'kpi' ? 'Indicador Clave' : ($vis == 'scatter' ? 'Dispersión (Cruce)' : ucfirst($vis)) }}</span>
                                                            <small>{{ $visDescriptions[$vis] ?? '' }}</small>
                                                            <div class="check-mark"><i
                                                                    class="fas fa-check-circle"></i></div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                <input type="hidden" name="tipo_visualizacion"
                                                    id="tipo_visualizacion_hidden"
                                                    value="{{ old('tipo_visualizacion', $configuracion->tipo_visualizacion ?? 'kpi') }}">
                                            </div>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-8">
                                                    <label for="clase_grid" class="form-label">
                                                        Ancho de la tarjeta
                                                        <i class="fa-solid fa-circle-info text-muted ms-1 cursor-pointer"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="Define el ancho de la tarjeta en la pantalla del perfil: 100% ocupa todo el ancho, 50% permite colocar dos tarjetas lado a lado, etc."></i>
                                                    </label>
                                                    <select id="clase_grid" name="clase_grid" class="form-select"
                                                        required>
                                                        <option value="col-12"
                                                            {{ old('clase_grid', $configuracion->clase_grid ?? '') == 'col-12' ? 'selected' : '' }}>
                                                            Bloque Completo (100%)</option>
                                                        <option value="col-md-6"
                                                            {{ old('clase_grid', $configuracion->clase_grid ?? '') == 'col-md-6' ? 'selected' : '' }}>
                                                            Media Pantalla (50%)</option>
                                                        <option value="col-md-4"
                                                            {{ old('clase_grid', $configuracion->clase_grid ?? '') == 'col-md-4' ? 'selected' : '' }}>
                                                            Un Tercio (33%)</option>
                                                        <option value="col-md-9"
                                                            {{ old('clase_grid', $configuracion->clase_grid ?? '') == 'col-md-9' ? 'selected' : '' }}>
                                                            Tres Cuartos (75%)</option>
                                                        <option value="col-md-8"
                                                            {{ old('clase_grid', $configuracion->clase_grid ?? '') == 'col-md-8' ? 'selected' : '' }}>
                                                            Dos Tercios (66%)</option>
                                                        <option value="col-md-3"
                                                            {{ old('clase_grid', $configuracion->clase_grid ?? '') == 'col-md-3' ? 'selected' : '' }}>
                                                            Un Cuarto (25%)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="anios_historial" class="form-label">
                                                        Años de historial
                                                            <i class="fa-solid fa-circle-info text-muted ms-1 cursor-pointer"
                                                             data-bs-toggle="tooltip" data-bs-placement="top"
                                                             title="Define cuántos cortes históricos se muestran en la tarjeta y cuántos años estarán disponibles para explorar. Con 1 año no se muestra una tendencia comparativa."></i>
                                                    </label>
                                                    <select class="form-select" id="anios_historial" name="anios_historial">
                                                        @php
                                                            $currentAnios = old('anios_historial', $configuracion->anios_historial ?? 5);
                                                            $maxDefault = max(5, $currentAnios);
                                                        @endphp
                                                        @for ($i = 1; $i <= $maxDefault; $i++)
                                                            <option value="{{ $i }}" {{ $i == $currentAnios ? 'selected' : '' }}>
                                                                {{ $i == 1 ? '1 año' : $i . ' años' }}
                                                            </option>
                                                        @endfor
                                                    </select>

                                                </div>
                                            </div>

                                            <details class="advanced-panel">
                                                <summary><i class="fa-solid fa-sliders me-2"></i>Ajustes técnicos avanzados <span class="fw-normal text-muted">(opcional)</span></summary>
                                                <div class="advanced-panel__body">
                                                <label for="ajustes_visuales" class="form-label">
                                                    Ajustes JSON (Avanzado)
                                                    <i class="fa-solid fa-circle-info text-muted ms-1 cursor-pointer"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="Configuraciones estéticas en formato JSON para personalizar la gráfica (colores de series, límites de ejes, leyendas adicionales, etc.)."></i>
                                                </label>
                                                <textarea class="form-control font-monospace" id="ajustes_visuales" name="ajustes_visuales" rows="4"
                                                    style="font-size: 0.8rem;" placeholder='{"colors": ["#861e34", "#c79b66"]}'>{{ old('ajustes_visuales', isset($configuracion->ajustes_visuales) ? json_encode($configuracion->ajustes_visuales, JSON_PRETTY_PRINT) : '') }}</textarea>
                                                        <div class="field-help"><i class="fa-solid fa-triangle-exclamation"></i><span>Úsalo solo si necesitas personalización técnica. El formulario funciona correctamente sin escribir JSON.</span></div>
                                                </div>
                                            </details>
                                        </div>

                                        {{-- Tab 2: Editorial --}}
                                        <div class="tab-pane fade" id="tab-contenido" role="tabpanel">
                                            <h4 class="form-section-title"><i class="fa-solid fa-pen-nib"></i>Narrativa y estilo</h4>
                                            <p class="section-lead">Convierte el dato en una explicación clara. Los campos opcionales usan valores predeterminados si se dejan vacíos.</p>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-8">
                                                    <label for="titulo_reporte" class="form-label">Título
                                                        Editorial</label>
                                                    <input type="text" class="form-control" id="titulo_reporte"
                                                        name="titulo_reporte"
                                                        value="{{ old('titulo_reporte', $configuracion->titulo_reporte ?? '') }}"
                                                        placeholder="Usar nombre del indicador por defecto">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="icono" class="form-label">Icono de la tarjeta</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i id="icon-preview"
                                                                class="{{ $configuracion->icono ?? 'fas fa-info-circle' }} text-vino"></i></span>
                                                        <input type="text" class="form-control" id="icono"
                                                            name="icono"
                                                            value="{{ old('icono', $configuracion->icono ?? 'fas fa-info-circle') }}">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-4">
                                                <label for="subtitulo_reporte" class="form-label">
                                                    Subtítulo <span class="text-muted fw-normal">(opcional)</span>
                                                </label>
                                                <input type="text" class="form-control @error('subtitulo_reporte') is-invalid @enderror"
                                                    id="subtitulo_reporte" name="subtitulo_reporte" maxlength="255"
                                                    value="{{ old('subtitulo_reporte', $configuracion->subtitulo_reporte ?? '') }}"
                                                    placeholder="Explica brevemente qué revela esta visualización">
                                                <div class="form-text">Se mostrará debajo del título en la ficha municipal.</div>
                                                @error('subtitulo_reporte')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-0">
                                                <label for="plantilla_narrativa" class="form-label">
                                                    Narrativa Dinámica
                                                    <i class="fa-solid fa-circle-info text-muted ms-1 cursor-pointer"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="Redacción en lenguaje natural del comportamiento del indicador. Las etiquetas entre llaves {tag} se reemplazarán en tiempo real con valores dinámicos."></i>
                                                </label>
                                                <textarea class="form-control" id="plantilla_narrativa" name="plantilla_narrativa" rows="5"
                                                    placeholder="Escribe usando las etiquetas disponibles...">{{ old('plantilla_narrativa', $configuracion->plantilla_narrativa ?? '') }}</textarea>

                                                <div class="placeholder-helper mt-3">
                                                    <div class="mb-2 fw-bold text-vino small">ETIQUETAS BASE:</div>
                                                    <span class="placeholder-tag"
                                                        onclick="insertAtCursor('{municipio}')">{municipio}</span>
                                                    <span class="placeholder-tag"
                                                        onclick="insertAtCursor('{valor}')">{valor}</span>
                                                    <span class="placeholder-tag"
                                                        onclick="insertAtCursor('{unidad}')">{unidad}</span>
                                                    <span class="placeholder-tag"
                                                        onclick="insertAtCursor('{anio}')">{anio}</span>
                                                    <span class="placeholder-tag"
                                                        onclick="insertAtCursor('{ranking}')">{ranking}</span>

                                                    <div id="dynamic_tags_container" style="display:none;"
                                                        class="mt-3">
                                                        <div
                                                            class="mb-2 fw-bold text-vino small border-top pt-2 text-uppercase">
                                                            Variables del Indicador:</div>
                                                        <div id="dynamic_tags_list"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="config-actions border-top">
                                        <a href="{{ route('admin.configuracion-fichas.index') }}" class="btn btn-light border px-4">Cancelar</a>
                                        <button type="submit" class="btn btn-custom-primary btn-lg px-5 shadow">
                                            <i class="fas fa-save me-2"></i>Guardar Configuración
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview --}}
                <div class="col-lg-4">
                    <div class="preview-sticky">
                        <div class="preview-card shadow-sm border-0">
                            <div class="preview-caption"><i class="fa-solid fa-eye"></i>VISTA PREVIA EN FICHA</div>
                            <div class="mock-card shadow">
                                <div class="mock-header">
                                    <div class="mock-icon shadow-sm">
                                        <i id="preview-mock-icon"
                                            class="{{ $configuracion->icono ?? 'fas fa-info-circle' }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mock-title" id="preview-mock-title">
                                            {{ $configuracion->titulo_reporte ?: $configuracion->indicador->nombre_amigable ?? 'Nuevo Indicador' }}
                                        </h3>
                                        <p class="mock-subtitle {{ $configuracion->subtitulo_reporte ? '' : 'd-none' }}" id="preview-mock-subtitle">
                                            {{ $configuracion->subtitulo_reporte }}
                                        </p>
                                    </div>
                                    <div class="mock-year">2024</div>
                                </div>
                                <div class="mock-body" id="preview-mock-narrative">
                                    {{ $configuracion->plantilla_narrativa ?: 'Escribe una narrativa...' }}
                                </div>
                                <div class="mock-chart" id="preview-mock-chart">
                                    <i
                                        class="{{ $icons[$configuracion->tipo_visualizacion ?? 'kpi'] ?? 'fas fa-chart-bar' }} me-2"></i>
                                    <span
                                        id="preview-mock-vis-name">{{ ucfirst($configuracion->tipo_visualizacion ?? 'KPI') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

</x-admin-layout>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const variablesByIndicatorUrl = @json(route('admin.configuracion-fichas.api-variables', ['indicador' => '__INDICATOR__']));
        const allVariablesUrl = @json(route('admin.configuracion-fichas.api-todas-variables'));
        const availableYearsUrl = @json(route('admin.configuracion-fichas.api-anios'));
        const correlationUrl = @json(route('admin.configuracion-fichas.api-correlacion'));
        let correlationController = null;

        // TomSelect
        let indicadorSelect = new TomSelect('#indicador_id', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            maxOptions: {{ $total_indicadores ?? 'null' }},
        });

        let variablesSelect = new TomSelect('#variables_ids', {
            plugins: ['remove_button'],
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
        const scatterVariableX = document.getElementById('scatter_variable_x');
        const scatterVariableY = document.getElementById('scatter_variable_y');
        const scatterXSelect = new TomSelect(scatterVariableX, {
            create: false,
            maxItems: 1,
            placeholder: 'Selecciona el posible factor...',
            sortField: { field: 'text', direction: 'asc' }
        });
        const scatterYSelect = new TomSelect(scatterVariableY, {
            create: false,
            maxItems: 1,
            placeholder: 'Primero selecciona el eje X...',
            sortField: { field: 'text', direction: 'asc' }
        });
        let syncingScatterAxes = false;

        // Visual Selector
        document.querySelectorAll('.vis-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.querySelectorAll('.vis-option').forEach(o => o.classList.remove(
                    'active'));
                this.classList.add('active');
                const val = this.dataset.value;
                document.getElementById('tipo_visualizacion_hidden').value = val;

                document.getElementById('preview-mock-vis-name').innerText = val.charAt(0)
                    .toUpperCase() + val.slice(1);
                const icons = {
                    'kpi': 'fas fa-stopwatch-20',
                    'piramide': 'fas fa-align-center',
                    'treemap': 'fas fa-th-large',
                    'barras': 'fas fa-chart-bar',
                    'lineas': 'fas fa-chart-line',
                    'mapa': 'fas fa-map-marked-alt',
                    'scatter': 'fas fa-project-diagram'
                };
                document.getElementById('preview-mock-chart').querySelector('i').className = (
                    icons[val] || 'fas fa-chart-bar') + ' me-2';
                
                // Re-fetch variables si cambiamos a scatter o regresamos de scatter
                const indicadorId = indicadorSelect.getValue();
                const variablesRequest = indicadorId
                    ? fetchVariables(indicadorId, variablesSelect.getValue())
                    : Promise.resolve();
                updateCorrelationPanel();

                if (val === 'scatter') {
                    variablesRequest.then(function() {
                        bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-datos-tab')).show();
                        setTimeout(function() {
                            document.getElementById('scatter-assistant').scrollIntoView({ behavior: 'smooth', block: 'center' });
                            if (!indicadorId) indicadorSelect.focus();
                        }, 180);
                    });
                }
            });
        });

        // Live Preview
        const inputTitle = document.getElementById('titulo_reporte');
        const inputSubtitle = document.getElementById('subtitulo_reporte');
        const inputIcon = document.getElementById('icono');
        const inputNarrative = document.getElementById('plantilla_narrativa');

        const mockTitle = document.getElementById('preview-mock-title');
        const mockSubtitle = document.getElementById('preview-mock-subtitle');
        const mockIcon = document.getElementById('preview-mock-icon');
        const mockIconInput = document.getElementById('icon-preview');
        const mockNarrative = document.getElementById('preview-mock-narrative');

        function updatePreview() {
            let t = inputTitle.value.trim();
            if (!t && indicadorSelect.getValue()) {
                const indicatorId = String(indicadorSelect.getValue());
                const originalOption = Array.from(document.getElementById('indicador_id').options)
                    .find(option => option.value === indicatorId);
                t = originalOption?.dataset.nombre
                    || indicadorSelect.options[indicatorId]?.text
                    || '';
            }
            mockTitle.innerText = t || 'Nuevo Indicador';

            const subtitle = inputSubtitle.value.trim();
            mockSubtitle.innerText = subtitle;
            mockSubtitle.classList.toggle('d-none', !subtitle);

            mockIcon.className = inputIcon.value || 'fas fa-info-circle';
            mockIconInput.className = (inputIcon.value || 'fas fa-info-circle') + ' text-vino';

            let n = inputNarrative.value.trim();
            if (!n) n = 'Escribe una narrativa para ver cómo se presenta en el perfil municipal...';

            n = n.replace(/{municipio}/g, '<strong class="text-vino">Puebla</strong>')
                .replace(/{valor}/g, '<strong class="text-vino">127,605</strong>')
                .replace(/{unidad}/g, '<strong class="text-vino">personas</strong>')
                .replace(/{anio}/g, '<strong class="text-vino">2024</strong>');

            mockNarrative.innerHTML = n;
        }

        inputTitle.addEventListener('input', updatePreview);
        inputSubtitle.addEventListener('input', updatePreview);
        inputIcon.addEventListener('input', updatePreview);
        inputNarrative.addEventListener('input', updatePreview);
        indicadorSelect.on('change', updatePreview);

        let oldVariables = @json(old(
                'variables_ids',
                isset($configuracion) && $configuracion->variables ? $configuracion->variables->pluck('id')->toArray() : []));

        let currentAniosHistorialValue = @json(old('anios_historial', $configuracion->anios_historial ?? 5));

        function populateAniosHistorial(availableYears, selectedVal) {
            const select = document.getElementById('anios_historial');
            if (!select) return;

            const years = (Array.isArray(availableYears) ? availableYears : [])
                .map(Number)
                .filter(Number.isFinite)
                .sort((a, b) => b - a);
            const previousVal = parseInt(select.value || selectedVal, 10);
            select.innerHTML = '';

            if (years.length === 0) {
                const option = document.createElement('option');
                option.value = '1';
                option.textContent = 'Sin años disponibles';
                option.disabled = true;
                select.appendChild(option);
                return;
            }

            const selected = previousVal >= 1 && previousVal <= years.length
                ? previousVal
                : Math.min(years.length, 5);

            for (let count = 1; count <= years.length; count++) {
                const option = document.createElement('option');
                option.value = count;
                option.textContent = count === 1
                    ? `1 corte: ${years[0]}`
                    : `${count} cortes: ${years.slice(0, count).join(', ')}`;
                option.selected = count === selected;
                select.appendChild(option);
            }
        }

        function fetchAniosDisponibles(indicadorId, variablesIds = []) {
            if (!indicadorId) {
                populateAniosHistorial([], currentAniosHistorialValue);
                return;
            }

            if (typeof variablesIds === 'string') {
                variablesIds = variablesIds ? [variablesIds] : [];
            }

            let url = `${availableYearsUrl}?indicador_id=${encodeURIComponent(indicadorId)}`;
            if (variablesIds && variablesIds.length > 0) {
                const filteredVars = variablesIds.filter(v => v);
                if (filteredVars.length > 0) {
                    url += `&variables_ids=${filteredVars.join(',')}`;
                }
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const selectEl = document.getElementById('anios_historial');
                        const activeVal = selectEl ? selectEl.value : currentAniosHistorialValue;
                        populateAniosHistorial(data.anios || [], activeVal);
                    }
                })
                .catch(error => {
                    console.error('Error fetching available years:', error);
                    populateAniosHistorial([], currentAniosHistorialValue);
                });
        }

        function updateAniosTrend() {
            const indicadorId = indicadorSelect.getValue();
            const selectedVars = variablesSelect.getValue();
            fetchAniosDisponibles(indicadorId, selectedVars);
        }

        function fetchVariables(indicadorId, preSelectedIds = []) {
            if (!indicadorId) return;

            const tipoVis = document.getElementById('tipo_visualizacion_hidden').value;
            const url = tipoVis === 'scatter' 
                ? allVariablesUrl
                : variablesByIndicatorUrl.replace('__INDICATOR__', encodeURIComponent(indicadorId));
            const selectedIds = (Array.isArray(preSelectedIds) ? preSelectedIds : [preSelectedIds])
                .filter(Boolean)
                .map(String);

            variablesSelect.disable();
            return fetch(url)
                .then(response => response.json())
                .then(data => {
                    variablesSelect.clear(true);
                    variablesSelect.clearOptions();
                    window.indicadorVariablesData = data;
                    data.forEach(item => variablesSelect.addOption({
                        value: String(item.id),
                        text: item.text
                    }));
                    const availableIds = new Set(data.map(item => String(item.id)));
                    const validSelectedIds = selectedIds.filter(id => availableIds.has(id));
                    if (validSelectedIds.length > 0) {
                        variablesSelect.setValue(validSelectedIds, true);
                    }
                    variablesSelect.enable();
                    variablesSelect.refreshOptions(false);
                    populateScatterAxes();
                    updateDynamicTags();
                    updateAniosTrend();
                    updateCorrelationPanel();
                })
                .catch(error => {
                    variablesSelect.enable();
                    console.error('Error fetching variables:', error);
                });
        }

        // Variables AJAX al cambiar indicador
        indicadorSelect.on('change', function(value) {
            fetchVariables(value, []);
        });

        // Si ya hay un indicador seleccionado al cargar (por old() en create, o al editar)
        if (indicadorSelect.getValue()) {
            fetchVariables(indicadorSelect.getValue(), oldVariables);
        } else {
            updateAniosTrend();
        }

        variablesSelect.on('change', function() {
            if (!syncingScatterAxes) populateScatterAxes();
            updateDynamicTags();
            updateAniosTrend();
            updateCorrelationPanel();
        });

        document.getElementById('incluir_spearman').addEventListener('change', updateCorrelationPanel);
        scatterXSelect.on('change', function(value) {
            populateScatterAxes(value, scatterYSelect.getValue());
            syncScatterAxes();
        });
        scatterYSelect.on('change', syncScatterAxes);

        function populateScatterAxes(preferredX = null, preferredY = null) {
            const data = window.indicadorVariablesData || [];
            const selectedIds = variablesSelect.getValue();
            const selectedX = String(preferredX || selectedIds[0] || scatterXSelect.getValue() || '');
            const selectedY = String(preferredY || selectedIds[1] || scatterYSelect.getValue() || '');

            scatterXSelect.clear(true);
            scatterXSelect.clearOptions();
            data.forEach(function(item) {
                scatterXSelect.addOption({ value: String(item.id), text: axisOptionLabel(item) });
            });
            if (selectedX) scatterXSelect.setValue(selectedX, true);
            scatterXSelect.refreshOptions(false);

            scatterYSelect.clear(true);
            scatterYSelect.clearOptions();
            data.forEach(function(item) {
                if (String(item.id) === selectedX) return;
                scatterYSelect.addOption({ value: String(item.id), text: axisOptionLabel(item) });
            });
            if (selectedY && selectedY !== selectedX) scatterYSelect.setValue(selectedY, true);
            scatterYSelect.settings.placeholder = selectedX
                ? 'Selecciona el resultado...'
                : 'Primero selecciona el eje X...';
            scatterYSelect.inputState();
            selectedX ? scatterYSelect.enable() : scatterYSelect.disable();
            scatterYSelect.refreshOptions(false);
        }

        function axisOptionLabel(item) {
            const unit = item.unidad ? ` · ${item.unidad}` : '';
            return `${item.text}${unit}`;
        }

        function syncScatterAxes() {
            const values = [scatterXSelect.getValue(), scatterYSelect.getValue()].filter(Boolean);
            syncingScatterAxes = true;
            variablesSelect.setValue(values);
            syncingScatterAxes = false;
            updateCorrelationPanel();
        }

        function updateCorrelationPanel() {
            const panel = document.getElementById('correlation-panel');
            const status = document.getElementById('correlation-status');
            const results = document.getElementById('correlation-results');
            const meta = document.getElementById('correlation-meta');
            const spearmanMetric = document.getElementById('spearman-metric');
            const isScatter = document.getElementById('tipo_visualizacion_hidden').value === 'scatter';
            const selectedIds = variablesSelect.getValue();
            const includeSpearman = document.getElementById('incluir_spearman').checked;
            const assistant = document.getElementById('scatter-assistant');
            const variablesHelp = document.getElementById('variables-help');

            panel.classList.toggle('d-none', !isScatter);
            assistant.classList.toggle('d-none', !isScatter);
            variablesSelect.wrapper.classList.toggle('d-none', isScatter);
            variablesHelp.classList.toggle('d-none', isScatter);
            if (!isScatter) return;

            spearmanMetric.classList.toggle('d-none', !includeSpearman);
            if (selectedIds.length !== 2) {
                if (correlationController) correlationController.abort();
                results.classList.add('d-none');
                meta.classList.add('d-none');
                status.innerHTML = selectedIds.length > 2
                    ? '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Seleccionaste más de dos variables. Conserva únicamente el eje X y el eje Y.</span>'
                    : 'Selecciona exactamente dos variables para analizar su relación.';
                return;
            }

            if (correlationController) correlationController.abort();
            correlationController = new AbortController();
            status.innerHTML = '<span class="text-success"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Calculando con datos municipales comparables...</span>';
            results.classList.add('d-none');
            meta.classList.add('d-none');

            const params = new URLSearchParams({
                variable_x_id: selectedIds[0],
                variable_y_id: selectedIds[1],
                incluir_spearman: includeSpearman ? '1' : '0'
            });

            fetch(`${correlationUrl}?${params.toString()}`, { signal: correlationController.signal })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'No fue posible calcular la correlación.');
                    return data;
                })
                .then(data => {
                    status.textContent = 'Resultado preliminar para validar la utilidad de la relación seleccionada.';
                    document.getElementById('pearson-value').textContent = formatCoefficient(data.pearson, 'r');
                    document.getElementById('pearson-reading').textContent = data.pearson_lectura;
                    document.getElementById('spearman-value').textContent = formatCoefficient(data.spearman, 'ρ');
                    document.getElementById('spearman-reading').textContent = data.spearman_lectura || '';
                    meta.innerHTML = `<i class="fa-solid fa-lightbulb me-1"></i><strong>Orientación:</strong> ${data.diagnostico}<br><i class="fa-solid fa-database me-1 mt-2"></i><strong>${data.n}</strong> municipios comparables · X: ${data.anio_x} · Y: ${data.anio_y}<br><i class="fa-solid fa-shield-halved me-1 mt-2"></i>${data.advertencia}`;
                    results.classList.remove('d-none');
                    meta.classList.remove('d-none');
                })
                .catch(error => {
                    if (error.name === 'AbortError') return;
                    status.innerHTML = `<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>${error.message}</span>`;
                });
        }

        function formatCoefficient(value, symbol) {
            return value === null || value === undefined ? `${symbol} = N/D` : `${symbol} = ${Number(value).toFixed(3)}`;
        }

        function updateDynamicTags() {
            const container = document.getElementById('dynamic_tags_container');
            const list = document.getElementById('dynamic_tags_list');
            if (!window.indicadorVariablesData) return;

            let selectedIds = variablesSelect.getValue();
            let varsToShow = (!selectedIds || selectedIds.length === 0) ?
                window.indicadorVariablesData :
                window.indicadorVariablesData.filter(v => selectedIds.includes(v.id.toString()));

            let html = '';
            varsToShow.forEach(v => {
                html +=
                    `<span class="placeholder-tag me-1" onclick="insertAtCursor('${v.tag_valor}')">${v.tag_valor}</span>`;
            });
            list.innerHTML = html;
            container.style.display = varsToShow.length > 0 ? 'block' : 'none';
        }

        const mostrarComparativaSwitch = document.getElementById('mostrar_comparativa');
        const benchmarkModeGroup = document.getElementById('benchmark_mode_group');
        if (mostrarComparativaSwitch && benchmarkModeGroup) {
            mostrarComparativaSwitch.addEventListener('change', function() {
                benchmarkModeGroup.style.display = this.checked ? 'block' : 'none';
            });
        }

        // Inicializar tooltips de Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        updatePreview();
    });

    function insertAtCursor(text) {
        const textarea = document.getElementById('plantilla_narrativa');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + text.length, start + text.length);
        textarea.dispatchEvent(new Event('input', {
            bubbles: true
        }));
    }
</script>
