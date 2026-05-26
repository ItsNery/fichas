@php
    $tituloCard = ($kpi['indicador_nombre'] === $kpi['nombre']) ? $kpi['nombre'] : $kpi['indicador_nombre'] . ': ' . $kpi['nombre'];
@endphp

@if ($kpi['tipo_visual'] === 'lista')
    <div class="card h-100 bento-card text-dark shadow-sm" style="border-bottom: 4px solid {{ $dimensionData['color'] ?? '#c5a059' }};">
        <div class="card-body">
            <h6 class="card-subtitle mb-3 fw-bold text-dark">{{ $tituloCard }}</h6>
            <ul class="list-group list-group-flush text-start">
                @foreach ($kpi['valor_display'] as $instrumento)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-light bg-transparent">
                        <small class="text-muted">{{ $instrumento->nombre }}</small>
                        <span class="badge rounded-pill" style="background-color: {{ $dimensionData['color'] ?? '#c5a059' }};">{{ $instrumento->pivot->anio }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@elseif ($kpi['tipo_visual'] === 'porcentaje')
    <a href="{{ !$kpi['solo_resumen'] ? route('banco-indicadores.index', ['indicador_id' => $kpi['indicador_id'], 'municipio_ids' => $municipio->id]) : '#' }}" class="text-decoration-none">
        <div class="card h-100 bento-card text-dark shadow-sm bg-white" style="border-bottom: 4px solid {{ $dimensionData['color'] ?? '#c5a059' }};">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3">
                <h6 class="text-muted fw-semibold mb-1 small" style="min-height: 2.5rem;">{{ $tituloCard }}</h6>
                <div class="gauge-chart w-100" style="height: 120px;" data-value="{{ $kpi['valor'] }}" data-color="{{ $dimensionData['color'] ?? '#c5a059' }}"></div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <p class="mb-0 text-muted small">Año: {{ $kpi['anio'] }}</p>
                    @if(isset($kpi['tendencia']))
                        <span class="badge bg-light {{ $kpi['tendenciaClase'] }} border">
                            <i class="{{ $kpi['tendenciaIcono'] }}"></i> {{ $kpi['tendencia'] }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </a>
@else
    <a href="{{ !$kpi['solo_resumen'] ? route('banco-indicadores.index', ['indicador_id' => $kpi['indicador_id'], 'municipio_ids' => $municipio->id]) : '#' }}" class="text-decoration-none">
        <div class="card h-100 bento-card text-dark shadow-sm bg-white" style="border-bottom: 4px solid {{ $dimensionData['color'] ?? '#c5a059' }};">
            <div class="card-body d-flex flex-column justify-content-between p-3 position-relative overflow-hidden">
                <div style="z-index: 2;">
                    <h6 class="text-muted fw-semibold mb-2 small">{{ $tituloCard }} <span class="badge bg-light text-dark border ms-1">{{ $kpi['anio'] }}</span></h6>
                    <div class="d-flex align-items-baseline gap-2 flex-wrap">
                        <h3 class="fw-bold mb-0 text-dark">{{ $kpi['valor_display'] }}</h3>
                        @if(isset($kpi['tendencia']))
                            <span class="small fw-bold {{ $kpi['tendenciaClase'] }} bg-white px-1 rounded">
                                <i class="{{ $kpi['tendenciaIcono'] }}"></i> {{ $kpi['tendencia'] }}%
                            </span>
                        @endif
                    </div>
                </div>
                <div class="sparkline-chart position-absolute bottom-0 start-0 w-100 py-3" style="height: 60px; z-index: 1; opacity: 0.8;" data-history="{{ $kpi['historial'] }}" data-color="{{ $dimensionData['color'] ?? '#c5a059' }}"></div>
                <div style="height: 40px;"></div>
            </div>
        </div>
    </a>
@endif
