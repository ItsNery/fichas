<x-admin-layout>
    @section('title', 'Centro de Importaciones')

    {{-- Componente de Encabezado Reutilizable --}}
    <x-page-header
        title="Centro de Importación de Datos"
        subtitle="Carga masiva de catálogos y actualización de registros históricos"
        icon="fa-solid fa-cloud-arrow-up" />

    {{-- Scripts de alertas (SweetAlert) --}}
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
    <script>
        document.addEventListener('DOMContentLoaded', () => Swal.fire({
            icon: 'error',
            title: 'Error de Validación',
            text: '{{ $errors->first() }}',
            confirmButtonColor: '#af1731'
        }));
    </script>
    @endif

    <div class="container py-4">
        <div class="card-panel">
            <div class="card-body p-4">
                <ul class="nav nav-tabs nav-tabs-clean mb-4" id="importTab" role="tablist">
                    @php
                    $tabs = [
                    ['id' => 'dimensiones', 'label' => 'Dimensiones'],
                    ['id' => 'tematicas', 'label' => 'Temáticas'],
                    ['id' => 'indicadores', 'label' => 'Indicadores'],
                    ['id' => 'variables', 'label' => 'Variables'],
                    ['id' => 'datoshistoricos', 'label' => 'Históricos'],
                    ['id' => 'datoscomplejos', 'label' => 'Complejos'],
                    ['id' => 'instrumentos', 'label' => 'Cat. Inst.'],
                    ['id' => 'asignaciones', 'label' => 'Asignaciones'],
                    ];
                    @endphp

                    @foreach ($tabs as $index => $tab)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $index == 0 ? 'active' : '' }}"
                            id="{{ $tab['id'] }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#{{ $tab['id'] }}"
                            type="button" role="tab">
                            {{ $tab['label'] }}
                        </button>
                    </li>
                    @endforeach
                </ul>

                <div class="tab-content" id="importTabContent">

                    <div class="tab-pane fade show active" id="dimensiones" role="tabpanel">
                        <x-import-form
                            action="{{ route('admin.import.dimensiones') }}"
                            template="{{ route('admin.import.plantilla', ['tipo' => 'dimensiones']) }}"
                            btnText="Cargar Dimensiones">
                            <x-slot name="instructions">
                                <code>nombre</code>, <code>color</code>, <code>nombre_tecnico</code>.
                            </x-slot>
                        </x-import-form>
                    </div>

                    {{-- 2. Temáticas --}}
                    <div class="tab-pane fade" id="tematicas" role="tabpanel">
                        <x-import-form
                            action="{{ route('admin.import.tematicas') }}"
                            template="{{ route('admin.import.plantilla', ['tipo' => 'tematicas']) }}"
                            btnText="Cargar Temáticas">
                            <x-slot name="instructions">
                                <code>nombre</code>, <code>nombre_tecnico</code>, <code>dimension_tecnico</code>.
                            </x-slot>
                        </x-import-form>
                    </div>

                    {{-- 3. Indicadores --}}
                    <div class="tab-pane fade" id="indicadores" role="tabpanel">
                        <x-import-form
                            action="{{ route('admin.import.indicadores') }}"
                            template="{{ route('admin.import.plantilla', ['tipo' => 'indicadores']) }}"
                            btnText="Cargar Indicadores">
                            <x-slot name="instructions">
                                <code>nombre_amigable</code>, <code>nombre_tecnico</code>, <code>descripción</code>,
                                <code>metodo_calculo</code>, <code>fuente</code>, <code>tipo_grafico_default</code>,
                                <code>es_complejo</code>, <code>solo_resumen</code>, <code>tipo_dato</code>,
                                <code>tematica_tecnico</code>, <code>priorizar_total</code>
                                <br>
                                Para <code>es_complejo</code>, <code>priorizar_total</code> y <code>solo_resumen</code>:
                                <code>1</code> significa verdadero y <code>0</code> falso.
                            </x-slot>
                        </x-import-form>
                    </div>

                    {{-- 4. Variables --}}
                    <div class="tab-pane fade" id="variables" role="tabpanel">
                        <x-import-form
                            action="{{ route('admin.import.variables') }}"
                            template="{{ route('admin.import.plantilla', ['tipo' => 'variables']) }}"
                            btnText="Cargar Variables">
                            <x-slot name="instructions">
                                <code>nombre_tecnico</code>, <code>nombre_amigable</code>, <code>unidad_medida</code>,
                                <code>es_kpi</code>, <code>mapeo_valores</code>, <code>indicador_tecnico</code>, <code>es_destacada</code>.
                                <br>
                                Para <code>es_kpi</code> y <code>es_destacada</code>: <code>1</code> significa verdadero y <code>0</code> falso.
                                <br>
                                Para <code>mapeo_valores</code>: tiene que estar en formato <code>JSON</code>.
                            </x-slot>
                        </x-import-form>
                    </div>

                    {{-- 5. Datos Históricos (CASO ESPECIAL: Doble Paso) --}}
                    <div class="tab-pane fade" id="datoshistoricos" role="tabpanel">
                        <div class="alert alert-light border-start border-4 border-warning mb-4 d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <i class="fa-solid fa-shield-halved text-warning me-2"></i>
                                <strong>Proceso gobernado:</strong> el archivo se valida y se envía a revisión. Los datos solo serán públicos después de su aprobación.
                            </div>
                            <a href="{{ route('admin.lotes-datos.index') }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                                <i class="fa-solid fa-box-archive me-1"></i>Ver lotes
                            </a>
                        </div>

                        <form id="validate-form" action="{{ route('admin.import.datos.validate') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="file-drop-zone">
                                <span class="file-info">
                                    <i class="fa-solid fa-file-csv fa-3x d-block mb-3 text-muted opacity-50"></i>
                                    <span class="fw-bold text-vino">Arrastra tu archivo aquí</span>
                                    <br><small class="text-muted">o haz clic para examinar (.xlsx, .csv)</small>
                                </span>
                                <input type="file" name="archivo_datos" class="file-input" required accept=".xlsx, .xls, .csv" onchange="updateFileInfo(this)">
                            </div>

                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <a href="{{ route('admin.import.plantilla', ['tipo' => 'datos-historicos']) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-download me-2"></i>Plantilla
                                </a>
                                <button id="validate-btn" type="submit" class="btn btn-custom-primary">
                                    <i class="fas fa-check-double me-2"></i>Validar Archivo
                                </button>
                            </div>
                        </form>

                        {{-- Área de Resultados JS --}}
                        <div id="results-area" class="mt-4" style="display: none;">
                            <div id="validation-message" class="alert shadow-sm"></div>

                            <form id="import-form" action="{{ route('admin.import.datos.perform') }}" method="POST" class="text-center mt-3" style="display: none;">
                                @csrf
                                <input type="hidden" name="lote_id" id="lote_id">
                                <button type="submit" class="btn btn-custom-verde btn-lg shadow">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar a Revisión
                                </button>
                            </form>
                        </div>

                        <div class="import-instructions">
                            <strong><i class="fas fa-key me-1"></i> Columnas Clave:</strong>
                            <code>municipio_cvegeo</code>, <code>anio</code>, <code>valor</code>, <code>variable_tecnico</code>.
                        </div>
                    </div>

                    {{-- 6. Datos Complejos (Cultivos) --}}
                    <div class="tab-pane fade" id="datoscomplejos" role="tabpanel">
                        <form action="{{ route('admin.import.datos_complejos') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-bold text-vino">Indicador Destino:</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fa-solid fa-wheat-awn text-muted"></i></span>
                                    <select name="indicador_id" class="form-select" required>
                                        <option value="">-- Selecciona el indicador complejo --</option>
                                        @foreach (App\Models\Indicador::where('es_complejo', true)->get() as $indicador)
                                        <option value="{{ $indicador->id }}">{{ $indicador->nombre_amigable }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="file-drop-zone">
                                <span class="file-info">
                                    <i class="fa-solid fa-table-cells fa-3x d-block mb-3 text-muted opacity-50"></i>
                                    <span class="fw-bold text-vino">Archivo de complejos</span>
                                    <br><small class="text-muted">Arrastra o selecciona (.xlsx)</small>
                                </span>
                                <input type="file" name="archivo" class="file-input" required accept=".xlsx, .xls, .csv" onchange="updateFileInfo(this)">
                            </div>

                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <a href="{{ route('admin.import.plantilla', ['tipo' => 'datos-complejos']) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-download me-2"></i>Plantilla
                                </a>
                                <button type="submit" class="btn btn-custom-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Validar y enviar a revisión
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- 7. Instrumentos --}}
                    <div class="tab-pane fade" id="instrumentos" role="tabpanel">
                        <x-import-form
                            action="{{ route('admin.import.instrumentos') }}"
                            template="{{ route('admin.import.plantilla', ['tipo' => 'catalogo-instrumentos']) }}"
                            btnText="Cargar Catálogo">
                            <x-slot name="instructions"><code>nombre</code>.</x-slot>
                        </x-import-form>
                    </div>

                    {{-- 8. Asignaciones --}}
                    <div class="tab-pane fade" id="asignaciones" role="tabpanel">
                        <x-import-form
                            action="{{ route('admin.import.instrumentos_asignacion') }}"
                            template="{{ route('admin.import.plantilla', ['tipo' => 'asignacion-instrumentos']) }}"
                            btnText="Cargar Asignaciones">
                            <x-slot name="instructions"><code>municipio_cvegeo</code>, <code>instrumento_nombre</code>, <code>anio</code>.</x-slot>
                        </x-import-form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function updateFileInfo(input) {
            // Buscamos el span .file-info dentro del mismo contenedor padre
            const zone = input.closest('.file-drop-zone');
            const fileInfo = zone.querySelector('.file-info');

            if (input.files && input.files.length > 0) {
                fileInfo.innerHTML = `
                    <i class="fas fa-check-circle fa-3x d-block mb-3 text-success"></i>
                    <span class="fw-bold text-dark">${input.files[0].name}</span>
                `;
                zone.style.borderColor = 'var(--color6)'; // Verde al confirmar
                zone.style.backgroundColor = '#f0fff4';
            } else {
                // Restaurar estado original si se cancela
                fileInfo.innerHTML = `
                    <i class="fas fa-cloud-upload-alt fa-3x d-block mb-3 text-muted opacity-50"></i>
                    <span class="fw-bold text-vino">Arrastra el archivo aquí</span>
                    <br><small class="text-muted">o haz clic para seleccionar</small>
                `;
                zone.style.borderColor = '';
                zone.style.backgroundColor = '';
            }
        }
        // Script del importador inteligente
        document.addEventListener('DOMContentLoaded', function() {
            const validateForm = document.getElementById(
                'validate-form'); // ...que coincide con el ID que buscamos aquí
            const validateBtn = document.getElementById('validate-btn');
            const resultsArea = document.getElementById('results-area');
            const validationMessage = document.getElementById('validation-message');
            const importForm = document.getElementById('import-form');
            const loteIdInput = document.getElementById('lote_id');
            const originalBtnText = validateBtn.innerHTML;

            if (validateForm) {
                validateForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // <-- LA LÍNEA MÁS IMPORTANTE
                    console.log('--- DEBUG: PASO 1: Formulario de validación enviado. ---');

                    validateBtn.disabled = true;
                    validateBtn.innerHTML =
                        `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Validando...`;
                    resultsArea.style.display = 'none';
                    importForm.style.display = 'none';

                    const formData = new FormData(this);
                    console.log('--- DEBUG: PASO 2: Realizando llamada fetch a:', this.action, '---');
                    fetch(this.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            }
                        })
                        .then(response => {
                            const contentType = response.headers.get('content-type');
                            console.log('--- DEBUG: PASO 3: Respuesta recibida del servidor. ---', {
                                status: response.status,
                                contentType: contentType
                            });
                            if (contentType && contentType.includes('spreadsheetml.sheet')) {
                                validationMessage.className = 'alert alert-danger';
                                validationMessage.innerHTML =
                                    '<strong>Se encontraron errores.</strong> Revisa el reporte descargado para ver los detalles. Corrige tu archivo y vuelve a intentarlo.';
                                resultsArea.style.display = 'block';
                                return response.blob().then(blob => ({
                                    blob: blob,
                                    isFile: true
                                }));
                            }
                            return response.json().then(json => ({
                                json: json,
                                isFile: false,
                                status: response.status
                            }));
                        })
                        .then(data => {
                            console.log('--- DEBUG: PASO 4: Procesando datos de la respuesta. ---',
                                data);
                            if (data.isFile) {
                                console.log(
                                    "-> La respuesta es un archivo (reporte de errores). Inicia la descarga."
                                );
                                const url = window.URL.createObjectURL(data.blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'reporte_de_errores.xlsx';
                                document.body.appendChild(a);
                                a.click();
                                a.remove();
                                window.URL.revokeObjectURL(url);
                            } else if (data.status === 422 && data.json.errors) {
                                console.log("-> La respuesta es un JSON con errores de validación.",
                                    data.json.errors);
                                let errorHtml =
                                    '<strong>Se encontraron los siguientes errores en el archivo:</strong><ul>';

                                data.json.errors.forEach(error => {
                                    errorHtml += `<li>Fila ${error.fila}: ${error.error}</li>`;
                                });

                                errorHtml += '</ul>';
                                validationMessage.className = 'alert alert-danger';
                                validationMessage.innerHTML = errorHtml;
                                resultsArea.style.display = 'block';
                            } else if (data.json.success) {
                                console.log("-> ¡Validación exitosa en el backend!");
                                console.log("-> Lote creado:", data.json.lote_id);
                                validationMessage.className = 'alert alert-success';
                                validationMessage.innerHTML = `${data.json.message} <a href="${data.json.detalle_url}" class="alert-link">Ver detalle</a>`;
                                loteIdInput.value = data.json.lote_id;
                                resultsArea.style.display = 'block';
                                importForm.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('--- DEBUG: OCURRIÓ UN ERROR EN LA LLAMADA FETCH ---', error);
                            console.error('Error:', error);
                            validationMessage.className = 'alert alert-danger';
                            validationMessage.innerText =
                                'Ocurrió un error inesperado. Revisa la consola para más detalles.';
                            resultsArea.style.display = 'block';
                        })
                        .finally(() => {
                            console.log('--- DEBUG: PASO 5: Proceso de validación finalizado. ---');
                            validateBtn.disabled = false;
                            validateBtn.innerHTML = originalBtnText;
                        });
                });
            }
        });
    </script>
</x-admin-layout>
