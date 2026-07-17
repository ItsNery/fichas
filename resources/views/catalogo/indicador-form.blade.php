<x-admin-layout>
    @section('title', $indicador ? 'Editar Indicador' : 'Crear Indicador')

    <x-page-header
        :title="$indicador ? 'Editar Indicador' : 'Crear Indicador'"
        :subtitle="$indicador ? 'Modifica los datos del indicador y sus variables' : 'Nuevo indicador con gestión completa de variables'"
        icon="fa-solid fa-chart-simple" />

    @if ($message = Session::get('success'))
    <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'success', title: '¡Éxito!', text: '{{ $message }}', confirmButtonColor: '#5f1b2d' }));</script>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger mb-4 shadow-sm border-0 border-start border-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $indicador ? route('admin.catalogos.indicadores.actualizar', $indicador) : route('admin.catalogos.indicadores.guardar') }}" id="indicadorForm">
        @csrf
        @if($indicador) @method('PUT') @endif

        {{-- PANEL 1: DATOS DEL INDICADOR --}}
        <div class="card-panel mb-4">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                <i class="fa-solid fa-info-circle text-vino"></i>
                <h5 class="mb-0 fw-bold text-vino" style="font-size:0.95rem;">Datos del Indicador</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Nombre Amigable <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_amigable" id="indicador_nombre_amigable" class="form-control text-vino fw-bold" value="{{ $indicador->nombre_amigable ?? old('nombre_amigable') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Nombre Técnico <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_tecnico" id="indicador_nombre_tecnico" class="form-control font-monospace text-muted" value="{{ $indicador->nombre_tecnico ?? old('nombre_tecnico') }}" data-auto-gen="{{ $indicador ? 'false' : 'true' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Temática <span class="text-danger">*</span></label>
                        <select name="tematica_id" class="form-select" required>
                            <option value="">Selecciona...</option>
                            @foreach($tematicas as $t)
                            <option value="{{ $t->id }}" {{ (($indicador->tematica_id ?? old('tematica_id', $tematicaId ?? null)) == $t->id) ? 'selected' : '' }}>{{ $t->nombre }} ({{ $t->dimension->nombre }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Tipo de Dato</label>
                        <select name="tipo_dato" class="form-select">
                            <option value="absoluto" {{ (($indicador->tipo_dato ?? '') == 'absoluto') ? 'selected' : '' }}>Absoluto</option>
                            <option value="porcentaje" {{ (($indicador->tipo_dato ?? '') == 'porcentaje') ? 'selected' : '' }}>Porcentaje</option>
                            <option value="tasa" {{ (($indicador->tipo_dato ?? '') == 'tasa') ? 'selected' : '' }}>Tasa</option>
                            <option value="indice" {{ (($indicador->tipo_dato ?? '') == 'indice') ? 'selected' : '' }}>Índice</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Polaridad</label>
                        <select name="polaridad" class="form-select">
                            <option value="neutro" {{ (($indicador->polaridad ?? '') == 'neutro') ? 'selected' : '' }}>Neutro</option>
                            <option value="asendente" {{ (($indicador->polaridad ?? '') == 'asendente') ? 'selected' : '' }}>Ascendente</option>
                            <option value="descendente" {{ (($indicador->polaridad ?? '') == 'descendente') ? 'selected' : '' }}>Descendente</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-secondary small">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2">{{ $indicador->descripcion ?? old('descripcion') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Fuente</label>
                        <input type="text" name="fuente" class="form-control" value="{{ $indicador->fuente ?? old('fuente') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Tipo gráfico default</label>
                        <select name="tipo_grafico_default" class="form-select">
                            <option value="">Automático</option>
                            <option value="Barras" {{ (($indicador->tipo_grafico_default ?? '') == 'Barras') ? 'selected' : '' }}>Barras</option>
                            <option value="Lineal" {{ (($indicador->tipo_grafico_default ?? '') == 'Lineal') ? 'selected' : '' }}>Lineal</option>
                            <option value="Piramide" {{ (($indicador->tipo_grafico_default ?? '') == 'Piramide') ? 'selected' : '' }}>Pirámide</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">Orden</label>
                        <input type="number" name="orden" class="form-control" value="{{ $indicador->orden ?? old('orden', 0) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-secondary small">Método de Cálculo</label>
                        <textarea name="metodo_calculo" class="form-control" rows="2">{{ $indicador->metodo_calculo ?? old('metodo_calculo') }}</textarea>
                    </div>
                    <div class="col-12 bg-light p-3 rounded border">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="solo_resumen" value="1" id="ind_solo_resumen" {{ ($indicador->solo_resumen ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="ind_solo_resumen">Solo Resumen</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="es_complejo" value="1" id="ind_es_complejo" {{ ($indicador->es_complejo ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="ind_es_complejo">Complejo</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="priorizar_total" value="1" id="ind_priorizar_total" {{ ($indicador->priorizar_total ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="ind_priorizar_total">Priorizar "Total"</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="visible_en_ficha" value="1" id="ind_visible_en_ficha" {{ ($indicador->visible_en_ficha ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="ind_visible_en_ficha">Visible en fichas públicas</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PANEL 2: VARIABLES --}}
        <div class="card-panel mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-tags text-vino"></i>
                    <h5 class="mb-0 fw-bold text-vino" style="font-size:0.95rem;">Variables</h5>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success" id="addVariableBtn"><i class="fa-solid fa-plus me-1"></i>Añadir variable</button>
            </div>
            <div class="card-body p-4">
                <div id="variablesContainer">
                    @forelse (($indicador->variables ?? []) as $var)
                    @include('catalogo._variable-row', ['var' => $var, 'index' => $loop->index, 'variables' => $variables])
                    @empty
                    <div class="text-center text-muted py-5" id="noVariablesMsg">
                        <i class="fa-solid fa-plus-circle fa-2x mb-2 opacity-50"></i>
                        <p class="small mb-0">Aún no hay variables. Haz clic en <strong>"Añadir variable"</strong> para comenzar.</p>
                    </div>
                    @endforelse
                </div>

                {{-- Template para nueva variable --}}
                <template id="variableRowTemplate">
                    @include('catalogo._variable-row', ['var' => null, 'index' => '__INDEX__', 'variables' => $variables])
                </template>
            </div>
        </div>

        {{-- PANEL 3: PREVISUALIZAR / GENERAR --}}
        <div class="card-panel mb-4" id="generationPanel" style="{{ $indicador && $indicador->variables->contains('es_construida', true) ? '' : 'display:none;' }}">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                <i class="fa-solid fa-bolt text-info"></i>
                <h5 class="mb-0 fw-bold text-vino" style="font-size:0.95rem;">Generación de datos para variables construidas</h5>
            </div>
            <div class="card-body p-4">
                <p class="small text-muted mb-3">Selecciona una variable construida para previsualizar y generar sus datos históricos.</p>
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <select id="generationVariableSelect" class="form-select" style="max-width:300px;">
                        <option value="">Selecciona variable...</option>
                    </select>
                    <button type="button" class="btn btn-outline-info btn-sm" id="previewBtn" disabled><i class="fa-solid fa-eye me-1"></i>Previsualizar</button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="generateBtn" disabled><i class="fa-solid fa-bolt me-1"></i>Generar datos</button>
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
        <div class="d-flex justify-content-end gap-3 mt-4 mb-5">
            <a href="{{ route('admin.catalogos.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
            <button type="submit" class="btn btn-custom-primary px-4"><i class="fa-solid fa-save me-1"></i>{{ $indicador ? 'Guardar cambios' : 'Crear indicador' }}</button>
        </div>
    </form>

    {{-- MODAL PREVISUALIZACIÓN --}}
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-vino"><i class="fa-solid fa-eye me-2 text-info"></i>Previsualización</h5>
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
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-custom-primary px-4" id="confirmGenerateBtn"><i class="fa-solid fa-bolt me-1"></i>Generar datos</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let varIndex = {{ ($indicador?->variables?->count() ?? 0) }};
            const container = document.getElementById('variablesContainer');
            const noMsg = document.getElementById('noVariablesMsg');
            const generationPanel = document.getElementById('generationPanel');
            const genSelect = document.getElementById('generationVariableSelect');
            let previewVariableId = null;
            let previewData = [];

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

            // --- AÑADIR VARIABLE ---
            document.getElementById('addVariableBtn').addEventListener('click', function() {
                const tmpl = document.getElementById('variableRowTemplate').innerHTML;
                const html = tmpl.replace(/__INDEX__/g, varIndex).replace(/__DISPLAY_INDEX__/g, varIndex + 1).replace(/__ID__/g, '');
                if (noMsg) noMsg.style.display = 'none';
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                const card = wrapper.firstElementChild;
                container.appendChild(card);
                attachVarEvents(card);
                initTomSelectsOnCard(card);
                varIndex++;
                actualizarSelectGeneracion();
            });

            // --- ELIMINAR VARIABLE ---
            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-variable-btn');
                if (btn && confirm('¿Eliminar esta variable?')) {
                    const card = btn.closest('.variable-card');
                    card.querySelectorAll('.tom-select-variable').forEach(el => {
                        if (el.tomselect) el.tomselect.destroy();
                    });
                    card.remove();
                    if (container.querySelectorAll('.variable-card').length === 0 && noMsg) noMsg.style.display = 'block';
                    actualizarSelectGeneracion();
                }
            });

            // --- TOGGLE FORMULA ---
            container.addEventListener('change', function(e) {
                const check = e.target.closest('.es-construida-check');
                if (check) {
                    const card = check.closest('.variable-card');
                    const formulaSection = card.querySelector('.formula-section');
                    formulaSection.style.display = check.checked ? 'block' : 'none';
                    actualizarSelectGeneracion();
                }
            });

            // --- TOGGLE TIPO FORMULA ---
            container.addEventListener('change', function(e) {
                const sel = e.target.closest('.formula-tipo-select');
                if (sel) {
                    const card = sel.closest('.variable-card');
                    card.querySelector('.formula-division-fields').style.display = sel.value === 'tasa_crecimiento' ? 'none' : '';
                    card.querySelector('.formula-tasa-fields').style.display = sel.value === 'tasa_crecimiento' ? '' : 'none';
                    card.querySelector('.formula-sumatoria-fields').style.display = sel.value === 'sumatoria' ? '' : 'none';
                }
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

            function attachVarEvents(card) {
                const check = card.querySelector('.es-construida-check');
                const sel = card.querySelector('.formula-tipo-select');
                if (check) {
                    const fs = card.querySelector('.formula-section');
                    fs.style.display = check.checked ? 'block' : 'none';
                }
                if (sel) {
                    const cardEl = sel.closest('.variable-card');
                    cardEl.querySelector('.formula-division-fields').style.display = ['tasa_crecimiento', 'sumatoria'].includes(sel.value) ? 'none' : '';
                    cardEl.querySelector('.formula-tasa-fields').style.display = sel.value === 'tasa_crecimiento' ? '' : 'none';
                    cardEl.querySelector('.formula-sumatoria-fields').style.display = sel.value === 'sumatoria' ? '' : 'none';
                }
                // Toggle mapeo_valores on load
                const tipoValor = card.querySelector('.tipo-valor-select');
                const mapeoWrapper = card.querySelector('.mapeo-valores-wrapper');
                if (tipoValor && mapeoWrapper) {
                    mapeoWrapper.style.display = tipoValor.value === 'numerica' ? 'none' : '';
                }
            }

            // Attach events to existing rows
            document.querySelectorAll('.variable-card').forEach(card => {
                attachVarEvents(card);
                initTomSelectsOnCard(card);
            });

            // --- ACTUALIZAR SELECT DE GENERACIÓN ---
            function actualizarSelectGeneracion() {
                const cards = container.querySelectorAll('.variable-card');
                let hasBuilt = false;
                genSelect.innerHTML = '<option value="">Selecciona variable...</option>';
                cards.forEach(card => {
                    const name = card.querySelector('input[name$="[nombre_amigable]"]')?.value || 'Variable';
                    const isBuilt = card.querySelector('.es-construida-check')?.checked;
                    if (isBuilt) {
                        hasBuilt = true;
                        const id = card.querySelector('input[name$="[id]"]')?.value || '';
                        genSelect.innerHTML += `<option value="${id}">${name}</option>`;
                    }
                });
                generationPanel.style.display = hasBuilt ? '' : 'none';
            }

            // --- PREVISUALIZAR ---
            document.getElementById('previewBtn').addEventListener('click', function() {
                const varId = genSelect.value;
                if (!varId) return;
                previewVariableId = varId;

                const modal = new bootstrap.Modal(document.getElementById('previewModal'));
                document.getElementById('modalPreviewBody').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>';
                modal.show();

                fetch(`/admin/catalogos/variables/${varId}/preview`, {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.error) { document.getElementById('modalPreviewBody').innerHTML = `<tr><td colspan="5" class="text-center text-danger">${data.error}</td></tr>`; return; }
                    previewData = data.rows || [];
                    const isTasa = previewData.length && previewData[0].valor_numerador === undefined;
                    document.getElementById('modalPreviewCol1').textContent = isTasa ? 'Valor anterior' : 'Valor numerador';
                    document.getElementById('modalPreviewCol2').textContent = isTasa ? 'Valor actual' : 'Valor denominador';
                    let html = '';
                    previewData.forEach(r => { html += `<tr><td>${r.municipio||'—'}</td><td>${r.anio||'—'}</td><td>${r.valor_numerador??r.valor_anterior??'—'}</td><td>${r.valor_denominador??r.valor_actual??'—'}</td><td class="fw-bold text-info">${r.valor??'—'}</td></tr>`; });
                    document.getElementById('modalPreviewBody').innerHTML = html || '<tr><td colspan="5" class="text-center text-muted">Sin datos</td></tr>';
                    document.getElementById('modalPreviewTotal').textContent = `Total: ${data.total || 0} registros.`;
                });
            });

            // --- GENERAR DESDE MODAL ---
            document.getElementById('confirmGenerateBtn').addEventListener('click', function() {
                if (!previewVariableId) return;
                fetch(`/admin/catalogos/variables/${previewVariableId}/generar`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Datos generados', text: data.message, confirmButtonColor: '#5f1b2d' });
                        bootstrap.Modal.getInstance(document.getElementById('previewModal'))?.hide();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Error al generar.', confirmButtonColor: '#af1731' });
                    }
                });
            });

            // --- GENERAR DIRECTO ---
            document.getElementById('generateBtn').addEventListener('click', function() {
                const varId = genSelect.value;
                if (!varId) return;
                Swal.fire({
                    title: '¿Generar datos?', text: 'Se calcularán y guardarán los datos históricos.',
                    icon: 'question', showCancelButton: true, confirmButtonColor: '#5f1b2d',
                    confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch(`/admin/catalogos/variables/${varId}/generar`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) Swal.fire({ icon: 'success', title: 'Datos generados', text: data.message, confirmButtonColor: '#5f1b2d' });
                            else Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Error.', confirmButtonColor: '#af1731' });
                        });
                    }
                });
            });

            // Enable/disable buttons based on selection
            genSelect.addEventListener('change', function() {
                document.getElementById('previewBtn').disabled = !this.value;
                document.getElementById('generateBtn').disabled = !this.value;
            });

            actualizarSelectGeneracion();
        });
    </script>
    @endpush
</x-admin-layout>
