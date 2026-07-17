@extends('layouts.admin')

@section('title', 'Lotes de Datos')

@section('content')
<x-page-header
    title="Lotes de Datos"
    subtitle="Seguimiento de cargas, revisiones y publicación de datos históricos"
    icon="fa-solid fa-box-archive" />

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card-panel">
        <div class="card-body p-4">
            <div class="bg-light rounded-3 p-3 mb-4 border">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-12">
                        <h6 class="text-uppercase text-muted small fw-bold ls-1 mb-0">
                            <i class="fa-solid fa-filter me-1 text-dorado"></i> Filtros de seguimiento
                        </h6>
                    </div>
                    <div class="col-md-5">
                        <label for="estado" class="form-label">Estado</label>
                        <select id="estado" name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="borrador" @selected($estado === 'borrador')>Borrador</option>
                            <option value="en_revision" @selected($estado === 'en_revision')>En revisión</option>
                            <option value="aprobado" @selected($estado === 'aprobado')>Aprobado</option>
                            <option value="rechazado" @selected($estado === 'rechazado')>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="per_page" class="form-label">Mostrar</label>
                        <select id="per_page" name="per_page" class="form-select" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100] as $cantidad)
                                <option value="{{ $cantidad }}" @selected($perPage === $cantidad)>{{ $cantidad }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-custom-primary flex-grow-1">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <a href="{{ route('admin.lotes-datos.index', ['per_page' => $perPage]) }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-eraser me-1"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="text-muted small mb-2">
                Mostrando {{ $lotes->firstItem() ?? 0 }}–{{ $lotes->lastItem() ?? 0 }} de {{ $lotes->total() }} lotes
            </div>

            @php
                $status = [
                    'borrador' => ['Borrador', 'secondary'],
                    'en_revision' => ['En revisión', 'warning'],
                    'aprobado' => ['Aprobado', 'success'],
                    'rechazado' => ['Rechazado', 'danger'],
                ];
            @endphp
            <div class="table-responsive">
                <table class="table table-custom w-100 align-middle">
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Archivo</th>
                            <th>Responsable</th>
                            <th>Fecha</th>
                            <th class="text-center">Filas</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lotes as $lote)
                            @php $badge = $status[$lote->estado] ?? [$lote->estado, 'secondary']; @endphp
                            <tr>
                                <td class="fw-bold text-vino">#{{ $lote->id }}</td>
                                <td>
                                    <span class="d-block fw-semibold">{{ $lote->archivo_original }}</span>
                                    <small class="text-muted">{{ str_replace('_', ' ', ucfirst($lote->tipo)) }} · {{ $lote->filas_insertar }} nuevas · {{ $lote->filas_actualizar }} actualizaciones</small>
                                </td>
                                <td>{{ $lote->usuarioCarga?->name ?? '—' }}</td>
                                <td>{{ $lote->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-center">{{ number_format($lote->total_filas) }}</td>
                                <td class="text-center"><span class="badge bg-{{ $badge[1] }}">{{ $badge[0] }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('admin.lotes-datos.show', $lote) }}" class="btn-icon-square view" title="Ver lote">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fa-solid fa-box-open fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                    <span class="text-muted">No se encontraron lotes de datos.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lotes->hasPages())
                <div class="d-flex justify-content-center mt-3">{{ $lotes->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
