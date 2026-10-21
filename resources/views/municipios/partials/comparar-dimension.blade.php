@php
    $items = $comparativa[$dimensionKey] ?? [];
@endphp

<script type="application/json" class="comparison-section-data">@json($comparativa)</script>

<div class="row g-4 align-items-stretch">
    @forelse ($items as $item)
        @php
            $config = $item['config'];
            $datos1 = $item['datos1'] ?? [];
            $datos2 = $item['datos2'] ?? [];
            $isKpi = $config->tipo_visualizacion === 'kpi';
            $hasCombined = $item['echarts_combinado'] !== null;
            $displayVal1 = $datos1['valor_actual'] ?? ($datos1['total'] ?? 'N/D');
            $displayVal2 = $datos2['valor_actual'] ?? ($datos2['total'] ?? 'N/D');
            $ajustesVisuales = $config->ajustes_visuales;
            $mapping = is_array($ajustesVisuales) ? ($ajustesVisuales['mapping'] ?? null) : null;
            if (!$mapping && !empty($datos1['variables'][0]['mapeo'])) {
                $mapping = $datos1['variables'][0]['mapeo'];
            }
            if (!$mapping && !empty($datos2['variables'][0]['mapeo'])) {
                $mapping = $datos2['variables'][0]['mapeo'];
            }
            if (is_array($mapping)) {
                $displayVal1 = $mapping[(string) ($datos1['total'] ?? '')] ?? $mapping[(int) ($datos1['total'] ?? 0)] ?? $displayVal1;
                $displayVal2 = $mapping[(string) ($datos2['total'] ?? '')] ?? $mapping[(int) ($datos2['total'] ?? 0)] ?? $displayVal2;
            }
            $valor1 = (float) ($datos1['total'] ?? 0);
            $valor2 = (float) ($datos2['total'] ?? 0);
            $esComparacionNumerica = !is_array($mapping) && $valor1 > 0 && $valor2 > 0;
            $diferenciaPorcentual = $esComparacionNumerica ? (($valor1 - $valor2) / $valor2) * 100 : null;
            $polaridad = $config->indicador->polaridad ?? 'neutral';
            $claseDiferencia = 'text-muted';
            if ($esComparacionNumerica && in_array($polaridad, ['ascendente', 'asendente'])) {
                $claseDiferencia = $diferenciaPorcentual > 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
            } elseif ($esComparacionNumerica && $polaridad === 'descendente') {
                $claseDiferencia = $diferenciaPorcentual < 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
            }
        @endphp
        <div class="col-12">
            <div class="perfil-tarjeta">
                <div class="perfil-tarjeta__body">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h3 class="perfil-tarjeta__titulo m-0">
                            {{ $config->titulo_reporte ?? $config->indicador->nombre_amigable }}
                        </h3>
                        @if (isset($datos1['metodo_calculo']) || isset($datos1['fuente']))
                            <i class="fa-solid fa-circle-info info-tooltip-trigger perfil-tarjeta__info-icon"
                                data-bs-toggle="popover" data-bs-trigger="hover focus" title="Metodología y Fuente"
                                data-bs-content="<strong>Método:</strong> {{ $datos1['metodo_calculo'] ?? 'No especificado' }}<br><strong>Fuente:</strong> {{ $datos1['fuente'] ?? 'No especificada' }}"
                                data-bs-html="true"></i>
                        @endif
                    </div>

                    @if ($isKpi)
                        <div class="row g-4 align-items-center justify-content-center my-2">
                            <div class="col-5 text-center border-end">
                                <span class="badge mb-2 text-uppercase" style="background: {{ $dimensionAccent }}; color: #fff;">
                                    {{ $municipio1->nombre }}
                                </span>
                                <h4 class="perfil-tarjeta__kpi-value" style="color: {{ $dimensionAccent }};">{{ $displayVal1 }}</h4>
                                @if (isset($datos1['variables'][0]['unidad']))
                                    <small class="text-muted d-block mt-1">{{ $datos1['variables'][0]['unidad'] }}</small>
                                @endif
                            </div>
                            <div class="col-2 text-center">
                                @if ($esComparacionNumerica)
                                    <div class="comparar-kpi__diff-badge shadow-sm px-2 py-1 rounded"
                                        style="background: {{ $dimensionAccent }}; border-color: {{ $dimensionAccent }};">
                                        <span class="{{ $claseDiferencia }} small">
                                            {{ $diferenciaPorcentual >= 0 ? '+' : '' }}{{ number_format($diferenciaPorcentual, 1) }}%
                                        </span>
                                    </div>
                                @else
                                    <div class="comparar-kpi__vs-mid text-muted small">vs</div>
                                @endif
                            </div>
                            <div class="col-5 text-center border-start">
                                <span class="badge mb-2 text-uppercase text-white" style="background: {{ $dimensionAccent }};">
                                    {{ $municipio2->nombre }}
                                </span>
                                <h4 class="perfil-tarjeta__kpi-value" style="color: {{ $dimensionAccent }};">{{ $displayVal2 }}</h4>
                                @if (isset($datos2['variables'][0]['unidad']))
                                    <small class="text-muted d-block mt-1">{{ $datos2['variables'][0]['unidad'] }}</small>
                                @endif
                            </div>
                        </div>

                        @if (isset($datos1['tendencia']) && count($datos1['tendencia']) > 1 && isset($datos2['tendencia']) && count($datos2['tendencia']) > 1)
                            <div class="row mt-4 pt-3 border-top justify-content-center">
                                <div class="col-5 text-center">
                                    <div class="perfil-tarjeta__sparkline mx-auto" id="sparkline-{{ $config->id }}-1"
                                        data-chart-id="{{ $config->id }}" data-muni="1"></div>
                                </div>
                                <div class="col-2 text-center text-muted small">Tendencia histórica</div>
                                <div class="col-5 text-center">
                                    <div class="perfil-tarjeta__sparkline mx-auto" id="sparkline-{{ $config->id }}-2"
                                        data-chart-id="{{ $config->id }}" data-muni="2"></div>
                                </div>
                            </div>
                        @endif
                    @elseif ($hasCombined)
                        <div class="perfil-tarjeta__chart-wrapper perfil-tarjeta__chart-wrapper--full">
                            <div class="perfil-tarjeta__skeleton" id="skeleton-combined-{{ $config->id }}">
                                <div class="spinner-border perfil-tarjeta__spinner" role="status"><span class="visually-hidden">Cargando gráfico…</span></div>
                            </div>
                            <div class="perfil-tarjeta__chart-box lazy-chart-combined perfil-tarjeta__chart-box--full"
                                id="chart-combined-{{ $config->id }}" data-chart-id="{{ $config->id }}"></div>
                        </div>
                    @else
                        <div class="row g-4 mt-2">
                            @foreach ([1 => $municipio1, 2 => $municipio2] as $municipioNumero => $municipio)
                                <div class="col-md-6 {{ $municipioNumero === 1 ? 'border-end' : '' }}">
                                    <h5 class="text-center small fw-bold text-uppercase mb-3" style="color: {{ $dimensionAccent }};">
                                        {{ $municipio->nombre }}
                                    </h5>
                                    <div class="perfil-tarjeta__chart-wrapper perfil-tarjeta__chart-wrapper--half">
                                        <div class="perfil-tarjeta__skeleton" id="skeleton-sep{{ $municipioNumero }}-{{ $config->id }}">
                                            <div class="spinner-border perfil-tarjeta__spinner" role="status"><span class="visually-hidden">Cargando gráfico…</span></div>
                                        </div>
                                        <div class="perfil-tarjeta__chart-box lazy-chart-sep perfil-tarjeta__chart-box--half"
                                            id="chart-sep{{ $municipioNumero }}-{{ $config->id }}" data-chart-id="{{ $config->id }}"
                                            data-muni="{{ $municipioNumero }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center text-muted py-4">No hay indicadores públicos para esta dimensión.</div>
    @endforelse
</div>
