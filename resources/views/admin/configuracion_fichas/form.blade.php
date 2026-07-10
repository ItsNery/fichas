<x-admin-layout>
    @section('title', $configuracion->exists ? 'Editar Configuración' : 'Nueva Configuración')

    <x-page-header title="{{ $configuracion->exists ? 'Editar Configuración' : 'Nueva Configuración' }}"
        subtitle="Personaliza el contenido y la visualización del indicador en la ficha municipal"
        icon="fa-solid fa-pen-to-square" />

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <div class="container py-4 pb-5">
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
                    <div class="card card-panel border-0 shadow-sm overflow-hidden">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                {{-- Sidebar de Tabs --}}
                                <div class="col-md-3 bg-light p-4 border-end">
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                                        <button class="nav-link active" id="tab-datos-tab" data-bs-toggle="pill"
                                            data-bs-target="#tab-datos" type="button" role="tab">
                                            <i class="fas fa-database me-2"></i>1. Datos
                                        </button>
                                        <button class="nav-link" id="tab-visual-tab" data-bs-toggle="pill"
                                            data-bs-target="#tab-visual" type="button" role="tab">
                                            <i class="fas fa-chart-pie me-2"></i>2. Visual
                                        </button>
                                        <button class="nav-link" id="tab-contenido-tab" data-bs-toggle="pill"
                                            data-bs-target="#tab-contenido" type="button" role="tab">
                                            <i class="fas fa-pen-nib me-2"></i>3. Editorial
                                        </button>
                                    </div>

                                    <div class="mt-5 p-3 rounded-4 bg-white border shadow-sm">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="activo"
                                                name="activo"
                                                {{ old('activo', $configuracion->activo ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold small" for="activo">ACTIVO</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="mostrar_comparativa"
                                                name="mostrar_comparativa"
                                                {{ old('mostrar_comparativa', $configuracion->mostrar_comparativa ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold small"
                                                for="mostrar_comparativa">BENCHMARK</label>
                                        </div>
                                        <div class="mt-3 pt-2 border-top" id="benchmark_mode_group"
                                            style="display: {{ old('mostrar_comparativa', $configuracion->mostrar_comparativa ?? false) ? 'block' : 'none' }};">
                                            <label for="benchmark_mode" class="form-label fw-bold small mb-1">Cálculo de
                                                Comparación</label>
                                            @php
                                                $currentMode = old(
                                                    'benchmark_mode',
                                                    $configuracion->ajustes_visuales['benchmark_mode'] ?? 'avg',
                                                );
                                            @endphp
                                            <select id="benchmark_mode" name="benchmark_mode"
                                                class="form-select form-select-sm">
                                                <option value="avg" {{ $currentMode == 'avg' ? 'selected' : '' }}>
                                                    Promediar municipios (Peer average)</option>
                                                <option value="sum" {{ $currentMode == 'sum' ? 'selected' : '' }}>
                                                    Sumar total regional/estatal (Share)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contenido de Tabs --}}
                                <div class="col-md-9 p-4 bg-white">
                                    <div class="tab-content" id="v-pills-tabContent">
                                        {{-- Tab 1: Datos --}}
                                        <div class="tab-pane fade show active" id="tab-datos" role="tabpanel">
                                            <h4 class="form-section-title">Origen de Datos</h4>

                                            <div class="mb-4">
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
                                            </div>

                                            <div class="mb-4">
                                                <label for="variables_ids" class="form-label">Variables (Filtro)</label>
                                                <select id="variables_ids" name="variables_ids[]" multiple
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
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-12">
                                                    <label for="orden" class="form-label">Prioridad (Orden)</label>
                                                    <input type="number" class="form-control" id="orden"
                                                        name="orden"
                                                        value="{{ old('orden', $configuracion->orden ?? 0) }}"
                                                        required>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Tab 2: Visualización --}}
                                        <div class="tab-pane fade" id="tab-visual" role="tabpanel">
                                            <h4 class="form-section-title">Estética Visual</h4>

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
                                                @endphp
                                                @foreach ($visualizaciones as $vis)
                                                    <div class="col-4">
                                                        <div class="vis-option {{ old('tipo_visualizacion', $configuracion->tipo_visualizacion ?? '') == $vis ? 'active' : '' }}"
                                                            data-value="{{ $vis }}">
                                                            <i class="{{ $icons[$vis] ?? 'fas fa-chart-area' }}"></i>
                                                            <span>{{ $vis == 'kpi' ? 'Indicador Clave' : ($vis == 'scatter' ? 'Dispersión (Cruce)' : ucfirst($vis)) }}</span>
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
                                                        Distribución (Grid)
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
                                                        Años Trend
                                                        <i class="fa-solid fa-circle-info text-muted ms-1 cursor-pointer"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="Define cuántos años de histórico se muestran en el minigráfico (Sparkline) de tendencia de la tarjeta. Evita saturar de puntos la visualización."></i>
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

                                            <div class="mb-0">
                                                <label for="ajustes_visuales" class="form-label">
                                                    Ajustes JSON (Avanzado)
                                                    <i class="fa-solid fa-circle-info text-muted ms-1 cursor-pointer"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="Configuraciones estéticas en formato JSON para personalizar la gráfica (colores de series, límites de ejes, leyendas adicionales, etc.)."></i>
                                                </label>
                                                <textarea class="form-control font-monospace" id="ajustes_visuales" name="ajustes_visuales" rows="4"
                                                    style="font-size: 0.8rem;" placeholder='{"colors": ["#861e34", "#c79b66"]}'>{{ old('ajustes_visuales', isset($configuracion->ajustes_visuales) ? json_encode($configuracion->ajustes_visuales, JSON_PRETTY_PRINT) : '') }}</textarea>
                                            </div>
                                        </div>

                                        {{-- Tab 3: Editorial --}}
                                        <div class="tab-pane fade" id="tab-contenido" role="tabpanel">
                                            <h4 class="form-section-title">Narrativa y Estilo</h4>

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
                                                    <label for="icono" class="form-label">Icono FA</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i id="icon-preview"
                                                                class="{{ $configuracion->icono ?? 'fas fa-info-circle' }} text-vino"></i></span>
                                                        <input type="text" class="form-control" id="icono"
                                                            name="icono"
                                                            value="{{ old('icono', $configuracion->icono ?? 'fas fa-info-circle') }}">
                                                    </div>
                                                </div>
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

                                    <div class="mt-5 border-top pt-4 text-end">
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
                            <h6 class="text-center mb-3 text-muted fw-bold small">VISTA PREVIA EN FICHA</h6>
                            <div class="mock-card shadow">
                                <div class="mock-header">
                                    <div class="mock-icon shadow-sm">
                                        <i id="preview-mock-icon"
                                            class="{{ $configuracion->icono ?? 'fas fa-info-circle' }}"></i>
                                    </div>
                                    <h3 class="mock-title" id="preview-mock-title">
                                        {{ $configuracion->titulo_reporte ?: $configuracion->indicador->nombre_amigable ?? 'Nuevo Indicador' }}
                                    </h3>
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
                if (indicadorId) {
                    fetchVariables(indicadorId, variablesSelect.getValue());
                }
            });
        });

        // Live Preview
        const inputTitle = document.getElementById('titulo_reporte');
        const inputIcon = document.getElementById('icono');
        const inputNarrative = document.getElementById('plantilla_narrativa');

        const mockTitle = document.getElementById('preview-mock-title');
        const mockIcon = document.getElementById('preview-mock-icon');
        const mockIconInput = document.getElementById('icon-preview');
        const mockNarrative = document.getElementById('preview-mock-narrative');

        function updatePreview() {
            let t = inputTitle.value.trim();
            if (!t && indicadorSelect.getValue()) {
                t = indicadorSelect.options[indicadorSelect.getValue()].dataset.nombre;
            }
            mockTitle.innerText = t || 'Nuevo Indicador';

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
        inputIcon.addEventListener('input', updatePreview);
        inputNarrative.addEventListener('input', updatePreview);
        indicadorSelect.on('change', updatePreview);

        let oldVariables = @json(old(
                'variables_ids',
                isset($configuracion) && $configuracion->variables ? $configuracion->variables->pluck('id')->toArray() : []));

        let currentAniosHistorialValue = @json(old('anios_historial', $configuracion->anios_historial ?? 5));

        function populateAniosHistorial(maxYears, selectedVal) {
            const select = document.getElementById('anios_historial');
            if (!select) return;
            
            const previousVal = select.value || selectedVal;
            select.innerHTML = '';
            
            let limit = maxYears > 0 ? maxYears : 10;
            if (previousVal && parseInt(previousVal) > limit) {
                limit = parseInt(previousVal);
            }

            for (let i = 1; i <= limit; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i === 1 ? '1 año' : `${i} años`;
                if (i == previousVal) {
                    option.selected = true;
                }
                select.appendChild(option);
            }
        }

        function fetchAniosDisponibles(indicadorId, variablesIds = []) {
            if (!indicadorId) {
                populateAniosHistorial(5, currentAniosHistorialValue);
                return;
            }

            if (typeof variablesIds === 'string') {
                variablesIds = variablesIds ? [variablesIds] : [];
            }

            let url = `/admin/configuracion-fichas/api/anios-disponibles?indicador_id=${indicadorId}`;
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
                        const maxYears = data.anios_disponibles;
                        const selectEl = document.getElementById('anios_historial');
                        const activeVal = selectEl ? selectEl.value : currentAniosHistorialValue;
                        populateAniosHistorial(maxYears, activeVal);
                    }
                })
                .catch(error => {
                    console.error('Error fetching available years:', error);
                    populateAniosHistorial(5, currentAniosHistorialValue);
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
                ? `/admin/configuracion-fichas/api/todas-las-variables` 
                : `/admin/configuracion-fichas/api/variables-por-indicador/${indicadorId}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    variablesSelect.clearOptions();
                    window.indicadorVariablesData = data;
                    data.forEach(item => variablesSelect.addOption({
                        value: item.id,
                        text: item.text
                    }));
                    if (preSelectedIds && preSelectedIds.length > 0) {
                        variablesSelect.setValue(preSelectedIds);
                    }
                    updateDynamicTags();
                    updateAniosTrend();
                });
        }

        // Variables AJAX al cambiar indicador
        document.getElementById('indicador_id').addEventListener('change', function(e) {
            fetchVariables(e.target.value, []);
        });

        // Si ya hay un indicador seleccionado al cargar (por old() en create, o al editar)
        if (indicadorSelect.getValue()) {
            fetchVariables(indicadorSelect.getValue(), oldVariables);
        } else {
            updateAniosTrend();
        }

        variablesSelect.on('change', function() {
            updateDynamicTags();
            updateAniosTrend();
        });

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
