<x-admin-layout>
    @section('title', 'Inicio')

    {{-- 1. HEADER (Componente unificado) --}}
    <x-page-header
        title="Panel de Inicio"
        subtitle="Resumen general del Sistema Estatal de Información"
        icon="fa-solid fa-chart-line" />

    <div class="container py-4">
        {{-- 2. BIENVENIDA --}}
        <div class="alert alert-welcome shadow mb-4" role="alert">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-area fa-3x text-white-50"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h4 class="alert-heading fw-bold mb-1">¡Hola de nuevo, {{ Auth::user()->name }}!</h4>
                    <p class="mb-0 opacity-75">Bienvenido al Panel de Administración. Aquí tienes un resumen de la actividad reciente.</p>
                </div>
            </div>
        </div>

        {{-- 3. TARJETAS KPI (Fila Superior) --}}
        <div class="row g-4 mb-4">
            {{-- KPI: Base de Datos --}}
            <div class="col-md-6">
                <div class="card-panel card-kpi h-100">
                    <div class="card-body d-flex align-items-center justify-content-between px-4 py-4">
                        <div>
                            <h6 class="text-uppercase text-muted mb-2 ls-1 small fw-bold">Base de Datos</h6>
                            <h2 class="display-5 fw-bold mb-0 text-vino">{{ number_format($stats['total_datos']) }}</h2>
                            <span class="text-verde small fw-bold">
                                <i class="fas fa-database me-1"></i> Registros Históricos
                            </span>
                        </div>
                        <div class="icon-shape bg-light text-vino">
                            <i class="fas fa-database fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPI: Comunidad --}}
            <div class="col-md-6">
                <div class="card-panel card-kpi h-100" style="border-left-color: var(--color6);">
                    <div class="card-body d-flex align-items-center justify-content-between px-4 py-4">
                        <div>
                            <h6 class="text-uppercase text-muted mb-2 ls-1 small fw-bold">Comunidad</h6>
                            <h2 class="display-5 fw-bold mb-0 text-verde">{{ number_format($stats['total_usuarios']) }}</h2>
                            <span class="text-muted small">
                                <i class="fas fa-users me-1"></i> Usuarios Admin
                            </span>
                        </div>
                        <div class="icon-shape bg-light text-verde">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. ESTRUCTURA DEL CATÁLOGO (Fila central) --}}
        <div class="card-panel mb-4">
            <div class="card-header-simple d-flex align-items-center">
                <i class="fa-solid fa-sitemap me-2 text-dorado"></i>
                <h6 class="text-uppercase text-muted ls-1 mb-0 fw-bold" style="font-size: 0.8rem;">Estructura del Catálogo</h6>
            </div>
            <div class="card-body p-0"> {{-- p-0 para que los bordes lleguen al final --}}
                <div class="row g-0 text-center"> {{-- g-0 para quitar espacios entre columnas --}}

                    {{-- Columna 1: Dimensiones --}}
                    <div class="col-md-3">
                        <div class="stat-item">
                            <span class="d-block text-uppercase text-muted fw-bold mb-1">Dimensiones</span>
                            <h3 class="fw-bold text-dorado mb-0">{{ number_format($stats['total_dimensiones']) }}</h3>
                            <i class="fas fa-ruler-combined stat-icon-bg"></i>
                        </div>
                    </div>

                    {{-- Columna 2: Temáticas --}}
                    <div class="col-md-3 border-start-md">
                        <div class="stat-item">
                            <span class="d-block text-uppercase text-muted fw-bold mb-1">Temáticas</span>
                            <h3 class="fw-bold text-dorado mb-0">{{ number_format($stats['total_tematicas']) }}</h3>
                            <i class="fas fa-layer-group stat-icon-bg"></i>
                        </div>
                    </div>

                    {{-- Columna 3: Indicadores --}}
                    <div class="col-md-3 border-start-md">
                        <div class="stat-item">
                            <span class="d-block text-uppercase text-muted fw-bold mb-1">Indicadores</span>
                            <h3 class="fw-bold text-dorado mb-0">{{ number_format($stats['total_indicadores']) }}</h3>
                            <i class="fas fa-chart-pie stat-icon-bg"></i>
                        </div>
                    </div>

                    {{-- Columna 4: Variables --}}
                    <div class="col-md-3 border-start-md">
                        <div class="stat-item">
                            <span class="d-block text-uppercase text-muted fw-bold mb-1">Variables</span>
                            <h3 class="fw-bold text-dorado mb-0">{{ number_format($stats['total_variables']) }}</h3>
                            <i class="fas fa-tags stat-icon-bg"></i>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="row g-4">

            {{-- 5. ACTIVIDAD RECIENTE (Columna Izquierda) --}}
            <div class="col-lg-8">
                <div class="card-panel h-100">
                    <div class="card-header-simple">
                        <h5 class="mb-0 text-vino fw-bold">
                            <i class="fa-solid fa-clock-rotate-left me-2 text-dorado"></i>Actividad Reciente
                        </h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($datosRecientes as $dato)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-bottom">
                            <div class="w-100">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge bg-light text-secondary border me-2">{{ $dato->anio }}</span>
                                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">
                                        {{ $dato->variable->indicador->nombre_amigable }}
                                    </small>
                                </div>

                                <span class="fw-bold text-vino d-block mb-1">
                                    {{ $dato->variable->nombre_amigable ?? 'Variable desconocida' }}
                                </span>

                                <small class="text-muted">
                                    <i class="fa-solid fa-location-dot me-1 text-dorado"></i> {{ $dato->municipio->nombre ?? 'N/A' }}
                                </small>
                            </div>
                            <div class="text-end ps-3">
                                <span class="fs-5 fw-bold text-dark font-monospace">
                                    {{ $dato->valor_display ?? $dato->valor }}
                                </span>
                                <div class="small text-muted mt-1 fst-italic" style="font-size: 0.7rem;">
                                    {{ $dato->updated_at->diffForHumans() }}
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-center py-5 text-muted border-0">
                            <i class="fa-solid fa-folder-open h1 d-block mb-3 opacity-25"></i>
                            No hay actividad reciente registrada.
                        </li>
                        @endforelse
                    </ul>
                    <div class="card-footer bg-white border-0 text-center py-3">
                        <a href="{{ route('admin.datos.index') }}" class="btn btn-link text-decoration-none fw-bold text-verde">
                            Ver todos los registros <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- 6. ACCIONES RÁPIDAS (Columna Derecha) --}}
            <div class="col-lg-4">

                {{-- Panel de Gestión --}}
                <div class="card-panel mb-4">
                    <div class="card-header-simple">
                        <h5 class="mb-0 text-vino fw-bold">
                            <i class="fa-solid fa-sliders me-2 text-dorado"></i>Gestión Rápida
                        </h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        {{-- Botones Principales (Usando las nuevas clases) --}}
                        <a href="{{ route('admin.datos.index') }}" class="btn btn-custom-primary text-start shadow-sm py-2">
                            <i class="fa-solid fa-pen-to-square me-2"></i> Editar Dato Individual
                        </a>

                        {{-- Botón Verde para Importación --}}
                        <a href="{{ route('admin.import.index') }}" class="btn btn-custom-verde text-start shadow-sm py-2">
                            <i class="fa-solid fa-file-csv me-2"></i> Importación Masiva
                        </a>

                        <div class="my-2 border-top"></div>

                        {{-- Botones Secundarios (Estilo menú lateral) --}}
                        <a href="{{ route('admin.catalogos.index') }}" class="btn btn-custom-action text-start">
                            <i class="fa-solid fa-list-check me-2"></i> Catálogos
                        </a>
                        <a href="{{ route('admin.instrumentos.index') }}" class="btn btn-custom-action text-start">
                            <i class="fa-solid fa-file-contract me-2"></i> Instrumentos
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-custom-action text-start">
                            <i class="fa-solid fa-users-gear me-2"></i> Usuarios
                        </a>

                        {{-- Botón de Salud (Rojo Outline) --}}
                        <a href="{{ route('admin.salud-datos') }}" class="btn btn-outline-danger text-start mt-2">
                            <i class="fa-solid fa-heart-pulse me-2"></i> Salud de los Datos
                        </a>
                    </div>
                </div>

                {{-- 7. FEEDBACK CIUDADANO --}}
                <div class="card-panel">
                    <div class="card-header-simple">
                        <h5 class="mb-0 text-vino fw-bold">
                            <i class="fa-solid fa-star me-2 text-dorado"></i>Feedback
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between text-center px-2">
                            <div class="emoji-stat">
                                <i class="fa-solid fa-face-smile h2 text-verde mb-2"></i>
                                <div class="h4 fw-bold mb-0 text-vino">{{ $votosFeliz ?? 0 }}</div>
                                <small class="text-muted fw-bold" style="font-size: 0.65rem;">POSITIVO</small>
                            </div>
                            <div class="emoji-stat border-start border-end px-3">
                                <i class="fa-solid fa-face-meh h2 text-dorado mb-2"></i>
                                <div class="h4 fw-bold mb-0 text-vino">{{ $votosNeutral ?? 0 }}</div>
                                <small class="text-muted fw-bold" style="font-size: 0.65rem;">NEUTRAL</small>
                            </div>
                            <div class="emoji-stat">
                                <i class="fa-solid fa-face-frown h2 text-rojo mb-2"></i>
                                <div class="h4 fw-bold mb-0 text-vino">{{ $votosTriste ?? 0 }}</div>
                                <small class="text-muted fw-bold" style="font-size: 0.65rem;">NEGATIVO</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-admin-layout>