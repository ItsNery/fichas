<x-admin-layout>
    @section('title', 'Gestión de Instrumentos')
    
    {{-- 1. HEADER --}}
    <x-page-header 
        title="Gestión de Instrumentos" 
        subtitle="Catálogo de instrumentos de planeación disponibles para asignación" 
        icon="fa-solid fa-file-contract" 
    />

    {{-- SCRIPTS DE ALERTAS (Reemplaza al @include) --}}
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => Swal.fire({
                icon: 'success', title: '¡Éxito!', text: '{{ session('success') }}', 
                confirmButtonColor: '#5f1b2d', timer: 2000, showConfirmButton: false
            }));
        </script>
    @endif
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => Swal.fire({
                icon: 'error', title: 'Error', text: '{{ $errors->first() }}', 
                confirmButtonColor: '#af1731'
            }));
        </script>
    @endif

    <div class="container py-4">
        
        {{-- 2. TARJETA PRINCIPAL --}}
        <div class="card-panel">
            <div class="card-body p-4">
                
                {{-- Barra Superior --}}
                <div class="d-flex justify-content-end mb-4">
                    @can('instrumentos.crear')
                    <button class="btn btn-custom-primary shadow-sm px-4" 
                        data-bs-toggle="modal" data-bs-target="#instrumentoModal" type="button">
                        <i class="fa-solid fa-plus me-2"></i>Añadir Instrumento
                    </button>
                    @endcan
                </div>

                {{-- 3. TABLA --}}
                <div class="table-responsive">
                    <table class="table table-custom w-100 align-middle" id="tabla-instrumentos">
                        <thead>
                            <tr>
                                <th class="ps-4">Nombre del Instrumento</th>
                                <th>Descripción</th>
                                @if(auth()->user()->can('instrumentos.editar') || auth()->user()->can('instrumentos.eliminar'))
                                <th class="text-center pe-4" style="width: 120px;">Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($instrumentos as $instrumento)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <span class="fw-bold text-dark">{{ $instrumento->nombre }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            {{ Str::limit($instrumento->descripcion, 80) ?? 'Sin descripción registrada.' }}
                                        </span>
                                    </td>
                                    @if(auth()->user()->can('instrumentos.editar') || auth()->user()->can('instrumentos.eliminar'))
                                    <td class="text-center pe-4">
                                        <div class="d-flex justify-content-center gap-2">
                                            {{-- Editar --}}
                                            @can('instrumentos.editar')
                                            <button type="button" class="btn-icon-square edit"
                                                data-bs-toggle="modal" data-bs-target="#instrumentoModal"
                                                data-id="{{ $instrumento->id }}" 
                                                data-nombre="{{ $instrumento->nombre }}"
                                                data-descripcion="{{ $instrumento->descripcion }}">
                                                <i class="fa-regular fa-pen-to-square" data-bs-toggle="tooltip" title="Editar"></i>
                                            </button>
                                            @endcan

                                            {{-- Eliminar --}}
                                            @can('instrumentos.eliminar')
                                            <form action="{{ route('admin.instrumentos.destroy', $instrumento) }}" method="POST" class="delete-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon-square danger">
                                                    <i class="fa-solid fa-trash" data-bs-toggle="tooltip" title="Eliminar"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->can('instrumentos.editar') || auth()->user()->can('instrumentos.eliminar') ? 3 : 2 }}" class="text-center py-5">
                                        <div class="mb-3 opacity-25">
                                            <i class="fa-solid fa-file-circle-xmark fa-4x text-muted"></i>
                                        </div>
                                        <h5 class="text-muted fw-bold small">No hay instrumentos registrados</h5>
                                        <p class="text-muted small mb-0">Comienza añadiendo uno nuevo.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. MODAL UNIFICADO --}}
    <div class="modal fade" id="instrumentoModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-vino" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="instrumentoForm" method="POST" autocomplete="off">
                    @csrf
                    <div id="method-container"></div> {{-- Aquí inyectamos @method('PUT') con JS --}}

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="instrumentoNombre" class="form-label fw-bold text-secondary small">Nombre del Instrumento</label>
                            <input type="text" class="form-control text-vino fw-bold" id="instrumentoNombre" name="nombre" required placeholder="Ej: Plan Municipal de Desarrollo">
                        </div>
                        <div class="mb-0">
                            <label for="instrumentoDescripcion" class="form-label fw-bold text-secondary small">Descripción</label>
                            <textarea class="form-control" id="instrumentoDescripcion" name="descripcion" rows="4" placeholder="Breve descripción del objetivo de este instrumento..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-custom-primary px-4">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPTS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // 2. DataTables (Configuración limpia)
            const table = new DataTable('#tabla-instrumentos', {
                language: { url: 'https://cdn.datatables.net/plug-ins/2.3.2/i18n/es-MX.json' },
                pagingType: 'simple_numbers',
                autoWidth: false,
                dom: '<"d-flex justify-content-between mb-3"f>t<"d-flex justify-content-center mt-3"p>',
            });

            // 3. Lógica de Eliminación (SweetAlert)
            // Usamos delegación de eventos en el documento para que funcione incluso si la tabla se pagina
            document.body.addEventListener('submit', function(e) {
                if (e.target.classList.contains('delete-form')) {
                    e.preventDefault();
                    const form = e.target;
                    
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Esta acción eliminará el instrumento del catálogo.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#af1731', // Rojo institucional
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }
            });

            // 4. Lógica del Modal (Crear / Editar)
            const instrumentoModal = document.getElementById('instrumentoModal');
            if (instrumentoModal) {
                const modalTitle = document.getElementById('modalTitle');
                const form = document.getElementById('instrumentoForm');
                const inputNombre = document.getElementById('instrumentoNombre');
                const inputDescripcion = document.getElementById('instrumentoDescripcion');
                const methodContainer = document.getElementById('method-container');

                instrumentoModal.addEventListener('show.bs.modal', event => {
                    const button = event.relatedTarget;
                    const instrumentoId = button.getAttribute('data-id');

                    // Limpiar errores previos si los hubiera (opcional)
                    // ...

                    if (instrumentoId) {
                        // --- MODO EDITAR ---
                        modalTitle.innerHTML = '<i class="fa-solid fa-pen-to-square me-2 text-dorado"></i>Editar Instrumento';
                        
                        // URL Update
                        let updateUrl = '{{ route('admin.instrumentos.update', ':id') }}';
                        form.action = updateUrl.replace(':id', instrumentoId);
                        
                        // Método PUT
                        methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                        
                        // Llenar campos
                        inputNombre.value = button.getAttribute('data-nombre');
                        inputDescripcion.value = button.getAttribute('data-descripcion');
                    } else {
                        // --- MODO CREAR ---
                        modalTitle.innerHTML = '<i class="fa-solid fa-plus me-2 text-dorado"></i>Nuevo Instrumento';
                        form.action = '{{ route('admin.instrumentos.store') }}';
                        methodContainer.innerHTML = ''; // Limpiar método (será POST por defecto)
                        form.reset();
                    }
                });
            }
        });
    </script>

</x-admin-layout>
