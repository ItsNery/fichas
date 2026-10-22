@extends('layouts.admin')

@section('title', 'Analítica de tráfico')

@section('content')
<x-page-header
    title="Analítica de tráfico"
    subtitle="Actividad de rutas costosas: comparativas y generación de PDF"
    icon="fa-solid fa-chart-line" />

<div class="container py-4">
    <div class="card-panel mb-4">
        <div class="card-body p-4">
            <form method="GET" class="d-flex gap-2 align-items-end">
                <div>
                    <label for="hours" class="form-label">Periodo</label>
                    <select id="hours" name="hours" class="form-select" onchange="this.form.submit()">
                        @foreach([1 => 'Última hora', 6 => 'Últimas 6 horas', 24 => 'Últimas 24 horas', 72 => 'Últimos 3 días', 168 => 'Últimos 7 días'] as $value => $label)
                            <option value="{{ $value }}" @selected($hours === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-custom-primary"><i class="fa-solid fa-filter me-2"></i>Actualizar</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Solicitudes', number_format($summary['requests']), 'fa-arrow-pointer'],
            ['IPs únicas', number_format($summary['uniqueIps']), 'fa-users'],
            ['Límites 429', number_format($summary['rateLimited']), 'fa-shield-halved'],
            ['Errores 5xx', number_format($summary['serverErrors']), 'fa-triangle-exclamation'],
            ['Promedio', $summary['averageDuration'].' ms', 'fa-stopwatch'],
        ] as [$label, $value, $icon])
            <div class="col-md-4 col-xl">
                <div class="card-panel h-100"><div class="card-body p-3">
                    <div class="text-muted small"><i class="fa-solid {{ $icon }} me-1"></i>{{ $label }}</div>
                    <div class="fs-4 fw-bold">{{ $value }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-panel h-100"><div class="card-body p-4">
                <h5 class="mb-3">Rutas con mayor actividad</h5>
                <div class="table-responsive"><table class="table table-custom mb-0">
                    <thead><tr><th>Ruta</th><th>Solicitudes</th><th>Promedio</th><th>429</th></tr></thead>
                    <tbody>@forelse($topRoutes as $route)<tr>
                        <td><code>{{ $route->route_name }}</code></td><td>{{ number_format($route->requests) }}</td>
                        <td>{{ round($route->average_duration) }} ms</td><td>{{ number_format($route->rate_limited) }}</td>
                    </tr>@empty<tr><td colspan="4" class="text-muted text-center py-4">Aún no hay eventos en este periodo.</td></tr>@endforelse</tbody>
                </table></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card-panel h-100"><div class="card-body p-4">
                <h5 class="mb-3">Orígenes con mayor actividad</h5>
                <div class="table-responsive"><table class="table table-custom mb-0">
                    <thead><tr><th>IP</th><th>Solicitudes</th><th>Promedio</th><th>429</th></tr></thead>
                    <tbody>@forelse($topIps as $source)<tr>
                        <td><code>{{ $source->display_ip }}</code></td>
                        <td>{{ number_format($source->requests) }}</td><td>{{ round($source->average_duration) }} ms</td><td>{{ number_format($source->rate_limited) }}</td>
                    </tr>@empty<tr><td colspan="4" class="text-muted text-center py-4">Aún no hay eventos en este periodo.</td></tr>@endforelse</tbody>
                </table></div>
            </div></div>
        </div>
    </div>

    <div class="card-panel mt-4"><div class="card-body p-4">
        <h5 class="mb-3">Eventos recientes</h5>
        <div class="table-responsive"><table class="table table-custom align-middle">
            <thead><tr><th>Fecha</th><th>Ruta</th><th>Estado</th><th>Duración</th><th>IP</th><th>Huella de visita</th></tr></thead>
            <tbody>@forelse($recentEvents as $event)<tr>
                <td>{{ $event->occurred_at->format('d/m/Y H:i:s') }}</td><td><code>{{ $event->route_name }}</code></td>
                <td><span class="badge {{ $event->status === 429 ? 'bg-warning text-dark' : ($event->status >= 500 ? 'bg-danger' : 'bg-success') }}">{{ $event->status }}</span></td>
                <td>{{ number_format($event->duration_ms) }} ms</td>
                <td>@can('trafico.ip.ver')<code>{{ $event->client_ip }}</code>@else<code>{{ $event->maskedIp() }}</code>@endcan</td>
                <td class="small text-nowrap">
                    <div><span class="text-muted">Visitante:</span> <code>{{ $event->shortFingerprint($event->visitor_hash) }}</code></div>
                    <div><span class="text-muted">Sesión:</span> <code>{{ $event->shortFingerprint($event->session_hash) }}</code></div>
                    <div><span class="text-muted">Navegador:</span> <code>{{ $event->shortFingerprint($event->user_agent_hash) }}</code></div>
                    <div><span class="text-muted">Origen:</span> <code>{{ $event->shortFingerprint($event->referer_hash) }}</code></div>
                </td>
            </tr>@empty<tr><td colspan="6" class="text-muted text-center py-4">Aún no hay eventos registrados.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-3">{{ $recentEvents->links() }}</div>
    </div></div>
</div>
@endsection
