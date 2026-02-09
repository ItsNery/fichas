<x-admin-layout>
    @section('title', 'Salud de los Datos')
    <x-page-header
        title="Panel de Salud de los Datos"
        subtitle="Diagnóstico y detección de anomalías en el sistema"
        icon="fa-solid fa-heart-pulse" />
    <div class="container py-4">

        {{-- 1. HEADER SÓLIDO (Estilo Importaciones) --}}

        {{-- 2. TARJETA PRINCIPAL --}}
        <div class="card-panel">
            <div class="card-body p-4">

                {{-- Navegación de Pestañas (Estilo Limpio) --}}
                <ul class="nav nav-tabs nav-tabs-clean" id="healthTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#indicadores-vacios">
                            Indicadores Vacíos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#variables-huerfanas">
                            Variables Huérfanas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#desactualizados">
                            Desactualizados
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#atipicos">
                            Datos Atípicos
                        </button>
                    </li>
                </ul>

                {{-- Contenido --}}
                <div class="tab-content" id="healthTabContent">

                    {{-- TAB 1: Indicadores Vacíos --}}
                    <div class="tab-pane fade show active" id="indicadores-vacios">
                        <div class="alert alert-light border-0 d-flex align-items-center mb-4" style="background-color: #fff8f0; color: var(--color1);">
                            <i class="fa-solid fa-circle-info me-3 fa-lg text-dorado"></i>
                            <div>
                                <strong>¿Qué es esto?</strong>
                                <span class="d-block small opacity-75">Indicadores creados que no tienen ninguna variable asociada (no mostrarán datos).</span>
                            </div>
                        </div>

                        @if($indicadoresVacios->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-custom w-100" id="tabla-indicadores-vacios">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 80px;">ID</th>
                                        <th>Indicador</th>
                                        <th class="text-center" style="width: 100px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($indicadoresVacios as $indicador)
                                    <tr>
                                        <td class="text-center fw-bold text-muted">#{{ $indicador->id }}</td>
                                        <td>
                                            <span class="fw-bold text-vino d-block">{{ $indicador->nombre_amigable }}</span>
                                        </td>
                                        <td class="text-center">
                                            {{-- Botón Estilo Imagen (Cuadrado Outline Rojo/Vino) --}}
                                            <a href="#" class="btn-icon-square danger text-decoration-none"
                                                data-bs-toggle="tooltip" title="Inspeccionar">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </a>
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

                    {{-- TAB 2: Variables Huérfanas --}}
                    <div class="tab-pane fade" id="variables-huerfanas">
                        <div class="alert alert-light border-0 d-flex align-items-center mb-4" style="background-color: #fff8f0; color: var(--color1);">
                            <i class="fa-solid fa-circle-info me-3 fa-lg text-dorado"></i>
                            <div>
                                <strong>Diagnóstico</strong>
                                <span class="d-block small opacity-75">Variables que existen en la base de datos pero no pertenecen a ningún indicador.</span>
                            </div>
                        </div>

                        @if($variablesHuerfanas->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-custom w-100" id="tabla-variables-huerfanas">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th>Variable</th>
                                        <th>Nombre Técnico</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($variablesHuerfanas as $variable)
                                    <tr>
                                        <td class="text-center text-muted">#{{ $variable->id }}</td>
                                        <td class="fw-bold text-vino">{{ $variable->nombre_amigable }}</td>
                                        <td class="text-muted small font-monospace">{{ $variable->nombre_tecnico }}</td>
                                        <td class="text-center">
                                            <a href="#" class="btn-icon-square danger text-decoration-none" data-bs-toggle="tooltip" title="Vincular">
                                                <i class="fa-solid fa-link"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-check-circle text-verde fa-3x mb-3 opacity-50"></i>
                            <h5 class="text-muted">Base de datos limpia de huérfanos.</h5>
                        </div>
                        @endif
                    </div>

                    {{-- TAB 3: Desactualizados --}}
                    <div class="tab-pane fade" id="desactualizados">
                        @if ($latestYear)
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded bg-light">
                            <div>
                                <h6 class="mb-0 text-vino fw-bold">Año de Corte: {{ $latestYear }}</h6>
                                <small class="text-muted">Indicadores sin datos para el año más reciente.</small>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom w-100" id="tabla-desactualizados">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th>Indicador</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($indicadoresDesactualizados as $indicador)
                                    <tr>
                                        <td class="text-center text-muted">#{{ $indicador->id }}</td>
                                        <td class="fw-bold text-dark">{{ $indicador->nombre_amigable }}</td>
                                        <td class="text-center">
                                            <a href="#" class="btn-icon-square danger text-decoration-none" data-bs-toggle="tooltip" title="Actualizar">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-secondary">No hay suficientes datos históricos.</div>
                        @endif
                    </div>

                    {{-- TAB 4: Atípicos --}}
                    <div class="tab-pane fade" id="atipicos">
                        <div class="alert alert-light border-0 mb-4" style="background-color: #fff5f5; color: var(--color3);">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            Datos con variación superior al <strong>{{ $threshold }}%</strong> respecto al año anterior.
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
                                        <td class="text-end text-muted">{{ number_format($dato->valor_anterior, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($dato->valor_actual, 2) }}</td>
                                        <td class="text-center text-danger fw-bold">
                                            @php $cambio = (($dato->valor_actual - $dato->valor_anterior) / $dato->valor_anterior) * 100; @endphp
                                            +{{ number_format($cambio, 0) }}%
                                        </td>
                                        <td class="text-center">
                                            <a href="#" class="btn-icon-square danger text-decoration-none" data-bs-toggle="tooltip" title="Verificar">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
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

                </div>
            </div>
        </div>
    </div>

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
                    if (target === '#variables-huerfanas') initTable('#tabla-variables-huerfanas');
                    if (target === '#desactualizados') initTable('#tabla-desactualizados');
                    if (target === '#atipicos') initTable('#tabla-atipicos');
                })
            });

            // Inicializar la primera
            initTable('#tabla-indicadores-vacios');
        });
    </script>
</x-admin-layout>