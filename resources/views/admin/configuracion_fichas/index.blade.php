<x-admin-layout>
    @section('title', 'Configuración de Fichas')

    <x-page-header 
        title="Configuración Editorial" 
        subtitle="Organiza y personaliza la visualización de los indicadores en las fichas municipales" 
        icon="fa-solid fa-gears" 
    />

    <div class="container py-4">
        {{-- Barra de Acciones --}}
        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('admin.configuracion-fichas.create') }}" class="btn btn-custom-verde shadow-sm">
                <i class="fas fa-plus me-2"></i>Nueva Configuración
            </a>
        </div>

        <div class="card card-panel border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success border-0 rounded-0 mb-0 alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Dimensión</th>
                                <th>Orden</th>
                                <th>Indicador / Título en Ficha</th>
                                <th>Visualización</th>
                                <th>Estado</th>
                                <th class="pe-4 text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $vis_icons = [
                                    'kpi' => 'fas fa-stopwatch-20',
                                    'piramide' => 'fas fa-align-center',
                                    'treemap' => 'fas fa-th-large',
                                    'barras' => 'fas fa-chart-bar',
                                    'lineas' => 'fas fa-chart-line',
                                    'mapa' => 'fas fa-map-marked-alt'
                                ];
                                $section_colors = [
                                    'general' => 'bg-secondary',
                                    'demografia' => 'bg-info',
                                    'economia' => 'bg-warning',
                                    'salud' => 'bg-success',
                                    'educacion' => 'bg-primary',
                                    'vivienda' => 'bg-orange',
                                    'seguridad' => 'bg-danger',
                                    'medio_ambiente' => 'bg-teal'
                                ];
                            @endphp
                            @forelse($configuraciones as $config)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge rounded-pill bg-secondary text-uppercase px-3 py-2" style="font-size: 0.65rem;">
                                            {{ $config->indicador->tematica->dimension->nombre ?? 'Sin Dimensión' }}
                                        </span>
                                    </td>
                                    <td class="fw-bold text-muted">#{{ $config->orden }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($config->icono)
                                                <div class="me-3 text-vino" style="width: 20px"><i class="{{ $config->icono }}"></i></div>
                                            @endif
                                            <div>
                                                <div class="fw-bold text-vino">{{ $config->titulo_reporte ?: $config->indicador->nombre_amigable }}</div>
                                                <div class="text-muted small">{{ $config->indicador->nombre_amigable }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="{{ $vis_icons[$config->tipo_visualizacion] ?? 'fas fa-chart-area' }} me-2 text-muted"></i>
                                            <span class="small fw-semibold">{{ ucfirst($config->tipo_visualizacion) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($config->activo)
                                            <span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i>Activo</span>
                                        @else
                                            <span class="text-danger small fw-bold"><i class="fas fa-times-circle me-1"></i>Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-end text-nowrap">
                                        <a href="{{ route('admin.configuracion-fichas.edit', $config->id) }}" class="btn-icon-square edit me-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.configuracion-fichas.destroy', $config->id) }}" method="POST" class="d-inline form-eliminar">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn-icon-square danger btn-eliminar" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-5 text-center text-muted">
                                        <i class="fas fa-info-circle mb-3 d-block fa-2x opacity-25"></i>
                                        No hay indicadores configurados para la ficha.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($configuraciones->hasPages())
                <div class="card-footer bg-white border-0 py-3 ps-4">
                    {{ $configuraciones->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejo de eliminación con SweetAlert
            document.querySelectorAll('.btn-eliminar').forEach(button => {
                button.addEventListener('click', function() {
                    const form = this.closest('.form-eliminar');
                    
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Esta acción eliminará la configuración del indicador en la ficha municipal.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#5f1b2d',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Auto-cerrar alertas de éxito
            const alert = document.querySelector('.alert-success');
            if(alert) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 3000);
            }
        });
    </script>
</x-admin-layout>
