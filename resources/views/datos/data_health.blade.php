<x-admin-layout>
    @section('title', 'Salud de los Datos')
    <x-page-header title="Panel de Salud de los Datos" subtitle="Diagnóstico y detección de anomalías en el sistema"
        icon="fa-solid fa-heart-pulse" />
    <div class="container py-4">

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>{{ session('success') }}
            </div>
        @endif

        {{-- 1. HEADER SÓLIDO (Estilo Importaciones) --}}

        {{-- 2. TARJETA PRINCIPAL --}}
        <div class="card-panel">
            <div class="card-body p-4">

                {{-- Navegación de Pestañas (Estilo Limpio) --}}
                <ul class="nav nav-tabs nav-tabs-clean" id="healthTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#indicadores-vacios">
                            Indicadores Vacíos
                            <span
                                class="badge rounded-pill text-bg-secondary ms-1">{{ $indicadoresVacios->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#celdas-sin-motivo">
                            Celdas sin motivo
                            <span class="badge rounded-pill text-bg-secondary ms-1">{{ count($celdasSinMotivo) }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fuera-de-rango">
                            Valores fuera de rango
                            <span class="badge rounded-pill text-bg-secondary ms-1">{{ $fueraDeRango['total'] }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cobertura-temporal">
                            Cobertura temporal
                            <span
                                class="badge rounded-pill text-bg-secondary ms-1">{{ ($coberturaTemporal['resumen']['atrasado'] ?? 0) + ($coberturaTemporal['resumen']['pendiente'] ?? 0) + ($coberturaTemporal['resumen']['sin_datos'] ?? 0) }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#atipicos">
                            Datos Atípicos
                            <span class="badge rounded-pill text-bg-secondary ms-1">{{ count($datosAtipicos) }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#polaridad-dss">
                            Polaridad DSS
                        </button>
                    </li>
                </ul>

                {{-- Contenido --}}
                <div class="tab-content" id="healthTabContent">

                    {{-- TAB 1: Indicadores Vacíos --}}
                    <div class="tab-pane fade show active" id="indicadores-vacios">
                        <div class="alert alert-light border-0 d-flex align-items-center mb-4"
                            style="background-color: #fff8f0; color: var(--color1);">
                            <i class="fa-solid fa-circle-info me-3 fa-lg text-dorado"></i>
                            <div>
                                <strong>¿Qué es esto?</strong>
                                <span class="d-block small opacity-75">Indicadores creados que no tienen ninguna
                                    variable asociada (no mostrarán datos).</span>
                            </div>
                        </div>

                        @if ($indicadoresVacios->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-custom w-100" id="tabla-indicadores-vacios">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 80px;">ID</th>
                                            <th>Indicador</th>
                                            <th style="width: 340px;">Asignar variable</th>
                                            <th class="text-center" style="width: 100px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($indicadoresVacios as $indicador)
                                            <tr>
                                                <td class="text-center fw-bold text-muted">#{{ $indicador->id }}</td>
                                                <td>
                                                    <span
                                                        class="fw-bold text-vino d-block">{{ $indicador->nombre_amigable }}</span>
                                                </td>
                                                <td>
                                                    @can('catalogos.editar')
                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                            data-bs-toggle="modal" data-bs-target="#asignarVariableModal"
                                                            data-indicador-id="{{ $indicador->id }}"
                                                            data-indicador-nombre="{{ $indicador->nombre_amigable }}">
                                                            <i class="fa-solid fa-link me-1"></i>Asignar variable
                                                        </button>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endcan
                                                </td>
                                                <td class="text-center">
                                                    @can('catalogos.ver')
                                                        <a href="{{ route('admin.catalogos.indicadores.editar', $indicador) }}"
                                                            class="btn-icon-square danger text-decoration-none"
                                                            data-bs-toggle="tooltip" title="Ver en catálogo">
                                                            <i class="fa-solid fa-magnifying-glass"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa-solid fa-check-circle text-verde fa-3x mb-3 opacity-50"></i>
                                <h5 class="text-muted">¡Excelente! Todo está conectado correctamente.</h5>
                            </div>
                        @endif
                    </div>

                    {{-- TAB 2: Celdas sin motivo de sin dato --}}
                    <div class="tab-pane fade" id="celdas-sin-motivo">
                        <div class="alert alert-light border-0 d-flex align-items-center mb-4"
                            style="background-color: #fff8f0; color: var(--color1);">
                            <i class="fa-solid fa-circle-info me-3 fa-lg text-dorado"></i>
                            <div>
                                <strong>Diagnóstico</strong>
                                <span class="d-block small opacity-75">Celdas sin valor y sin motivo de "sin dato":
                                    huecos sin explicación.</span>
                            </div>
                        </div>

                        @if (count($celdasSinMotivo) > 0)
                            <div class="table-responsive">
                                <table class="table table-custom w-100" id="tabla-celdas-sin-motivo">
                                    <thead>
                                        <tr>
                                            <th>Variable</th>
                                            <th>Indicador</th>
                                            <th class="text-center">Celdas</th>
                                            <th class="text-center">Municipios</th>
                                            <th class="text-center">Años</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($celdasSinMotivo as $celda)
                                            <tr>
                                                <td>
                                                    <span
                                                        class="fw-bold text-vino d-block">{{ $celda['variable'] }}</span>
                                                </td>
                                                <td class="text-muted small">{{ $celda['indicador'] ?? '—' }}</td>
                                                <td class="text-center fw-bold">{{ number_format($celda['celdas']) }}
                                                </td>
                                                <td class="text-center">{{ $celda['municipios'] }}</td>
                                                <td class="text-center text-muted small">
                                                    {{ $celda['desde'] }}–{{ $celda['hasta'] }}</td>
                                                <td class="text-center">
                                                    @can('datos.ver')
                                                        <a href="{{ route('admin.datos.index', ['variable_id' => $celda['variable_id']]) }}"
                                                            class="btn-icon-square danger text-decoration-none"
                                                            data-bs-toggle="tooltip" title="Ver los datos de la variable">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa-solid fa-check-circle text-verde fa-3x mb-3 opacity-50"></i>
                                <h5 class="text-muted">Todas las celdas vacías tienen motivo de "sin dato".</h5>
                            </div>
                        @endif
                    </div>

                    {{-- TAB 3: Valores fuera de rango --}}
                    <div class="tab-pane fade" id="fuera-de-rango">
                        <div class="alert alert-light border-0 d-flex align-items-center mb-4"
                            style="background-color: #fff5f5; color: var(--color3);">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <div>
                                <strong>Valores que no pueden ser ciertos</strong>
                                <span class="d-block small opacity-75">
                                    Por tipo de dato: un porcentaje debe estar entre 0 y 100, y un conteo, una tasa o un
                                    índice no puede ser negativo.
                                    Un porcentaje negativo puede ser legítimo en tasas de crecimiento, por eso se
                                    reportan para revisión.
                                </span>
                            </div>
                        </div>

                        @if (count($fueraDeRango['resumen']) > 0)
                            <h6 class="text-uppercase text-muted small fw-bold mb-2">Resumen por indicador</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-custom w-100" id="tabla-fuera-rango-resumen">
                                    <thead>
                                        <tr>
                                            <th>Indicador</th>
                                            <th class="text-center">Tipo de dato</th>
                                            <th class="text-center">Casos</th>
                                            <th class="text-end">Mínimo</th>
                                            <th class="text-end">Máximo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fueraDeRango['resumen'] as $resumen)
                                            <tr>
                                                <td class="fw-bold text-vino">{{ $resumen['indicador'] }}</td>
                                                <td class="text-center"><span
                                                        class="badge bg-light text-dark border fw-normal">{{ $resumen['tipo_dato'] }}</span>
                                                </td>
                                                <td class="text-center fw-bold">{{ number_format($resumen['casos']) }}
                                                </td>
                                                <td class="text-end text-muted">
                                                    {{ number_format((float) $resumen['minimo'], 2) }}</td>
                                                <td class="text-end fw-bold text-danger">
                                                    {{ number_format((float) $resumen['maximo'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="text-uppercase text-muted small fw-bold mb-2">
                                Casos más severos
                                <span class="text-lowercase fw-normal">({{ count($fueraDeRango['casos']) }} de
                                    {{ number_format($fueraDeRango['total']) }})</span>
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-custom w-100" id="tabla-fuera-rango-casos">
                                    <thead>
                                        <tr>
                                            <th>Indicador / Variable</th>
                                            <th>Municipio</th>
                                            <th class="text-center">Año</th>
                                            <th class="text-end">Valor</th>
                                            <th>Motivo</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fueraDeRango['casos'] as $caso)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-vino">{{ $caso['indicador'] }}</div>
                                                    <div class="small text-muted">{{ $caso['variable'] }}</div>
                                                </td>
                                                <td>{{ $caso['municipio'] }}</td>
                                                <td class="text-center">{{ $caso['anio'] }}</td>
                                                <td class="text-end fw-bold">
                                                    {{ number_format((float) $caso['valor'], 2) }}</td>
                                                <td><span
                                                        class="badge text-bg-light border text-dark fw-normal">{{ $caso['regla'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @can('datos.ver')
                                                        <a href="{{ route('admin.datos.index', ['variable_id' => $caso['variable_id'], 'municipio_id' => $caso['municipio_id']]) }}"
                                                            class="btn-icon-square danger text-decoration-none"
                                                            data-bs-toggle="tooltip" title="Ver datos del municipio">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa-solid fa-check-circle text-verde fa-3x mb-3 opacity-50"></i>
                                <h5 class="text-muted">Todos los valores están dentro del rango esperado de su tipo de
                                    dato.</h5>
                            </div>
                        @endif
                    </div>

                    {{-- TAB 4: Cobertura temporal --}}
                    <div class="tab-pane fade" id="cobertura-temporal">
                        <div class="alert alert-light border-0 d-flex align-items-center mb-4"
                            style="background-color: #f2f7f5; color: var(--color1);">
                            <i class="fa-solid fa-calendar-check me-3 fa-lg text-verde"></i>
                            <div>
                                <strong>Cobertura temporal por indicador</strong>
                                <span class="d-block small opacity-75">
                                    Cada indicador se evalúa contra su propia cadencia (anual, bienal, quinquenal…) y no
                                    contra un corte global:
                                    una serie quinquenal no está desactualizada por no tener el año más reciente.
                                </span>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            @foreach ([['clave' => 'atrasado', 'label' => 'Atrasados', 'texto' => 'Más de un ciclo sin actualizar', 'clase' => 'text-danger', 'icon' => 'fa-arrow-down'], ['clave' => 'pendiente', 'label' => 'Pendientes', 'texto' => 'Falta el corte más reciente disponible', 'clase' => 'text-warning', 'icon' => 'fa-clock'], ['clave' => 'sin_datos', 'label' => 'Sin datos', 'texto' => 'Con variables pero ningún registro', 'clase' => 'text-danger', 'icon' => 'fa-circle-exclamation'], ['clave' => 'al_dia', 'label' => 'Al día', 'texto' => 'Cobertura completa según su cadencia', 'clase' => 'text-success', 'icon' => 'fa-check']] as $tarjeta)
                                <div class="col-6 col-lg-3">
                                    <div class="border rounded p-3 h-100 bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold {{ $tarjeta['clase'] }}"><i
                                                    class="fa-solid {{ $tarjeta['icon'] }} me-1"></i>{{ $tarjeta['label'] }}</span>
                                            <strong
                                                class="fs-4">{{ $coberturaTemporal['resumen'][$tarjeta['clave']] ?? 0 }}</strong>
                                        </div>
                                        <small class="text-muted">{{ $tarjeta['texto'] }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom w-100" id="tabla-cobertura-temporal">
                                <thead>
                                    <tr>
                                        <th>Indicador</th>
                                        <th class="text-center">Cadencia</th>
                                        <th class="text-center">Último dato</th>
                                        <th class="text-center">Corte esperado</th>
                                        <th class="text-center">Retraso</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Periodicidad declarada</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($coberturaTemporal['filas'] as $fila)
                                        @continue($fila['estado'] === 'al_dia')
                                        <tr>
                                            <td>
                                                <span
                                                    class="fw-bold text-vino d-block">{{ $fila['indicador'] }}</span>
                                                @if ($fila['anios'] > 0)
                                                    <small class="text-muted">{{ $fila['anios'] }} años de serie
                                                        ({{ $fila['primero'] }}–{{ $fila['ultimo'] }})</small>
                                                @endif
                                            </td>
                                            <td class="text-center"><span
                                                    class="badge bg-light text-dark border fw-normal">{{ $fila['cadencia'] }}</span>
                                            </td>
                                            <td class="text-center">{{ $fila['ultimo'] ?? '—' }}</td>
                                            <td class="text-center">{{ $fila['esperado'] ?? '—' }}</td>
                                            <td
                                                class="text-center fw-bold {{ $fila['estado'] === 'atrasado' ? 'text-danger' : 'text-warning' }}">
                                                {{ $fila['retraso'] !== null ? $fila['retraso'] . ' año(s)' : '—' }}
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge fw-normal {{ $fila['estado'] === 'atrasado' ? 'text-bg-danger' : ($fila['estado'] === 'sin_datos' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                                    {{ match ($fila['estado']) {
                                                        'atrasado' => 'Atrasado',
                                                        'sin_datos' => 'Sin datos',
                                                        default => 'Pendiente',
                                                    } }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @can('catalogos.editar')
                                                    <form method="POST"
                                                        action="{{ route('admin.salud-datos.periodicidad', $fila['indicador_id']) }}"
                                                        class="d-flex justify-content-center">
                                                        @csrf
                                                        <select name="periodicidad" class="form-select form-select-sm"
                                                            style="max-width:190px;">
                                                            <option value="">Sin declarar</option>
                                                            @foreach (\App\Models\Indicador::PERIODICIDADES as $valor => $definicion)
                                                                <option value="{{ $valor }}"
                                                                    @selected($fila['periodicidad'] === $valor)>
                                                                    {{ $definicion['etiqueta'] }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-secondary ms-1"
                                                            title="Guardar periodicidad">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span
                                                        class="text-muted small">{{ $fila['periodicidad'] ?? '—' }}</span>
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @canany(['datos.importar', 'catalogos.importar',
                                                    'instrumentos.importar'])
                                                    <a href="{{ route('admin.import.index') }}"
                                                        class="btn-icon-square danger text-decoration-none"
                                                        data-bs-toggle="tooltip" title="Cargar datos nuevos">
                                                        <i class="fa-solid fa-upload"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 4: Atípicos --}}
                    <div class="tab-pane fade" id="atipicos">
                        <div class="alert alert-light border-0 mb-4"
                            style="background-color: #fff5f5; color: var(--color3);">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            Datos con variación superior al <strong>{{ $threshold }}%</strong> respecto al año
                            anterior.
                        </div>

                        @if (!empty($datosAtipicos))
                            <div class="table-responsive">
                                <table class="table table-custom w-100" id="tabla-atipicos">
                                    <thead>
                                        <tr>
                                            <th>Contexto</th>
                                            <th class="text-center">Año</th>
                                            <th class="text-end">Anterior</th>
                                            <th class="text-end">Actual</th>
                                            <th class="text-center">Variación</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($datosAtipicos as $dato)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-vino">{{ $dato->variable_nombre }}</div>
                                                    <div class="small text-muted">{{ $dato->municipio_nombre }}</div>
                                                </td>
                                                <td class="text-center">{{ $dato->anio }}</td>
                                                <td class="text-end text-muted">
                                                    {{ number_format($dato->valor_anterior, 2) }}</td>
                                                <td class="text-end fw-bold">
                                                    {{ number_format($dato->valor_actual, 2) }}</td>
                                                <td class="text-center text-danger fw-bold">
                                                    @php $cambio = (($dato->valor_actual - $dato->valor_anterior) / $dato->valor_anterior) * 100; @endphp
                                                    +{{ number_format($cambio, 0) }}%
                                                </td>
                                                <td class="text-center">
                                                    @can('datos.ver')
                                                        <a href="{{ route('admin.datos.index', ['variable_id' => $dato->variable_id, 'municipio_id' => $dato->municipio_id]) }}"
                                                            class="btn-icon-square danger text-decoration-none"
                                                            data-bs-toggle="tooltip" title="Ver datos del municipio">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa-solid fa-check-circle text-verde fa-3x mb-3 opacity-50"></i>
                                <h5 class="text-muted">No se detectaron anomalías estadísticas graves.</h5>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="polaridad-dss">
                        <div class="alert alert-light border-0 d-flex align-items-center mb-4"
                            style="background-color: #f2f7f5; color: var(--color1);">
                            <i class="fa-solid fa-compass me-3 fa-lg text-verde"></i>
                            <div>
                                <strong>Lectura DSS</strong>
                                <span class="d-block small opacity-75">La polaridad indica cómo interpretar los
                                    cambios: si subir es favorable, si bajar es favorable o si el indicador es
                                    únicamente descriptivo.</span>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            @foreach ([['key' => 'asendente', 'label' => 'Ascendente', 'text' => 'Mayor valor favorable', 'class' => 'text-success', 'icon' => 'fa-arrow-up'], ['key' => 'descendente', 'label' => 'Descendente', 'text' => 'Menor valor favorable', 'class' => 'text-danger', 'icon' => 'fa-arrow-down'], ['key' => 'neutro', 'label' => 'Neutro', 'text' => 'Cambio descriptivo', 'class' => 'text-secondary', 'icon' => 'fa-minus']] as $polaridad)
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100 bg-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold {{ $polaridad['class'] }}"><i
                                                    class="fa-solid {{ $polaridad['icon'] }} me-1"></i>{{ $polaridad['label'] }}</span>
                                            <strong>{{ $polaridadResumen[$polaridad['key']] ?? 0 }}</strong>
                                        </div>
                                        <small class="text-muted">{{ $polaridad['text'] }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($indicadoresSinPolaridad > 0)
                            <div class="alert alert-warning mb-0"><i
                                    class="fa-solid fa-triangle-exclamation me-2"></i>{{ $indicadoresSinPolaridad }}
                                indicadores no tienen polaridad definida.</div>
                        @else
                            <div class="alert alert-success mb-0"><i class="fa-solid fa-check-circle me-2"></i>Todos
                                los indicadores tienen una polaridad registrada.</div>
                        @endif
                        <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Esta lectura
                            no se aplica a visualizaciones de dispersión (scatter), que se interpretan como asociaciones
                            entre variables.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: ASIGNAR VARIABLE A UN INDICADOR --}}
    @can('catalogos.editar')
        <div class="modal fade" id="asignarVariableModal" tabindex="-1" aria-labelledby="asignarVariableModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-vino" id="asignarVariableModalLabel">
                            <i class="fa-solid fa-link me-2 text-dorado"></i>Asignar variable a un indicador
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form method="POST" id="asignarVariableForm" action="">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label for="asignarIndicador" class="form-label fw-bold text-secondary small">Indicador
                                    destino</label>
                                <select name="indicador_id" id="asignarIndicador" class="form-select" required>
                                    @foreach ($indicadoresVacios as $indicadorDisponible)
                                        <option value="{{ $indicadorDisponible->id }}">
                                            {{ $indicadorDisponible->nombre_amigable }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="asignarVariable"
                                    class="form-label fw-bold text-secondary small">Variable</label>
                                <select name="variable_id" id="asignarVariable" class="form-select" required>
                                    <option value="">-- Elige la variable --</option>
                                    @foreach ($variablesParaAsignar as $variableDisponible)
                                        <option value="{{ $variableDisponible['id'] }}"
                                            data-indicador-actual="{{ $variableDisponible['indicador_actual'] ?? '' }}">
                                            {{ $variableDisponible['nombre_amigable'] }}
                                            @if ($variableDisponible['indicador_actual'])
                                                — hoy en «{{ $variableDisponible['indicador_actual'] }}»
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Si la variable ya pertenece a otro indicador, se moverá al que
                                    elijas.</div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 bg-light">
                            <button type="button" class="btn btn-outline-secondary px-4"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-custom-primary px-4">
                                <i class="fa-solid fa-link me-1"></i>Asignar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{-- Scripts para DataTables y Tooltips --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // DataTables Config
            const commonConfig = {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.3.2/i18n/es-MX.json'
                },
                pagingType: 'simple_numbers',
                autoWidth: false,
                width: '100%',
                // Esto pone el buscador arriba a la izquierda y limpio
                dom: '<"d-flex justify-content-between mb-3"f>t<"d-flex justify-content-between mt-3"ip>',
            };

            // Inicializar al cambiar de tab
            const initTable = (id) => {
                if (!$.fn.DataTable.isDataTable(id)) {
                    new DataTable(id, commonConfig);
                }
            };

            const triggerTabList = [].slice.call(document.querySelectorAll('#healthTab button'))
            triggerTabList.forEach(function(triggerEl) {
                triggerEl.addEventListener('shown.bs.tab', function(event) {
                    const target = event.target.getAttribute('data-bs-target');
                    if (target === '#indicadores-vacios') initTable('#tabla-indicadores-vacios');
                    if (target === '#celdas-sin-motivo') initTable('#tabla-celdas-sin-motivo');
                    if (target === '#fuera-de-rango') initTable('#tabla-fuera-rango-resumen');
                    if (target === '#cobertura-temporal') initTable('#tabla-cobertura-temporal');
                    if (target === '#atipicos') initTable('#tabla-atipicos');
                })
            });

            // Inicializar la primera
            initTable('#tabla-indicadores-vacios');

            // Asignar variable a un indicador vacío
            const modalAsignar = document.getElementById('asignarVariableModal');
            const formAsignar = document.getElementById('asignarVariableForm');
            const selectIndicador = document.getElementById('asignarIndicador');
            const selectVariable = document.getElementById('asignarVariable');
            const rutaAsignar = @json(route('admin.catalogos.indicadores.asignar-variable', ['indicador' => '__ID__']));

            if (modalAsignar && formAsignar) {
                modalAsignar.addEventListener('show.bs.modal', function(event) {
                    const boton = event.relatedTarget;
                    const id = boton?.dataset.indicadorId;

                    if (id) selectIndicador.value = id;
                });

                formAsignar.addEventListener('submit', function(event) {
                    const opcion = selectVariable.options[selectVariable.selectedIndex];
                    const actual = opcion?.dataset.indicadorActual;

                    formAsignar.action = rutaAsignar.replace('__ID__', selectIndicador.value);

                    if (!actual) return;

                    event.preventDefault();

                    Swal.fire({
                        icon: 'warning',
                        title: 'La variable ya pertenece a otro indicador',
                        html: `«${opcion.textContent.trim()}» se moverá de «${actual}» al indicador elegido.`,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, mover',
                        cancelButtonText: 'Cancelar',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-custom-primary px-4 me-2',
                            cancelButton: 'btn btn-outline-secondary px-4'
                        }
                    }).then(function(result) {
                        if (result.isConfirmed) formAsignar.submit();
                    });
                });
            }
        });
    </script>
</x-admin-layout>
