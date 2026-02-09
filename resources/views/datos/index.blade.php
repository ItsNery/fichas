<x-admin-layout>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
    @section('title', 'Gestión de Datos Históricos')

    {{-- Componente de Encabezado --}}
    <x-page-header
        title="Gestión de Datos Históricos"
        icon="fa-solid fa-database" />

    <div class="container py-4">

        <div class="card-panel">
            <div class="card-body p-4">

                {{-- BARRA DE FILTROS --}}
                <div class="bg-light rounded-3 p-3 mb-4 border">
                    <form method="GET" action="{{ route('admin.datos.index') }}" class="row g-3 align-items-center">

                        <div class="col-12 mb-2">
                            <h6 class="text-uppercase text-muted small fw-bold ls-1 mb-0">
                                <i class="fa-solid fa-filter me-1 text-dorado"></i> Filtros de Búsqueda
                            </h6>
                        </div>

                        {{-- Filtro Municipio (Con Tom Select) --}}
                        <div class="col-md-5">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </span>
                                {{-- OJO: Quitamos la clase form-select de Bootstrap para que Tom Select tome control total sin conflictos CSS --}}
                                <select id="select-municipio" name="municipio_id" placeholder="Buscar municipio..." autocomplete="off">
                                    {{-- Esta opción vacía es CRUCIAL para el "Todos" --}}
                                    <option value="">Todos los municipios</option>
                                    @foreach ($municipios as $municipio)
                                    <option value="{{ $municipio->id }}" {{ request('municipio_id') == $municipio->id ? 'selected' : '' }}>
                                        {{ $municipio->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Filtro Variable (Con Tom Select) --}}
                        <div class="col-md-5">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    <i class="fa-solid fa-tags"></i>
                                </span>
                                <select id="select-variable" name="variable_id" placeholder="Buscar variable..." autocomplete="off">
                                    <option value="">Todas las variables</option>
                                    @foreach ($variables as $variable)
                                    <option value="{{ $variable->id }}" {{ request('variable_id') == $variable->id ? 'selected' : '' }}>
                                        {{ $variable->nombre_amigable }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-custom-primary w-100 shadow-sm" style="min-height: calc(1.5em + 1rem + 2px);">
                                <i class="fa-solid fa-magnifying-glass me-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TABLA DE DATOS --}}
                <div class="table-responsive">
                    <table class="table table-custom w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Municipio</th>
                                <th>Variable</th>
                                <th class="text-center">Año</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($datos as $dato)
                            <tr id="dato-row-{{ $dato->id }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-location-dot small"></i>
                                        </div>
                                        <span class="fw-medium text-secondary">{{ $dato->municipio->nombre }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-vino fw-bold">{{ $dato->variable->nombre_amigable }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border fw-normal px-3">{{ $dato->anio }}</span>
                                </td>
                                <td class="valor-col text-end fw-bold text-dark fs-6">
                                    {{ number_format($dato->valor, 2) }}
                                </td>
                                <td class="text-center">
                                    {{-- Botón Cuadrado Dorado (Estilo Edit) --}}
                                    <button type="button" class="btn-icon-square edit edit-btn"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-update-url="{{ route('admin.datos.update', $dato) }}"
                                        data-info-text="{{ $dato->variable->nombre_amigable }} de {{ $dato->municipio->nombre }} ({{ $dato->anio }})"
                                        data-current-value="{{ rtrim(rtrim(number_format($dato->valor, 8, '.', ''), '0'), '.') }}">
                                        <i class="fa-regular fa-pen-to-square" data-bs-toggle="tooltip" title="Editar valor"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="opacity-50 mb-3">
                                        <i class="fa-solid fa-folder-open fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted small fw-bold">No se encontraron registros</h5>
                                    <p class="text-muted small mb-0">Intenta ajustar los filtros de búsqueda.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginación --}}
                <div class="mt-4 d-flex justify-content-center">
                    {{ $datos->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE EDICIÓN (Estilizado) --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-vino" id="editModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2 text-dorado"></i>Editar Dato Histórico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="alert alert-light border mb-4 d-flex">
                            <i class="fa-solid fa-circle-info text-dorado mt-1 me-2"></i>
                            <span id="modal-info-text" class="text-muted small"></span>
                        </div>
                        <div class="mb-2">
                            <label for="modal-valor" class="form-label fw-bold text-secondary">Nuevo Valor</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light text-muted">#</span>
                                <input type="text"
                                    inputmode="decimal"
                                    pattern="[0-9]*[.,]?[0-9]*"
                                    class="form-control fw-bold text-vino border-start-0 ps-0"
                                    id="modal-valor"
                                    name="valor"
                                    required>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-custom-primary px-4">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPTS (Tu lógica AJAX original + Tooltips) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Tooltips de Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Referencias del Modal
            const editModalEl = document.getElementById('editModal');
            const editModal = new bootstrap.Modal(editModalEl);
            const editForm = document.getElementById('editForm');
            const modalInfoText = document.getElementById('modal-info-text');
            const modalValorInput = document.getElementById('modal-valor');

            // 1. Llenar el modal
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    editForm.action = this.dataset.updateUrl;
                    modalInfoText.textContent = this.dataset.infoText;
                    modalValorInput.value = this.dataset.currentValue;
                    modalValorInput.classList.remove('is-invalid');
                });
            });

            // 2. AJAX Submit
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                let valorLimpio = modalValorInput.value.replace(',', '.');
                fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'PUT',
                            valor: valorLimpio
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => Promise.reject(err));
                        }
                        return response.json();
                    })
                    .then(data => {
                        editModal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: data.success,
                            timer: 1500,
                            showConfirmButton: false,
                            confirmButtonColor: '#5f1b2d'
                        });

                        // Actualizar tabla dinámicamente
                        const rowId = this.action.split('/').pop();
                        const valorCell = document.querySelector(`#dato-row-${rowId} .valor-col`);
                        if (valorCell) {
                            valorCell.textContent = parseFloat(data.newValue).toLocaleString('es-MX', {
                                minimumFractionDigits: 2
                            });
                        }
                    })
                    .catch(errorData => {
                        if (errorData.errors && errorData.errors.valor) {
                            modalValorInput.classList.add('is-invalid');
                            modalValorInput.nextElementSibling.textContent = errorData.errors.valor[0];
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo actualizar el dato.',
                                confirmButtonColor: '#af1731'
                            });
                        }
                    });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {

            // Configuración Común para Tom Select
            const tomSelectConfig = {
                create: false, // No permitir crear nuevos items
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                allowEmptyOption: true, // ¡IMPORTANTE! Permite seleccionar el value="" (Todos)
                plugins: ['clear_button'], // Opcional: añade una 'x' para limpiar rápido
            };

            // Inicializar Municipio
            if (document.getElementById('select-municipio')) {
                new TomSelect('#select-municipio', tomSelectConfig);
            }

            // Inicializar Variable
            if (document.getElementById('select-variable')) {
                new TomSelect('#select-variable', tomSelectConfig);
            }
        });
    </script>

</x-admin-layout>