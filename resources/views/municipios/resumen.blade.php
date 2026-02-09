@extends('layouts.plantilla')
@section('title', 'Inicio')
@section('meta-description', 'Página principal del Portal de Información Municipal y Regional del Estado de Puebla')
@section('canonical-url', url()->current())
@section('og-title', 'Inicio - Portal de Información Municipal y Regional')
@section('og-description',
    'Bienvenido a la página de inicio del Portal de Información Municipal y Regional del Estado
    de Puebla.')
@section('og:url', url()->current())
@section('twitter-title', 'Inicio - Portal de Información Municipal y Regional')
@section('twitter-description',
    'Bienvenido a la página de inicio del Portal de Información Municipal y Regional del
    Estado de Puebla.')
@section('css')
    <style>
        .themed-pills .nav-link {
            /* El color del texto ahora viene de la variable --dimension-color */
            color: var(--dimension-color, #007bff);
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }

        .themed-pills .nav-link.active {
            /* El color de fondo para el pill activo también viene de la variable */
            background-color: var(--dimension-color, #007bff);
            color: white;
        }
    </style>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection
@section('jss')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('content')
    <div class="container my-5">
        {{-- 1. ENCABEZADO DE IMPACTO --}}
        <div class="p-5 mb-4 bg-light rounded-3 shadow-sm">
            <div class="container-fluid py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="display-4 fw-bold">{{ $municipio->nombre }}</h1>
                        <p class="fs-5 text-muted">
                            {{ $municipio->microrregion->macrorregion->nombre }} / {{ $municipio->microrregion->nombre }}
                        </p>
                    </div>
                    <div class="text-end">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary mb-2">
                            <i class="fas fa-arrow-left me-1"></i> Regresar
                        </a>
                        <a href="{{ route('fichas.resumen.pdf', $municipio) }}" class="btn btn-danger mb-2" target="_blank">
                            <i class="far fa-file-pdf me-1"></i> Exportar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. SECCIÓN DE INDICADORES CLAVE (KPIs) CON PESTAÑAS --}}
        <h3 class="fw-bold border-bottom pb-2 mb-3">Indicadores Clave (KPIs)</h3>
        <ul class="nav nav-pills nav-fill mb-4" id="dimensionTab" role="tablist">
            @foreach ($datosAgrupados as $dimensionData)
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold @if ($loop->first) active @endif"
                        id="tab-{{ $dimensionData['slug'] }}" data-bs-toggle="tab"
                        data-bs-target="#pane-{{ $dimensionData['slug'] }}" type="button" role="tab"
                        style="--bs-nav-pills-link-active-bg: {{ $dimensionData['color'] ?? '#0d6efd' }};">
                        {{ $dimensionData['nombre'] }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content" id="dimensionTabContent">
            @forelse($datosAgrupados as $dimensionData)
                <div class="tab-pane fade @if ($loop->first) show active @endif"
                    id="pane-{{ $dimensionData['slug'] }}" role="tabpanel">
                    {{-- 1. Pestañas Horizontales para las Temáticas --}}
                    <ul class="nav nav-pills border-bottom mb-4 themed-pills" role="tablist"
                        style="--dimension-color: {{ $dimensionData['color'] ?? '#0d6efd' }};">
                        @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if ($loop->first) active @endif"
                                    data-bs-toggle="pill"
                                    data-bs-target="#pane-tematica-{{ Str::slug($tematica) }}-{{ $dimensionData['slug'] }}"
                                    type="button" role="tab">
                                    {{ $tematica }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    {{-- 2. Contenido de las Pestañas de Temáticas --}}
                    <div class="tab-content">
                        @foreach ($dimensionData['tematicas'] as $tematica => $kpis)
                            <div class="tab-pane fade @if ($loop->first) show active @endif"
                                id="pane-tematica-{{ Str::slug($tematica) }}-{{ $dimensionData['slug'] }}"
                                role="tabpanel">

                                <div class="row g-4">
                                    @forelse ($kpis as $kpi)
                                        <div class="col-md-6 col-lg-4">
                                            @if ($kpi['valor'] === 'lista')
                                                {{-- CASO A: Es nuestro KPI de Instrumentos --}}
                                                <div class="card h-100 text-dark shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2 text-muted">{{ $kpi['nombre'] }}</h6>
                                                        <ul class="list-group list-group-flush text-start">
                                                            @foreach ($kpi['valor_display'] as $instrumento)
                                                                <li
                                                                    class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                                    <small>{{ $instrumento->nombre }}</small>
                                                                    <span
                                                                        class="badge bg-primary rounded-pill">{{ $instrumento->pivot->anio }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            @else
                                                <a href="{{ !$kpi['solo_resumen'] ? route('fichas.index', ['indicador_id' => $kpi['indicador_id'], 'municipio_ids' => $municipio->id]) : '#' }}"
                                                    class="card h-100 text-decoration-none text-dark stat-card shadow-sm"
                                                    style="border-left-color: {{ $dimensionData['color'] ?? '#0d6efd' }};">

                                                    <div class="card-body text-center">
                                                        @if ($kpi['indicador_id'] == 92)
                                                            Plantas de tratamiento de aguas residuales por tipo
                                                            <p class="stat-value mb-1">{{ $kpi['valor_display'] }}</p>
                                                            <p class="stat-label mb-0">{{ $kpi['nombre'] }}
                                                                ({{ $kpi['anio'] }})
                                                            </p>
                                                        @else
                                                            <p class="stat-value mb-1">{{ $kpi['valor_display'] }}</p>
                                                            <p class="stat-label mb-0">{{ $kpi['nombre'] }}
                                                                ({{ $kpi['anio'] }})
                                                            </p>
                                                        @endif
                                                    </div>
                                                </a>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <p class="text-muted">No hay KPIs disponibles para esta temática.</p>
                                        </div>
                                    @endforelse
                                </div>

                            </div>
                        @endforeach
                    </div>

                </div>
                @empty
                    <div class="alert alert-warning mt-4">
                        No se encontraron indicadores clave (KPIs) definidos para mostrar en esta ficha.
                    </div>
                @endforelse
            </div>

        </div>
    @endsection
