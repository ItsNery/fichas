@extends('layouts.admin')

@section('title', $formula->nombre)

@section('content')
<x-page-header
    :title="$formula->nombre"
    subtitle="Asigna variables a la fórmula y genera los datos resultantes"
    icon="fa-solid fa-function" />

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>{{ session('error') }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-panel mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Definición</h5>
                            <p class="text-muted small mb-0">{{ $formula->descripcion ?: 'Sin descripción.' }}</p>
                        </div>
                        <a href="{{ route('admin.formulas.edit', $formula) }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                            <i class="fa-solid fa-pen me-1"></i>Editar
                        </a>
                    </div>

                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <code class="font-monospace">{{ $formula->expresion }}</code>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-light border">{{ $formula->ambito_label }}</span>
                        <span class="badge {{ $formula->activa ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $formula->activa ? 'Activa' : 'Inactiva' }}
                        </span>
                        @if($tipoResultado)
                            <span class="badge text-bg-info">Tipo calculado: {{ ucfirst($tipoResultado) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-panel">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-1">Asignar variables</h5>
                    <p class="text-muted small mb-3">
                        Solo aparecen variables del catálogo. Si falta alguna, primero debes crearla y cargar sus datos.
                    </p>

                    <form method="GET" action="{{ route('admin.formulas.show', $formula) }}">
                        @foreach($slots as $slotFormula)
                            <div class="mb-3">
                                <label for="var-{{ $slotFormula['clave'] }}" class="form-label fw-bold text-secondary small">
                                    {{ $slotFormula['clave'] }}{{ $slotFormula['nombre'] ? ': '.$slotFormula['nombre'] : '' }}
                                </label>
                                <select name="asignaciones[{{ $slotFormula['clave'] }}]" id="var-{{ $slotFormula['clave'] }}" class="form-select form-select-sm">
                                    <option value="">-- Sin asignar --</option>
                                    @foreach($variables as $variable)
                                        <option value="{{ $variable->id }}"
                                            @selected((int) ($asignaciones[$slotFormula['clave']] ?? $slotFormula['variable_id'] ?? 0) === $variable->id)>
                                            {{ $variable->nombre_amigable }}
                                            @if($variable->indicador) — {{ $variable->indicador->nombre_amigable }} @endif
                                            @if($variable->indicador?->tipo_dato) ({{ $variable->indicador->tipo_dato }}) @endif
                                            @if(! in_array($variable->id, $variablesConDatos)) (sin datos cargados) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        <div class="mb-3">
                            <label for="anio" class="form-label fw-bold text-secondary small">Año (opcional)</label>
                            <input type="number" name="anio" id="anio" class="form-control form-control-sm" value="{{ request('anio') }}">
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-info">
                                <i class="fa-solid fa-calculator me-1"></i>Ver resultado
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-panel h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-1">Resultado</h5>
                    <p class="text-muted small mb-3">
                        @if($formula->ambito === 'municipal')
                            Un renglón por municipio y año.
                        @else
                            Un renglón por año para el estado de Puebla.
                        @endif
                    </p>

                    @if($error)
                        <div class="alert alert-danger small">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $error }}
                        </div>
                    @endif

                    @if(empty($filas))
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-table fa-2x opacity-50 mb-3"></i>
                            <p class="mb-0 small">Asigna las variables y presiona “Ver resultado”.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped align-middle">
                                <thead>
                                    <tr>
                                        @if($formula->ambito === 'municipal')
                                            <th>Municipio</th>
                                        @endif
                                        <th>Año</th>
                                        @foreach($slots as $slotFormula)
                                            <th>{{ $slotFormula['clave'] }}</th>
                                        @endforeach
                                        <th class="fw-bold">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($filas as $fila)
                                        <tr>
                                            @if($formula->ambito === 'municipal')
                                                <td>{{ $municipios[$fila['municipio_id']] ?? '—' }}</td>
                                            @endif
                                            <td>{{ $fila['anio'] }}</td>
                                            @foreach($slots as $slotFormula)
                                                <td>{{ isset($fila['detalle'][$slotFormula['clave']]) ? number_format((float) $fila['detalle'][$slotFormula['clave']], 2, '.', ',') : '—' }}</td>
                                            @endforeach
                                            <td class="fw-bold text-success">{{ $fila['valor'] === null ? '—' : number_format((float) $fila['valor'], 4, '.', ',') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold text-secondary small mb-2">Generar datos</h6>
                        <p class="text-muted small mb-3">
                            Los resultados se guardan en la variable destino que elijas, con un lote de tipo “construido”.
                        </p>

                        <form method="POST" action="{{ route('admin.formulas.generar', $formula) }}" class="row g-2 align-items-end">
                            @csrf
                            @foreach($slots as $slotFormula)
                                <input type="hidden" name="asignaciones[{{ $slotFormula['clave'] }}]"
                                    value="{{ $asignaciones[$slotFormula['clave']] ?? $slotFormula['variable_id'] ?? '' }}">
                            @endforeach
                            <div class="col-md-8">
                                <label for="variable_resultado_id" class="form-label fw-bold text-secondary small">Variable destino</label>
                                <select name="variable_resultado_id" id="variable_resultado_id" class="form-select" required>
                                    <option value="">-- Selecciona la variable donde se guardará --</option>
                                    @foreach($variables as $variable)
                                        <option value="{{ $variable->id }}">{{ $variable->nombre_amigable }} ({{ $variable->nombre_tecnico }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-custom-primary w-100">
                                    <i class="fa-solid fa-bolt me-1"></i>Generar
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
