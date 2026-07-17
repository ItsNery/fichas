@extends('layouts.admin')

@section('title', 'Detalle del Lote')

@section('content')
<x-page-header
    title="Detalle del Lote #{{ $lote->id }}"
    subtitle="Revisión y trazabilidad de la carga de datos históricos"
    icon="fa-solid fa-magnifying-glass-chart" />

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>{{ $errors->first() }}
        </div>
    @endif

    @php
        $status = [
            'borrador' => ['Borrador', 'secondary'],
            'en_revision' => ['En revisión', 'warning'],
            'aprobado' => ['Aprobado', 'success'],
            'rechazado' => ['Rechazado', 'danger'],
        ];
        $badge = $status[$lote->estado] ?? [$lote->estado, 'secondary'];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-panel h-100 p-3">
                <small class="text-uppercase text-muted fw-bold">Estado</small>
                <div class="mt-2"><span class="badge bg-{{ $badge[1] }} fs-6">{{ $badge[0] }}</span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-panel h-100 p-3">
                <small class="text-uppercase text-muted fw-bold">Total de filas</small>
                <div class="h3 text-vino fw-bold mb-0 mt-1">{{ number_format($lote->total_filas) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-panel h-100 p-3">
                <small class="text-uppercase text-muted fw-bold">Nuevas</small>
                <div class="h3 text-success fw-bold mb-0 mt-1">{{ number_format($lote->filas_insertar) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-panel h-100 p-3">
                <small class="text-uppercase text-muted fw-bold">Actualizaciones</small>
                <div class="h3 text-warning fw-bold mb-0 mt-1">{{ number_format($lote->filas_actualizar) }}</div>
            </div>
        </div>
    </div>

    <div class="card-panel mb-4">
        <div class="card-header-simple d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 text-vino fw-bold">
                <i class="fa-solid {{ $lote->tipo === 'dato_historico_manual' ? 'fa-pen-to-square' : 'fa-file-excel' }} mx-2 text-dorado"></i>{{ $lote->archivo_original }}
            </h5>
            <a href="{{ route('admin.lotes-datos.index') }}" class="btn btn-outline-secondary btn-sm mx-2">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver
            </a>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 small">
                <div class="col-md-4"><strong>Cargado por:</strong> {{ $lote->usuarioCarga?->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Fecha:</strong> {{ $lote->created_at->format('d/m/Y H:i') }}</div>
                <div class="col-md-4"><strong>Revisor:</strong> {{ $lote->usuarioRevision?->name ?? 'Pendiente' }}</div>
                <div class="col-md-4"><strong>Tipo:</strong> {{ str_replace('_', ' ', ucfirst($lote->tipo)) }}</div>
                @if($lote->observaciones)
                    <div class="col-12">
                        <div class="alert alert-{{ $lote->estado === 'rechazado' ? 'danger' : 'secondary' }} mb-0">
                            <strong>Observaciones:</strong> {{ $lote->observaciones }}
                        </div>
                    </div>
                @endif
            </div>

            @can('datos.importar')
                @if($lote->estado === 'borrador' && ($lote->usuario_carga_id === auth()->id() || auth()->user()->can('datos.aprobar')))
                    <div class="border-top mt-4 pt-4 text-end">
                        <form method="POST" action="{{ route('admin.import.datos.perform') }}" class="workflow-confirm" data-action="submit">
                            @csrf
                            <input type="hidden" name="lote_id" value="{{ $lote->id }}">
                            <button class="btn btn-custom-verde">
                                <i class="fa-solid fa-paper-plane me-2"></i>Enviar a revisión
                            </button>
                        </form>
                    </div>
                @endif
            @endcan

            @can('datos.aprobar')
                @if($lote->estado === 'en_revision')
                    <div class="border-top mt-4 pt-4">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <form method="POST" action="{{ route('admin.lotes-datos.aprobar', $lote) }}" class="workflow-confirm" data-action="approve">
                                    @csrf
                                    <button class="btn btn-custom-verde w-100">
                                        <i class="fa-solid fa-circle-check me-2"></i>Aprobar y publicar
                                    </button>
                                </form>
                            </div>
                            <div class="col-lg-7">
                                <form method="POST" action="{{ route('admin.lotes-datos.rechazar', $lote) }}" class="d-flex gap-2 workflow-confirm" data-action="reject">
                                    @csrf
                                    <input type="text" name="observaciones" class="form-control" required minlength="10"
                                        placeholder="Motivo del rechazo (mínimo 10 caracteres)">
                                    <button class="btn btn-outline-danger text-nowrap">
                                        <i class="fa-solid fa-circle-xmark me-1"></i>Rechazar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>

    <div class="card-panel">
        <div class="card-header-simple px-2">
            <h6 class="mb-0 text-uppercase text-muted fw-bold small">Vista previa de filas</h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-custom w-100 align-middle">
                    @if($lote->tipo === 'datos_complejos')
                    <thead><tr><th>Fila</th><th>Municipio</th><th>Indicador</th><th>Año</th><th>Datos</th><th class="text-center">Acción</th></tr></thead>
                    <tbody>
                        @foreach($filas as $fila)
                            <tr>
                                <td>{{ $fila->fila_origen }}</td>
                                <td>{{ $fila->municipio?->nombre }}</td>
                                <td>{{ $fila->indicador?->nombre_amigable }}</td>
                                <td>{{ $fila->anio }}</td>
                                <td>
                                    <details>
                                        <summary class="text-vino fw-semibold">{{ count($fila->datos ?? []) }} categorías</summary>
                                        <pre class="small bg-light border rounded p-2 mt-2 mb-0">{{ json_encode($fila->datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $fila->accion === 'insertar' ? 'success' : 'warning' }}">{{ ucfirst($fila->accion) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @else
                    <thead><tr><th>Fila</th><th>Municipio</th><th>Variable</th><th>Año</th><th class="text-end">Valor</th><th class="text-center">Acción</th></tr></thead>
                    <tbody>
                        @foreach($filas as $fila)
                            <tr>
                                <td>{{ $fila->fila_origen }}</td>
                                <td>{{ $fila->municipio?->nombre }}</td>
                                <td>{{ $fila->variable?->nombre_amigable }}</td>
                                <td>{{ $fila->anio }}</td>
                                <td class="text-end fw-bold">{{ $fila->motivoSinDato?->codigo ?? number_format((float) $fila->valor, 4) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $fila->accion === 'insertar' ? 'success' : 'warning' }}">{{ ucfirst($fila->accion) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @endif
                </table>
            </div>
            @if($filas->hasPages())
                <div class="d-flex justify-content-center mt-3">{{ $filas->links() }}</div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.workflow-confirm').forEach(form => {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const action = this.dataset.action;
        const observations = this.querySelector('[name="observaciones"]');

        if (action === 'reject' && (!observations.value.trim() || observations.value.trim().length < 10)) {
            Swal.fire({
                icon: 'error', title: 'Observaciones requeridas',
                text: 'Escribe un motivo de rechazo de al menos 10 caracteres.',
                confirmButtonColor: '#af1731'
            });
            observations.focus();
            return;
        }

        const settings = {
            submit: { icon: 'question', title: '¿Enviar a revisión?', text: 'El lote quedará pendiente de dictamen.', confirm: 'Enviar', color: '#5f1b2d' },
            approve: { icon: 'question', title: '¿Aprobar y publicar?', text: 'Los datos del lote se publicarán inmediatamente.', confirm: 'Aprobar', color: '#2e7d32' },
            reject: { icon: 'warning', title: '¿Rechazar lote?', text: 'El lote no modificará los datos publicados.', confirm: 'Rechazar', color: '#af1731' }
        }[action];

        Swal.fire({
            icon: settings.icon, title: settings.title, text: settings.text,
            showCancelButton: true, confirmButtonText: settings.confirm,
            cancelButtonText: 'Cancelar', confirmButtonColor: settings.color
        }).then(result => { if (result.isConfirmed) this.submit(); });
    });
});
</script>
@endpush
@endsection
