@extends('layouts.admin')

@section('title', 'Auditoría del Sistema')

@section('content')
<x-page-header
    title="Auditoría del Sistema"
    subtitle="Trazabilidad de cambios y acciones realizadas en la plataforma"
    icon="fa-solid fa-clipboard-list" />

<div class="container py-4">

    <div class="card-panel">
        <div class="card-body p-4">
            <div class="bg-light rounded-3 p-3 mb-4 border">
                <form method="GET" class="row g-3 align-items-end">
                <div class="col-12">
                    <h6 class="text-uppercase text-muted small fw-bold ls-1 mb-0">
                        <i class="fa-solid fa-filter me-1 text-dorado"></i> Filtros de auditoría
                    </h6>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label">Modelo</label>
                    <select name="modelo" class="form-select">
                        <option value="">Todos</option>
                        <option value="App\Models\Indicador" @selected(request('modelo') === 'App\Models\Indicador')>Indicador</option>
                        <option value="App\Models\Variable" @selected(request('modelo') === 'App\Models\Variable')>Variable</option>
                        <option value="App\Models\Dimension" @selected(request('modelo') === 'App\Models\Dimension')>Dimensión</option>
                        <option value="App\Models\Tematica" @selected(request('modelo') === 'App\Models\Tematica')>Temática</option>
                        <option value="App\Models\DatoHistorico" @selected(request('modelo') === 'App\Models\DatoHistorico')>Dato Histórico</option>
                        <option value="App\Models\Municipio" @selected(request('modelo') === 'App\Models\Municipio')>Municipio</option>
                        <option value="App\Models\ConfiguracionFicha" @selected(request('modelo') === 'App\Models\ConfiguracionFicha')>Configuración Ficha</option>
                        <option value="App\Models\LoteDatos" @selected(request('modelo') === 'App\Models\LoteDatos')>Lote de Datos</option>
                        <option value="App\Models\DatoIndicadorComplejo" @selected(request('modelo') === 'App\Models\DatoIndicadorComplejo')>Dato Complejo</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label">Usuario</label>
                    <select name="usuario" class="form-select">
                        <option value="">Todos</option>
                        @foreach(\App\Models\User::orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" @selected(request('usuario') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-control" value="{{ request('desde') }}">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="per_page" class="form-label">Mostrar</label>
                    <select id="per_page" name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $cantidad)
                            <option value="{{ $cantidad }}" @selected($perPage === $cantidad)>{{ $cantidad }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-custom-primary me-2 shadow-sm">
                        <i class="fa-solid fa-filter me-2"></i>Filtrar
                    </button>
                    <a href="{{ route('admin.auditoria.index', ['per_page' => $perPage]) }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-eraser me-1"></i>Limpiar
                    </a>
                </div>
                </form>
            </div>

            <div class="text-muted small mb-2">
                Mostrando {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} de {{ $logs->total() }} registros
            </div>

            <div class="table-responsive">
                <table class="table table-custom w-100 align-middle">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Usuario</th>
                            <th>Modelo</th>
                            <th>Evento</th>
                            <th>Descripción</th>
                            <th class="text-center">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->causer?->name ?? 'Sistema' }}</td>
                            <td>{{ class_basename($log->subject_type) }}</td>
                            <td>
                                @switch($log->event)
                                    @case('created')
                                        <span class="badge bg-success">Creado</span>
                                        @break
                                    @case('updated')
                                        <span class="badge bg-warning">Actualizado</span>
                                        @break
                                    @case('deleted')
                                        <span class="badge bg-danger">Eliminado</span>
                                        @break
                                    @default
                                        <span class="badge bg-info">{{ $log->event }}</span>
                                @endswitch
                            </td>
                            <td>{{ $log->description }}</td>
                            <td class="text-center">
                                @if($log->properties->count())
                                    <button type="button" class="btn-icon-square view"
                                        data-bs-toggle="modal" data-bs-target="#logDetail{{ $log->id }}"
                                        title="Ver cambios">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    <div class="modal fade" id="logDetail{{ $log->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-vino fw-bold">
                                                        <i class="fa-solid fa-code-compare me-2 text-dorado"></i>Detalle de cambios
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <pre class="mb-0"><code>{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fa-solid fa-folder-open fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                <span class="text-muted">No se encontraron registros de auditoría.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
