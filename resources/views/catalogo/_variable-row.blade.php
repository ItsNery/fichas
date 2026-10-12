<div class="variable-card border rounded-3 p-3 p-md-4 mb-3 bg-white shadow-sm" data-index="{{ $index }}">
    <input type="hidden" name="variables[{{ $index }}][id]" value="{{ $var->id ?? '' }}">
    <div class="d-flex justify-content-between align-items-center gap-3 pb-3 mb-3 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-vino fw-bold small" style="width:2rem; height:2rem;">
                <span class="var-display-index">{{ is_numeric($index) ? $index + 1 : '__DISPLAY_INDEX__' }}</span>
            </span>
            <div>
                <h6 class="mb-0 fw-bold text-dark">Variable <span class="var-display-index">{{ is_numeric($index) ? $index + 1 : '__DISPLAY_INDEX__' }}</span></h6>
                <span class="small text-muted">Configuración del valor</span>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-variable-btn" title="Eliminar variable">
            <i class="fa-solid fa-trash me-1"></i><span class="d-none d-sm-inline">Eliminar</span>
        </button>
    </div>
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label fw-bold text-secondary small">Nombre Amigable</label>
            <input type="text" name="variables[{{ $index }}][nombre_amigable]" class="form-control form-control-sm var-nombre-amigable" value="{{ $var->nombre_amigable ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold text-secondary small">Nombre Técnico</label>
            <input type="text" name="variables[{{ $index }}][nombre_tecnico]" class="form-control form-control-sm font-monospace var-nombre-tecnico" value="{{ $var->nombre_tecnico ?? '' }}" data-auto-gen="true">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold text-secondary small">Unidad</label>
            <input type="text" name="variables[{{ $index }}][unidad_medida]" class="form-control form-control-sm" value="{{ $var->unidad_medida ?? '' }}">
        </div>
    </div>
    <div class="row g-3 mt-1 p-3 mx-0 bg-light rounded-3 border">
        <div class="col-md-2">
            <label class="form-label fw-bold text-secondary small">Orden</label>
            <input type="number" name="variables[{{ $index }}][orden]" class="form-control form-control-sm" value="{{ $var->orden ?? (is_numeric($index) ? $index : '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold text-secondary small">Tipo de valor</label>
            <select name="variables[{{ $index }}][tipo_valor]" class="form-select form-select-sm tipo-valor-select">
                <option value="">Automático</option>
                <option value="categorica" {{ ($var->tipo_valor ?? '') == 'categorica' ? 'selected' : '' }}>Categórica</option>
                <option value="numerica" {{ ($var->tipo_valor ?? '') == 'numerica' ? 'selected' : '' }}>Numérica</option>
            </select>
        </div>
        <div class="col-md-7 d-flex align-items-end gap-3 pb-1 flex-wrap">
            <span class="small fw-bold text-secondary me-1">Mostrar como</span>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="variables[{{ $index }}][es_destacada]" value="1" id="var_dest_{{ $index }}" {{ ($var->es_destacada ?? false) ? 'checked' : '' }}>
                <label class="form-check-label small" for="var_dest_{{ $index }}">Destacada</label>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="variables[{{ $index }}][es_kpi]" value="1" id="var_kpi_{{ $index }}" {{ ($var->es_kpi ?? false) ? 'checked' : '' }}>
                <label class="form-check-label small" for="var_kpi_{{ $index }}">KPI</label>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="variables[{{ $index }}][visible_en_ficha]" value="1" id="var_visible_{{ $index }}" {{ ($var->visible_en_ficha ?? true) ? 'checked' : '' }}>
                <label class="form-check-label small" for="var_visible_{{ $index }}">Visible</label>
            </div>
        </div>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-12 mapeo-valores-wrapper" style="{{ ($var->tipo_valor ?? '') == 'numerica' ? 'display:none;' : '' }}">
            <label class="form-label fw-bold text-secondary small">Mapeo de Valores (JSON)</label>
            <textarea name="variables[{{ $index }}][mapeo_valores]" class="form-control form-control-sm font-monospace" rows="1" placeholder='{"1":"Urbano","2":"Rural"}'>{{ is_array($var->mapeo_valores ?? null) ? json_encode($var->mapeo_valores) : ($var->mapeo_valores ?? '') }}</textarea>
            <div class="form-text">Úsalo solo para convertir valores categóricos en etiquetas legibles.</div>
        </div>
    </div>
    <div class="mt-3 pt-3 border-top">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input es-construida-check" type="checkbox" name="variables[{{ $index }}][es_construida]" value="1" id="var_const_{{ $index }}" {{ ($var->es_construida ?? false) ? 'checked' : '' }}>
                <label class="form-check-label small fw-bold text-info" for="var_const_{{ $index }}">Variable construida <span class="text-muted fw-normal">Calcula este valor a partir de otras variables.</span></label>
            </div>
    </div>
    <div class="formula-section border rounded-3 p-3 mt-3 bg-info bg-opacity-10" style="display:{{ ($var->es_construida ?? false) ? 'block' : 'none' }};">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-1">
                    <label class="form-label fw-bold text-secondary small mb-0"
                        for="formula_tipo_{{ $index }}">Tipo de fórmula</label>
                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary formula-type-help-btn"
                        aria-label="Ver explicación del tipo de fórmula">
                        <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
                    </button>
                </div>
                <select name="variables[{{ $index }}][formula_tipo]" id="formula_tipo_{{ $index }}"
                    class="form-select form-select-sm formula-tipo-select"
                    aria-describedby="formula_tipo_description_{{ $index }}">
                    <option value="division" {{ ($var->formula_tipo ?? '') == 'division' ? 'selected' : '' }}>División (N/D×mult)</option>
                    <option value="tasa_crecimiento" {{ ($var->formula_tipo ?? '') == 'tasa_crecimiento' ? 'selected' : '' }}>Tasa de crecimiento</option>
                    <option value="tasa_crecimiento_inegi_2025" {{ ($var->formula_tipo ?? '') == 'tasa_crecimiento_inegi_2025' ? 'selected' : '' }}>Tasa de crecimiento INEGI 2025</option>
                    <option value="sumatoria" {{ ($var->formula_tipo ?? '') == 'sumatoria' ? 'selected' : '' }}>Sumatoria de variables</option>
                </select>
                <div class="form-text formula-type-description" id="formula_tipo_description_{{ $index }}"
                    aria-live="polite"></div>
            </div>
        </div>
        <div class="formula-division-fields row g-2 mt-1" style="display:{{ in_array(($var->formula_tipo ?? 'division'), ['tasa_crecimiento', 'tasa_crecimiento_inegi_2025', 'sumatoria'], true) ? 'none' : '' }};">
            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary small">Variable numerador</label>
                <select name="variables[{{ $index }}][formula_numerador_id]" class="form-select form-select-sm tom-select-variable">
                    <option value="">Selecciona...</option>
                    @foreach($variables->groupBy(fn($v) => $v->indicador?->nombre_amigable ?? 'Sin indicador') as $indicadorName => $vars)
                    <optgroup label="{{ $indicadorName }}">
                        @foreach($vars as $v)
                        <option value="{{ $v->id }}" {{ (($var->formula_config['numerador_variable_id'] ?? '') == $v->id) ? 'selected' : '' }}>{{ $v->nombre_amigable }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary small">Variable denominador</label>
                <select name="variables[{{ $index }}][formula_denominador_id]" class="form-select form-select-sm tom-select-variable">
                    <option value="">Selecciona...</option>
                    @foreach($variables->groupBy(fn($v) => $v->indicador?->nombre_amigable ?? 'Sin indicador') as $indicadorName => $vars)
                    <optgroup label="{{ $indicadorName }}">
                        @foreach($vars as $v)
                        <option value="{{ $v->id }}" {{ (($var->formula_config['denominador_variable_id'] ?? '') == $v->id) ? 'selected' : '' }}>{{ $v->nombre_amigable }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary small">Multiplicador</label>
                <input type="number" name="variables[{{ $index }}][formula_multiplicador]" class="form-control form-control-sm" value="{{ $var->formula_config['multiplicador'] ?? 100 }}" step="any">
            </div>
        </div>
        <div class="formula-tasa-fields row g-2 mt-1" style="display:{{ in_array(($var->formula_tipo ?? ''), ['tasa_crecimiento', 'tasa_crecimiento_inegi_2025'], true) ? '' : 'none' }};">
            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary small">Variable (universo total)</label>
                <select name="variables[{{ $index }}][formula_variable_id]" class="form-select form-select-sm tom-select-variable">
                    <option value="">Selecciona...</option>
                    @foreach($variables->groupBy(fn($v) => $v->indicador?->nombre_amigable ?? 'Sin indicador') as $indicadorName => $vars)
                    <optgroup label="{{ $indicadorName }}">
                        @foreach($vars as $v)
                        <option value="{{ $v->id }}" {{ (($var->formula_config['variable_id'] ?? '') == $v->id) ? 'selected' : '' }}>{{ $v->nombre_amigable }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary small">Multiplicador</label>
                <input type="number" name="variables[{{ $index }}][formula_multiplicador]" class="form-control form-control-sm formula-tasa-multiplicador" value="{{ $var->formula_config['multiplicador'] ?? 100 }}" step="any" {{ ($var->formula_tipo ?? '') == 'tasa_crecimiento_inegi_2025' ? 'readonly' : '' }}>
            </div>
            <div class="col-12 formula-inegi-help" style="display:{{ ($var->formula_tipo ?? '') == 'tasa_crecimiento_inegi_2025' ? '' : 'none' }};">
                <div class="form-text">Fórmula INEGI: [(Px / P0)^(1 / t) - 1] × 100, donde t es la diferencia entre los años disponibles.</div>
            </div>
        </div>
        <div class="formula-sumatoria-fields row g-2 mt-1" style="display:{{ ($var->formula_tipo ?? '') == 'sumatoria' ? '' : 'none' }};">
            <div class="col-md-10">
                <label class="form-label fw-bold text-secondary small">Variables a sumar</label>
                <select name="variables[{{ $index }}][formula_variable_ids][]" class="form-select form-select-sm tom-select-variable" multiple>
                    @foreach($variables->groupBy(fn($v) => $v->indicador?->nombre_amigable ?? 'Sin indicador') as $indicadorName => $vars)
                    <optgroup label="{{ $indicadorName }}">
                        @foreach($vars as $v)
                        <option value="{{ $v->id }}" {{ in_array($v->id, $var->formula_config['variable_ids'] ?? []) ? 'selected' : '' }}>{{ $v->nombre_amigable }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                <div class="form-text">Se sumarán los valores que coincidan por municipio y año.</div>
            </div>
        </div>
    </div>
</div>
