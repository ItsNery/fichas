<x-admin-layout>
    @section('title', 'Gestión de Municipios')

    {{-- 1. HEADER --}}
    <x-page-header
        title="Gestión de Municipios"
        subtitle="Administración de entidades y asignación de instrumentos de planeación"
        icon="fa-solid fa-map-location-dot" />

    <div class="container py-4">

        {{-- ALERTA DE ÉXITO (SweetAlert se encarga, pero mantenemos el include por si acaso) --}}
        @include('partials.alerts')

        {{-- 2. TARJETA PRINCIPAL --}}
        <div class="card-panel">
            <div class="card-body p-4">

                {{-- TABLA --}}
                <div class="table-responsive">
                    <table class="table table-custom w-100 align-middle" id="tabla-municipios">
                        <thead>
                            <tr>
                                <th class="ps-4 text-center" style="width: 80px;">ID</th>
                                <th>Clave GEO</th>
                                <th>Nombre del Municipio</th>
                                <th class="text-center">Asignación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($municipios as $municipio)
                            <tr>
                                <td class="ps-4 text-center">
                                    <span class="text-muted fw-bold">#{{ $municipio->id }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace px-3 py-2">
                                        {{ $municipio->cvegeo }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-location-dot text-dorado me-3 opacity-50"></i>
                                        <span class="fw-bold text-vino">{{ $municipio->nombre }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        {{-- Botón Editar Info --}}
                                        <a href="{{ route('admin.municipios.edit', $municipio) }}" class="btn-icon-square">
                                            <i class="fas fa-edit fs-5" data-bs-toggle="tooltip" title="Editar Información"></i>
                                        </a>

                                        {{-- Botón Asignar Instrumentos --}}
                                        <button type="button" class="btn-icon-square edit"
                                            data-bs-toggle="modal" data-bs-target="#asignarModal"
                                            data-municipio-id="{{ $municipio->id }}"
                                            data-municipio-nombre="{{ $municipio->nombre }}"
                                            style="width: 42px; height: 42px;"> <i class="fas fa-file-signature fs-5" data-bs-toggle="tooltip" title="Asignar Instrumentos"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    {{-- 3. MODAL UNIFICADO --}}
    <div class="modal fade" id="asignarModal" tabindex="-1" aria-labelledby="asignarModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-vino" id="asignarModalTitle">
                        <i class="fas fa-file-contract me-2 text-dorado"></i>Asignar Instrumentos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="asignarForm" method="POST" autocomplete="off">
                    @csrf
                    <div class="modal-body p-4">
                        {{-- Info del Municipio --}}
                        <div class="alert alert-light border d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-white border d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-map-pin text-vino"></i>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Municipio Seleccionado</small>
                                <h5 class="mb-0 fw-bold text-dark" id="municipioNombre"></h5>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="anio_instrumentos" class="form-label fw-bold text-secondary small">Año de Asignación</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-calendar text-muted"></i></span>
                                <input type="number" name="anio_instrumentos" id="anio_instrumentos" class="form-control fw-bold text-vino border-start-0 ps-0"
                                    placeholder="Ej. {{ date('Y') }}" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="instrumentos_select" class="form-label fw-bold text-secondary small">Instrumentos Disponibles</label>
                            <div class="input-group shadow-sm">
                                {{-- Quitamos form-control para que Tom Select mande --}}
                                <select name="instrumentos[]" id="instrumentos_select" multiple="multiple" placeholder="Buscar y seleccionar...">
                                    {{-- JS llena esto --}}
                                </select>
                            </div>
                            <div class="form-text small mt-2">
                                <i class="fa-solid fa-circle-info me-1 text-dorado"></i>
                                Puedes seleccionar múltiples instrumentos de la lista.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-custom-primary px-4">Guardar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- Estilos y Scripts de Tom Select (Si no están en el layout) --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // 2. DataTables (Configuración limpia)
            const usersTable = new DataTable('#tabla-municipios', {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.3.2/i18n/es-MX.json'
                },
                pagingType: 'simple_numbers',
                autoWidth: false,
                // Buscador arriba limpio
                dom: '<"d-flex justify-content-between mb-3"f>t<"d-flex justify-content-center mt-3"p>',
            });

            // 3. Tom Select Inicialización
            const tomSelect = new TomSelect('#instrumentos_select', {
                plugins: ['remove_button', 'clear_button'],
                maxOptions: null,
                render: {
                    // Personalizar cómo se ven los items seleccionados (opcional)
                    item: function(data, escape) {
                        return '<div class="item bg-custom-primary text-white border-0">' + escape(data.text) + '</div>';
                    }
                }
            });

            // 4. Lógica del Modal
            const asignarModal = document.getElementById('asignarModal');
            const anioInput = document.getElementById('anio_instrumentos');
            const municipioNombreEl = document.getElementById('municipioNombre');
            const form = document.getElementById('asignarForm');

            asignarModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const municipioId = button.getAttribute('data-municipio-id');
                const municipioNombre = button.getAttribute('data-municipio-nombre');

                // Set UI info
                municipioNombreEl.textContent = municipioNombre;
                form.action = `{{ url('admin/municipios') }}/${municipioId}/instrumentos`;

                // Resetear inputs mientras carga
                tomSelect.clear();
                tomSelect.clearOptions();
                tomSelect.disable(); // Deshabilitar visualmente mientras carga
                anioInput.value = '';

                // Fetch Data
                fetch(`{{ url('admin/municipios') }}/${municipioId}/instrumentos`)
                    .then(response => response.json())
                    .then(data => {
                        // Habilitar de nuevo
                        tomSelect.enable();

                        // Llenar opciones
                        data.catalogo.forEach(instrumento => {
                            tomSelect.addOption({
                                value: instrumento.id,
                                text: instrumento.nombre
                            });
                        });

                        // Sincronizar opciones (refresh)
                        tomSelect.sync();

                        // Seleccionar los asignados
                        const asignadosIds = Object.keys(data.asignados);
                        if (asignadosIds.length > 0) {
                            tomSelect.setValue(asignadosIds);
                            // Tomar el año del primer instrumento asignado (asumiendo mismo año para el lote)
                            anioInput.value = data.asignados[asignadosIds[0]];
                        } else {
                            // Si no hay asignados, sugerir año actual
                            anioInput.value = new Date().getFullYear();
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        tomSelect.enable();
                        Swal.fire('Error', 'No se pudo cargar la información del municipio.', 'error');
                    });
            });

            // Feedback visual al guardar
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
            });
        });
    </script>
    @endpush
</x-admin-layout>