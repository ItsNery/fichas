<x-admin-layout>
    @section('title', 'Configuración de Fichas')

    <x-page-header 
        title="Configuración Editorial" 
        subtitle="Organiza y personaliza la visualización de los indicadores en las fichas municipales" 
        icon="fa-solid fa-gears" 
    />

    <style>
        .config-index-toolbar { padding: 1rem; background: #f8f7f5; border: 1px solid #e8e3df; border-radius: 1rem; }
        .config-index-toolbar .form-control, .config-index-toolbar .form-select { min-height: 42px; }
        .config-index-toolbar .input-group-text { background: #fff; }
        .config-index-meta { color: #737a80; font-size: .78rem; }
        .config-index-title { color: #651b2c; font-weight: 800; }
        .config-index-subtitle { color: #70777d; font-size: .76rem; }
        .config-index-visual { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .65rem; color: #59616a; background: #f5f3f1; border-radius: .65rem; font-size: .76rem; font-weight: 700; }
        .config-index-state { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 800; }
        .config-index-state.active { color: #1f6b4d; background: #e7f5ed; }
        .config-index-state.inactive { color: #a13b42; background: #fbeaec; }
        @media (max-width: 767.98px) {
            .config-index-toolbar .btn { width: 100%; }
        }
    </style>

    <div class="container py-4">
        {{-- Barra de búsqueda y acciones --}}
        <div class="config-index-toolbar mb-4">
            <form method="GET" action="{{ route('admin.configuracion-fichas.index') }}" class="row g-2 align-items-end">
                <div class="col-lg-5">
                    <label for="config-search" class="form-label small fw-bold text-secondary mb-1">Buscar configuración</label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input id="config-search" type="search" name="q" value="{{ request('q') }}"
                            class="form-control border-start-0" placeholder="Indicador, título, temática o dimensión...">
                    </div>
                </div>
                <div class="col-sm-5 col-lg-2">
                    <label for="config-visual" class="form-label small fw-bold text-secondary mb-1">Visualización</label>
                    <select id="config-visual" name="visualizacion" class="form-select">
                        <option value="">Todas</option>
                        @foreach($visualizaciones as $visualizacion)
                            <option value="{{ $visualizacion }}" {{ request('visualizacion') === $visualizacion ? 'selected' : '' }}>{{ ucfirst($visualizacion) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-5 col-lg-2">
                    <label for="config-status" class="form-label small fw-bold text-secondary mb-1">Estado</label>
                    <select id="config-status" name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Activos</option>
                        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
                <div class="col-sm-2 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-custom-primary flex-grow-1"><i class="fas fa-filter me-1"></i>Filtrar</button>
                    @if(request()->hasAny(['q', 'visualizacion', 'estado']))
                        <a href="{{ route('admin.configuracion-fichas.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros"><i class="fas fa-rotate-left"></i></a>
                    @endif
                </div>
            </form>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <div class="config-index-meta"><i class="fas fa-layer-group me-1"></i>{{ $configuraciones->total() }} configuraciones encontradas</div>
                @can('configuracion-fichas.crear')
                <a href="{{ route('admin.configuracion-fichas.create') }}" class="btn btn-custom-verde shadow-sm">
                    <i class="fas fa-plus me-2"></i>Nueva Configuración
                </a>
                @endcan
            </div>
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
                                @if(auth()->user()->can('configuracion-fichas.editar') || auth()->user()->can('configuracion-fichas.eliminar'))
                                <th class="pe-4 text-end">Acciones</th>
                                @endif
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
                                    'mapa' => 'fas fa-map-marked-alt',
                                    'scatter' => 'fas fa-project-diagram'
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
                                        <div>
                                            <span class="badge rounded-pill bg-secondary text-uppercase px-3 py-2" style="font-size: 0.65rem;">
                                                {{ $config->indicador->tematica->dimension->nombre ?? 'Sin Dimensión' }}
                                            </span>
                                            <div class="config-index-subtitle mt-1">{{ $config->indicador->tematica->nombre ?? 'Sin temática' }}</div>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-muted">#{{ $config->orden }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($config->icono)
                                                <div class="me-3 text-vino" style="width: 20px"><i class="{{ $config->icono }}"></i></div>
                                            @endif
                                            <div>
                                                <div class="fw-bold text-vino">{{ $config->titulo_reporte ?: $config->indicador->nombre_amigable }}</div>
                                                @if($config->subtitulo_reporte)
                                                    <div class="text-secondary small">{{ $config->subtitulo_reporte }}</div>
                                                @endif
                                                <div class="text-muted small">{{ $config->indicador->nombre_amigable }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="config-index-visual"><i class="{{ $vis_icons[$config->tipo_visualizacion] ?? 'fas fa-chart-area' }}"></i>{{ ucfirst($config->tipo_visualizacion) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($config->activo)
                                            <span class="config-index-state active"><i class="fas fa-check-circle"></i>Activo</span>
                                        @else
                                            <span class="config-index-state inactive"><i class="fas fa-times-circle"></i>Inactivo</span>
                                        @endif
                                    </td>
                                    @if(auth()->user()->can('configuracion-fichas.editar') || auth()->user()->can('configuracion-fichas.eliminar'))
                                    <td class="pe-4 text-end text-nowrap">
                                        @can('configuracion-fichas.editar')
                                        <a href="{{ route('admin.configuracion-fichas.edit', $config->id) }}" class="btn-icon-square edit me-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('configuracion-fichas.eliminar')
                                        <form action="{{ route('admin.configuracion-fichas.destroy', $config->id) }}" method="POST" class="d-inline form-eliminar">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn-icon-square danger btn-eliminar" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->can('configuracion-fichas.editar') || auth()->user()->can('configuracion-fichas.eliminar') ? 6 : 5 }}" class="py-5 text-center text-muted">
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
