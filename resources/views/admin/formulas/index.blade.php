@extends('layouts.admin')

@section('title', 'Fórmulas')

@section('content')
<x-page-header
    title="Catálogo de fórmulas"
    subtitle="Crea fórmulas reutilizables y combínalas con las variables que ya tienen datos"
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

    <div class="card-panel mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-file-import me-2 text-vino"></i>Importar fórmulas existentes
                    </h5>
                    <p class="text-muted small mb-0">
                        Trae al catálogo las fórmulas que ya usan las variables construidas
                        (<code>division</code> y <code>sumatoria</code>). Las variables de origen no se modifican
                        ni se desactivan.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.formulas.importar') }}" class="d-flex gap-2 align-items-center flex-wrap">
                    @csrf
                    <select name="ambito" class="form-select form-select-sm" style="width:auto">
                        <option value="municipal">Ámbito municipal</option>
                        <option value="estatal">Ámbito estatal (Puebla)</option>
                    </select>
                    <button class="btn btn-custom-verde text-nowrap">
                        <i class="fa-solid fa-download me-1"></i>Importar
                    </button>
                </form>
            </div>

            @if(session('omitidas'))
                @php $omitidas = session('omitidas'); @endphp
                @if(count($omitidas) > 0)
                    <div class="alert alert-light border border-start border-4 border-warning mt-3 mb-0">
                        <strong class="d-block mb-2">Fórmulas omitidas ({{ count($omitidas) }})</strong>
                        <ul class="small mb-0">
                            @foreach($omitidas as $omitida)
                                <li>
                                    <strong>{{ $omitida['nombre'] }}</strong>
                                    — {{ $omitida['detalle'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="card-panel">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
                <div>
                    <h5 class="mb-1 fw-bold text-dark">Fórmulas disponibles</h5>
                    <p class="text-muted small mb-0">
                        Una fórmula es una plantilla: define cómo se combinan variables. Las variables concretas
                        se asignan al momento de usarla.
                    </p>
                </div>
                <a href="{{ route('admin.formulas.create') }}" class="btn btn-custom-primary text-nowrap">
                    <i class="fa-solid fa-plus me-1"></i>Nueva fórmula
                </a>
            </div>

            @if($formulas->isEmpty())
                <div class="text-center py-5">
                    <i class="fa-solid fa-function fa-3x text-muted opacity-50 mb-3"></i>
                    <p class="fw-semibold mb-1">Aún no hay fórmulas</p>
                    <p class="text-muted small mb-3">Crea la primera para empezar a combinar variables.</p>
                    <a href="{{ route('admin.formulas.create') }}" class="btn btn-custom-primary">
                        <i class="fa-solid fa-plus me-1"></i>Crear fórmula
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-custom w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Fórmula</th>
                                <th>Expresión</th>
                                <th>Alcance</th>
                                <th>Variables</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($formulas as $formula)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.formulas.show', $formula) }}" class="fw-semibold text-decoration-none">
                                            {{ $formula->nombre }}
                                        </a>
                                        @if($formula->descripcion)
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($formula->descripcion, 70) }}</div>
                                        @endif
                                    </td>
                                    <td><code class="small">{{ $formula->expresion }}</code></td>
                                    <td><span class="badge text-bg-light border">{{ $formula->ambito_label }}</span></td>
                                    <td>
                                        @foreach($formula->slots() as $slotFormula)
                                            <span class="badge rounded-pill text-bg-secondary me-1">
                                                {{ $slotFormula['clave'] }}{{ $slotFormula['nombre'] ? ': '.$slotFormula['nombre'] : '' }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $formula->activa ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $formula->activa ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.formulas.show', $formula) }}" class="btn btn-outline-info" title="Usar">
                                                <i class="fa-solid fa-play"></i>
                                            </a>
                                            <a href="{{ route('admin.formulas.edit', $formula) }}" class="btn btn-outline-secondary" title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form action="{{ route('admin.formulas.destroy', $formula) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('¿Eliminar la fórmula \'{{ $formula->nombre }}\'?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger" title="Eliminar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
