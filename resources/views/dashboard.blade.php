<x-admin-layout>
    @section('title', 'Dashboard Ejecutivo')

    <x-page-header
        title="Dashboard Ejecutivo"
        subtitle="Sistema Estatal de Información — Gobierno de Puebla"
        icon="fa-solid fa-chart-line" />

    <div class="container-fluid py-4 px-0">
        <div class="alert alert-welcome shadow mb-4" role="alert">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-area fa-3x text-white-50"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h4 class="alert-heading fw-bold mb-1">¡Hola de nuevo, {{ Auth::user()->name }}!</h4>
                    <p class="mb-0 opacity-75">Bienvenido al panel de gobierno. Monitorea la salud del sistema, la completitud de datos y los indicadores clave.</p>
                </div>
            </div>
        </div>

        {{-- Fila 1: KPIs Estratégicos --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="card-panel card-kpi h-100">
                    <div class="card-body d-flex align-items-center justify-content-between px-3 py-3">
                        <div>
                            <h6 class="text-uppercase text-muted mb-1 ls-1 small fw-bold">Registros</h6>
                            <h3 class="fw-bold mb-0 text-vino">{{ number_format($stats['total_datos']) }}</h3>
                            <small class="text-muted">históricos</small>
                        </div>
                        <div class="icon-shape bg-light text-vino"><i class="fas fa-database fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card-panel card-kpi h-100" style="border-left-color: var(--color2);">
                    <div class="card-body d-flex align-items-center justify-content-between px-3 py-3">
                        <div>
                            <h6 class="text-uppercase text-muted mb-1 ls-1 small fw-bold">Indicadores</h6>
                            <h3 class="fw-bold mb-0 text-dorado">{{ $stats['total_indicadores'] }}</h3>
                            <small class="text-muted">activos</small>
                        </div>
                        <div class="icon-shape bg-light text-dorado"><i class="fas fa-chart-pie fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card-panel card-kpi h-100" style="border-left-color: var(--color6);">
                    <div class="card-body d-flex align-items-center justify-content-between px-3 py-3">
                        <div>
                            <h6 class="text-uppercase text-muted mb-1 ls-1 small fw-bold">Completitud</h6>
                            <h3 class="fw-bold mb-0 text-verde">{{ $dataCompletitud }}%</h3>
                            <small class="text-muted">año {{ $latestYear ?? '—' }}</small>
                        </div>
                        <div class="icon-shape bg-light text-verde"><i class="fas fa-check-circle fa-lg"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card-panel card-kpi h-100" style="border-left-color: var(--color4);">
                    <div class="card-body d-flex align-items-center justify-content-between px-3 py-3">
                        <div>
                            <h6 class="text-uppercase text-muted mb-1 ls-1 small fw-bold">Por revisar</h6>
                            <h3 class="fw-bold mb-0 text-secondary">{{ $stats['lotes_pendientes'] }}</h3>
                            <small class="text-muted">lotes de datos</small>
                        </div>
                        <a href="{{ route('admin.lotes-datos.index', ['estado' => 'en_revision']) }}" class="icon-shape bg-light text-secondary text-decoration-none" title="Ver lotes pendientes">
                            <i class="fas fa-clipboard-check fa-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fila 2: Gráficos --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="card-panel h-100 p-3">
                    <div class="card-header-simple d-flex align-items-center">
                        <h6 class="text-uppercase text-muted mb-0 fw-bold small">Registros por Año</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="chartAnios" height="180"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card-panel h-100 p-3">
                    <div class="card-header-simple d-flex align-items-center">
                        <h6 class="text-uppercase text-muted mb-0 fw-bold small">Indicadores por Dimensión</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="chartDimension" height="180"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fila 3: Gobernanza --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="card-panel h-100 p-3">
                    <div class="card-header-simple d-flex align-items-center">
                        <h6 class="text-uppercase text-muted mb-0 fw-bold small">Metadatos SNIEG</h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="display-3 fw-bold text-vino mb-2">{{ $totalIndicadores > 0 ? round(($metadataCompletos / $totalIndicadores) * 100) : 0 }}%</div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-vino" style="width: {{ $totalIndicadores > 0 ? ($metadataCompletos / $totalIndicadores) * 100 : 0 }}%"></div>
                        </div>
                        <small class="text-muted">{{ $metadataCompletos }} de {{ $totalIndicadores }} indicadores con metadatos completos</small>
                        <div class="mt-3">
                            <a href="{{ route('admin.diccionario.index') }}" class="btn btn-sm btn-outline-primary">Gestionar metadatos</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-panel h-100 p-3">
                    <div class="card-header-simple d-flex align-items-center">
                        <h6 class="text-uppercase text-muted mb-0 fw-bold small">Estado de Publicación</h6>
                    </div>
                    <div class="card-body py-3">
                        @php $coloresPub = ['publicado' => 'success', 'borrador' => 'secondary', 'en_revision' => 'warning', 'deprecado' => 'danger']; @endphp
                        @forelse($estadosPublicacion as $estado => $conteo)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><span class="badge bg-{{ $coloresPub[$estado] ?? 'secondary' }} me-2">{{ $estado }}</span></span>
                            <span class="fw-bold">{{ $conteo }}</span>
                        </div>
                        @empty
                        <p class="text-muted text-center py-3 mb-0">Sin datos</p>
                        @endforelse
                        <div class="mt-2 text-end">
                            <a href="{{ route('admin.catalogos.index') }}" class="small">Ir a catálogos →</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-panel h-100 p-3">
                    <div class="card-header-simple d-flex align-items-center">
                        <h6 class="text-uppercase text-muted mb-0 fw-bold small">Actividad Reciente</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($actividadReciente as $act)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong class="small">{{ $act['usuario'] }}</strong>
                                <small class="text-muted d-block">{{ $act['evento'] }}</small>
                            </div>
                            <small class="text-muted">{{ $act['fecha'] }}</small>
                        </li>
                        @empty
                        <li class="list-group-item text-center py-4 text-muted">Sin actividad</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Fila 4: Actividad reciente (datos) + Acciones rápidas --}}
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card-panel h-100 p-3">
                    <div class="card-header-simple d-flex align-items-center">
                        <h6 class="text-uppercase text-muted mb-0 fw-bold small">Últimos Registros Modificados</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($datosRecientes as $dato)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-bottom">
                            <div class="w-100">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge bg-light text-secondary border me-2">{{ $dato->anio }}</span>
                                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">{{ $dato->variable->indicador->nombre_amigable }}</small>
                                </div>
                                <span class="fw-bold text-vino d-block mb-1">{{ $dato->variable->nombre_amigable ?? 'Variable desconocida' }}</span>
                                <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-dorado"></i>{{ $dato->municipio->nombre ?? 'N/A' }}</small>
                            </div>
                            <div class="text-end ps-3">
                                <span class="fw-bold text-dark font-monospace">{{ $dato->valor_display ?? $dato->valor }}</span>
                                <div class="small text-muted mt-1 fst-italic" style="font-size: 0.7rem;">{{ $dato->updated_at->diffForHumans() }}</div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-center py-5 text-muted border-0"><i class="fa-solid fa-folder-open h1 d-block mb-3 opacity-25"></i>No hay actividad reciente.</li>
                        @endforelse
                    </ul>
                    <div class="card-footer bg-white border-0 text-center py-3">
                        <a href="{{ route('admin.datos.index') }}" class="btn btn-link text-decoration-none fw-bold text-verde">Ver todos los registros <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-panel mb-3 p-3">
                    <div class="card-header-simple">
                        <h6 class="mb-0 text-uppercase text-muted fw-bold small">Acceso Rápido</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        @can('datos.editar')<a href="{{ route('admin.datos.index') }}" class="btn btn-custom-primary text-start shadow-sm py-2"><i class="fa-solid fa-pen-to-square me-2"></i> Editar Dato</a>@endcan
                        @if(auth()->user()->canAny(['datos.importar', 'catalogos.importar', 'instrumentos.importar']))<a href="{{ route('admin.import.index') }}" class="btn btn-custom-verde text-start shadow-sm py-2"><i class="fa-solid fa-file-csv me-2"></i> Importación Masiva</a>@endif
                        <div class="my-1 border-top"></div>
                        @can('catalogos.ver')<a href="{{ route('admin.catalogos.index') }}" class="btn btn-custom-action text-start"><i class="fa-solid fa-list-check me-2"></i> Catálogos</a>@endcan
                        @can('diccionario.ver')<a href="{{ route('admin.diccionario.index') }}" class="btn btn-custom-action text-start"><i class="fa-solid fa-book-open me-2"></i> Diccionario</a>@endcan
                        @can('auditoria.ver')<a href="{{ route('admin.auditoria.index') }}" class="btn btn-custom-action text-start"><i class="fa-solid fa-clipboard-list me-2"></i> Auditoría</a>@endcan
                        @can('salud-datos.ver')<a href="{{ route('admin.salud-datos') }}" class="btn btn-outline-danger text-start mt-2"><i class="fa-solid fa-heart-pulse me-2"></i> Salud de Datos</a>@endcan
                    </div>
                </div>
                <div class="card-panel p-3">
                    <div class="card-header-simple">
                        <h6 class="mb-0 text-uppercase text-muted fw-bold small">Feedback</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between text-center px-2">
                            <div>
                                <i class="fa-solid fa-face-smile h2 text-verde mb-1"></i>
                                <div class="h4 fw-bold mb-0 text-vino">{{ $votosFeliz ?? 0 }}</div>
                                <small class="text-muted fw-bold" style="font-size: 0.65rem;">POSITIVO</small>
                            </div>
                            <div class="border-start border-end px-3">
                                <i class="fa-solid fa-face-meh h2 text-dorado mb-1"></i>
                                <div class="h4 fw-bold mb-0 text-vino">{{ $votosNeutral ?? 0 }}</div>
                                <small class="text-muted fw-bold" style="font-size: 0.65rem;">NEUTRAL</small>
                            </div>
                            <div>
                                <i class="fa-solid fa-face-frown h2 text-danger mb-1"></i>
                                <div class="h4 fw-bold mb-0 text-vino">{{ $votosTriste ?? 0 }}</div>
                                <small class="text-muted fw-bold" style="font-size: 0.65rem;">NEGATIVO</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var vino = '#712b2f';
        var dorado = '#b8860b';
        var verde = '#2e7d32';

        new Chart(document.getElementById('chartAnios'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($datosPorAnio->keys()) !!},
                datasets: [{
                    label: 'Registros',
                    data: {!! json_encode($datosPorAnio->values()) !!},
                    backgroundColor: 'rgba(113, 43, 47, 0.7)',
                    borderColor: vino,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        new Chart(document.getElementById('chartDimension'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($dimensionConteo->pluck('nombre')) !!},
                datasets: [{
                    data: {!! json_encode($dimensionConteo->pluck('total')) !!},
                    backgroundColor: ['#712b2f', '#b8860b', '#2e7d32', '#6b7280', '#2563eb', '#9333ea'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 8, font: { size: 11 } }
                    }
                }
            }
        });
    });
    </script>
    @endpush
</x-admin-layout>
