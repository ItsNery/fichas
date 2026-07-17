@extends('layouts.admin')

@section('title', 'Diccionario de Datos')

@section('content')
<x-page-header
    title="Diccionario de Datos"
    subtitle="Metadatos, documentación y estado de gobernanza de los indicadores"
    icon="fa-solid fa-book-open" />

<div class="container py-4">

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card-panel">
        <div class="card-body p-4">
            <div class="bg-light rounded-3 p-3 mb-4 border">
                <form method="GET" action="{{ route('admin.diccionario.index') }}" class="row g-3 align-items-end">
                <div class="col-12">
                    <h6 class="text-uppercase text-muted small fw-bold ls-1 mb-0">
                        <i class="fa-solid fa-filter me-1 text-dorado"></i> Filtros de búsqueda
                    </h6>
                </div>
                <div class="col-lg-7">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="search" id="search" name="search" value="{{ $search }}"
                            class="form-control border-start-0"
                            placeholder="Indicador, responsable, temática, dimensión...">
                    </div>
                </div>
                <div class="col-sm-5 col-lg-2">
                    <label for="per_page" class="form-label">Mostrar</label>
                    <select id="per_page" name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $cantidad)
                            <option value="{{ $cantidad }}" @selected($perPage === $cantidad)>{{ $cantidad }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-7 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-custom-primary flex-grow-1 shadow-sm">
                        <i class="fa-solid fa-magnifying-glass me-2"></i>Buscar
                    </button>
                    @if($search !== '')
                        <a href="{{ route('admin.diccionario.index', ['per_page' => $perPage]) }}"
                            class="btn btn-outline-secondary" title="Limpiar búsqueda">
                            <i class="fa-solid fa-eraser me-1"></i>Limpiar
                        </a>
                    @endif
                </div>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2 text-muted small">
                <span>Mostrando {{ $indicadores->firstItem() ?? 0 }}–{{ $indicadores->lastItem() ?? 0 }} de {{ $indicadores->total() }}</span>
                @if($search !== '')
                    <span>Resultados para “{{ $search }}”</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-custom w-100 align-middle">
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th>Dimensión</th>
                            <th>Temática</th>
                            <th>Responsable</th>
                            <th>Periodicidad</th>
                            <th>Cobertura</th>
                            <th>Clasificación</th>
                            <th>Estado</th>
                            <th class="text-center">Completitud</th>
                            @can('diccionario.editar')
                            <th class="text-center">Acciones</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($indicadores as $item)
                        @php $ind = $item['indicador']; @endphp
                        <tr>
                            <td>{{ $ind->nombre_amigable }}</td>
                            <td>{{ $ind->tematica?->dimension?->nombre ?? '-' }}</td>
                            <td>{{ $ind->tematica?->nombre ?? '-' }}</td>
                            <td>{{ $ind->responsable ?? '-' }}</td>
                            <td>{{ $ind->periodicidad ?? '-' }}</td>
                            <td>{{ $ind->cobertura_geografica ?? '-' }}</td>
                            <td>
                                @switch($ind->clasificacion ?? 'publica')
                                    @case('publica') <span class="badge bg-success">Pública</span> @break
                                    @case('uso_interno') <span class="badge bg-warning">Uso interno</span> @break
                                    @case('confidencial') <span class="badge bg-danger">Confidencial</span> @break
                                @endswitch
                            </td>
                            <td>
                                @switch($ind->estado_publicacion ?? 'publicado')
                                    @case('borrador') <span class="badge bg-secondary">Borrador</span> @break
                                    @case('en_revision') <span class="badge bg-info">En revisión</span> @break
                                    @case('publicado') <span class="badge bg-success">Publicado</span> @break
                                    @case('deprecado') <span class="badge bg-dark">Deprecado</span> @break
                                @endswitch
                            </td>
                            <td class="text-center">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar @if($item['completitud'] < 50) bg-danger @elseif($item['completitud'] < 80) bg-warning @else bg-success @endif"
                                        role="progressbar"
                                        style="width: {{ $item['completitud'] }}%"
                                        aria-valuenow="{{ $item['completitud'] }}" aria-valuemin="0" aria-valuemax="100">
                                        {{ $item['completitud'] }}%
                                    </div>
                                </div>
                            </td>
                            @can('diccionario.editar')
                            <td class="text-center">
                                <a href="{{ route('admin.diccionario.edit', $ind) }}"
                                    class="btn-icon-square edit"
                                    data-bs-toggle="tooltip" title="Editar metadatos">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('diccionario.editar') ? 10 : 9 }}" class="text-center py-5">
                                <i class="fa-solid fa-folder-open fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                <span class="text-muted">{{ $search !== '' ? 'No se encontraron indicadores con ese criterio.' : 'No hay indicadores registrados.' }}</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($indicadores->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $indicadores->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
