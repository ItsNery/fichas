<?php

namespace App\Services;

use App\Exceptions\FormulaException;
use App\Models\DatoGeograficoHistorico;
use App\Models\DatoHistorico;
use App\Models\Formula;
use App\Models\LoteDatos;
use App\Models\Municipio;
use App\Models\Variable;
use App\Services\Formula\FormulaCompatibilityChecker;
use App\Services\Formula\FormulaEvaluator;
use App\Services\Formula\FormulaTypeSystem;
use App\Services\Formula\FormulaExpressionParser;
use Illuminate\Support\Facades\DB;

class FormulaService
{
    public function __construct(
        private readonly FormulaExpressionParser $parser,
        private readonly FormulaEvaluator $evaluator,
        private readonly GeographicUnitService $unidades,
        private readonly FormulaCompatibilityChecker $compatibilidad,
    ) {
    }

    /**
     * @return array{arbol: array, slots: array<int, string>}
     */
    public function analizar(string $expresion): array
    {
        return $this->parser->analizar($expresion);
    }

    /**
     * Claves de variables utilizadas en la expresión (A, B, C...).
     *
     * @return array<int, string>
     */
    public function slotsDeExpresion(string $expresion): array
    {
        return $this->analizar($expresion)['slots'];
    }

    /**
     * Previsualiza el resultado de una fórmula.
     *
     * @param  array<string, int|null>  $asignaciones  clave => variable_id
     * @return array<int, array<string, mixed>>
     */
    public function previsualizar(
        Formula $formula,
        array $asignaciones,
        ?int $anio = null,
        ?string $ambito = null
    ): array {
        $analisis = $this->analizar($formula->expresion);
        $ambito = $ambito ?: $formula->ambito;

        $faltantes = collect($analisis['slots'])
            ->reject(fn ($clave) => ! empty($asignaciones[$clave]))
            ->values();

        if ($faltantes->isNotEmpty()) {
            throw new FormulaException(sprintf(
                'Falta asignar las variables: %s.',
                $faltantes->implode(', ')
            ));
        }

        $variableIds = collect($asignaciones)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($variableIds->isEmpty()) {
            throw new FormulaException('No hay variables fuente asignadas.');
        }

        $compatibilidad = $this->validarCompatibilidad($analisis['arbol'], $asignaciones);

        if (! empty($compatibilidad['errores'])) {
            throw new FormulaException(implode(' ', $compatibilidad['errores']));
        }

        $datos = $this->datosMunicipales($variableIds, $anio);
        if ($datos->isEmpty()) {
            throw new FormulaException('Las variables fuente no tienen datos cargados.');
        }

        return $ambito === Formula::AMBITO_MUNICIPAL
            ? $this->previsualizarMunicipal($analisis['arbol'], $asignaciones, $datos)
            : $this->previsualizarEstatal($analisis['arbol'], $asignaciones, $datos);
    }

    /**
     * Genera y guarda los resultados de la fórmula.
     *
     * @param  array<string, int|null>  $asignaciones
     */
    public function generar(Formula $formula, array $asignaciones, Variable $variableResultado): int
    {
        $analisis = $this->analizar($formula->expresion);
        $compatibilidad = $this->validarCompatibilidad($analisis['arbol'], $asignaciones);

        if (! empty($compatibilidad['errores'])) {
            throw new FormulaException(implode(' ', $compatibilidad['errores']));
        }

        $tipoDestino = $variableResultado->indicador?->tipo_dato
            ?? FormulaTypeSystem::DESCONOCIDO;

        if (! FormulaTypeSystem::destinoCompatible($compatibilidad['tipoResultado'], (string) $tipoDestino)) {
            throw new FormulaException(FormulaTypeSystem::explicarDestino(
                $compatibilidad['tipoResultado'],
                (string) $tipoDestino
            ));
        }

        $filas = $this->previsualizar($formula, $asignaciones);

        if (empty($filas)) {
            throw new FormulaException('No hay resultados para generar.');
        }

        $lote = $this->crearLote($formula, $variableResultado);

        if ($formula->ambito === Formula::AMBITO_MUNICIPAL) {
            foreach ($filas as $fila) {
                DatoHistorico::updateOrCreate(
                    [
                        'municipio_id' => $fila['municipio_id'],
                        'variable_id' => $variableResultado->id,
                        'anio' => $fila['anio'],
                    ],
                    [
                        'valor' => $fila['valor'],
                        'lote_datos_id' => $lote->id,
                    ]
                );
            }
        } else {
            $unidad = $this->unidades->forSelection('estatal');

            foreach ($filas as $fila) {
                DatoGeograficoHistorico::updateOrCreate(
                    [
                        'unidad_geografica_id' => $unidad->id,
                        'variable_id' => $variableResultado->id,
                        'anio' => $fila['anio'],
                    ],
                    [
                        'valor' => $fila['valor'],
                        'lote_datos_id' => $lote->id,
                    ]
                );
            }
        }

        $lote->update(['total_filas' => count($filas)]);

        return count($filas);
    }

    public function crearLote(Formula $formula, Variable $variableResultado): LoteDatos
    {
        return LoteDatos::create([
            'tipo' => 'construido',
            'estado' => LoteDatos::APROBADO,
            'usuario_carga_id' => auth()->id(),
            'observaciones' => sprintf(
                'Datos generados automáticamente con la fórmula "%s" (#%d) sobre la variable "%s"',
                $formula->nombre,
                $formula->id,
                $variableResultado->nombre_amigable
            ),
            'archivo_original' => '',
            'archivo_path' => '',
        ]);
    }

    /**
     * Valores municipales cargados para las variables fuente.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $variableIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, float>>>
     *         [variable_id][municipio_id][anio] => valor
     */
    private function datosMunicipales($variableIds, ?int $anio = null)
    {
        return DB::table('dato_historicos')
            ->whereIn('variable_id', $variableIds)
            ->whereNotNull('valor')
            ->when($anio, fn ($query) => $query->where('anio', $anio))
            ->select('variable_id', 'municipio_id', 'anio', 'valor')
            ->get()
            ->groupBy('variable_id')
            ->map(function ($filas) {
                return $filas
                    ->groupBy('municipio_id')
                    ->map(function ($porMunicipio) {
                        return $porMunicipio->mapWithKeys(fn ($fila) => [
                            (int) $fila->anio => (float) $fila->valor,
                        ]);
                    });
            });
    }

    /**
     * @param  array<string, int|null>  $asignaciones
     * @return array<int, array<string, mixed>>
     */
    private function previsualizarEstatal(array $arbol, array $asignaciones, $datos): array
    {
        $mapaClave = $this->mapaVariableClave($asignaciones);
        $filas = [];

        foreach ($this->aniosDisponibles($datos, $mapaClave) as $anio) {
            $acumulados = [];

            foreach ($datos as $variableId => $porMunicipio) {
                $clave = $mapaClave[(int) $variableId] ?? null;

                if ($clave === null) {
                    continue;
                }

                $valores = [];

                foreach ($porMunicipio as $valoresPorAnio) {
                    if (array_key_exists($anio, $valoresPorAnio->all())) {
                        $valores[] = (float) $valoresPorAnio[$anio];
                    }
                }

                $acumulados[$clave] = $valores;
            }

            $valor = $this->evaluator->evaluar(
                $arbol,
                fn (string $clave, ?string $funcion) => $this->aplicarAgregacion($acumulados[$clave] ?? [], $funcion)
            );

            $filas[] = [
                'anio' => $anio,
                'valor' => $valor === null ? null : round($valor, 4),
                'detalle' => array_map(
                    fn (array $valores) => $this->aplicarAgregacion($valores, 'sum'),
                    $acumulados
                ),
            ];
        }

        return $filas;
    }

    /**
     * @param  array<string, int|null>  $asignaciones
     * @return array<int, array<string, mixed>>
     */
    private function previsualizarMunicipal(array $arbol, array $asignaciones, $datos): array
    {
        $mapaClave = $this->mapaVariableClave($asignaciones);
        $combinadas = [];

        foreach ($datos as $variableId => $porMunicipio) {
            $clave = $mapaClave[(int) $variableId] ?? null;

            if ($clave === null) {
                continue;
            }

            foreach ($porMunicipio as $municipioId => $valoresPorAnio) {
                foreach ($valoresPorAnio as $anio => $valor) {
                    $llave = $municipioId . '-' . $anio;
                    $combinadas[$llave] ??= [
                        'municipio_id' => (int) $municipioId,
                        'anio' => (int) $anio,
                        'valores' => [],
                    ];
                    $combinadas[$llave]['valores'][$clave] = (float) $valor;
                }
            }
        }

        $resultado = [];

        foreach ($combinadas as $fila) {
            $valor = $this->evaluator->evaluar(
                $arbol,
                fn (string $clave, ?string $funcion) => $fila['valores'][$clave] ?? null
            );

            $resultado[] = [
                'municipio_id' => $fila['municipio_id'],
                'anio' => $fila['anio'],
                'valor' => $valor === null ? null : round($valor, 4),
                'detalle' => $fila['valores'],
            ];
        }

        usort($resultado, fn ($a, $b) => [$a['municipio_id'], $a['anio']] <=> [$b['municipio_id'], $b['anio']]);

        return $resultado;
    }

    /**
     * @param  array<int, float>  $valores
     */
    private function aplicarAgregacion(array $valores, ?string $funcion): ?float
    {
        if (empty($valores)) {
            return null;
        }

        return match ($funcion) {
            'avg' => array_sum($valores) / count($valores),
            'count' => (float) count($valores),
            'min' => min($valores),
            'max' => max($valores),
            default => array_sum($valores),
        };
    }

    /**
     * @param  array<string, int|null>  $asignaciones
     * @return array<int, string> variable_id => clave
     */
    private function mapaVariableClave(array $asignaciones): array
    {
        $mapa = [];

        foreach ($asignaciones as $clave => $variableId) {
            if (! $variableId) {
                continue;
            }

            $mapa[(int) $variableId] = strtoupper((string) $clave);
        }

        return $mapa;
    }

    /**
     * @param  array<int, string>  $mapaClave
     * @return array<int, int>
     */
    private function aniosDisponibles($datos, array $mapaClave): array
    {
        $anios = [];

        foreach ($datos as $variableId => $porMunicipio) {
            if (! isset($mapaClave[(int) $variableId])) {
                continue;
            }

            foreach ($porMunicipio as $valoresPorAnio) {
                foreach (array_keys($valoresPorAnio->all()) as $anio) {
                    $anios[(int) $anio] = true;
                }
            }
        }

        $anios = array_keys($anios);
        sort($anios);

        return $anios;
    }

    /**
     * Valida que las variables asignadas combinen tipos de dato compatibles.
     *
     * @param  array<string, int|null>  $asignaciones
     * @return array{errores: array<int, string>, tipoResultado: string}
     */
    private function validarCompatibilidad(array $arbol, array $asignaciones): array
    {
        $tiposPorSlot = [];

        foreach ($asignaciones as $clave => $variableId) {
            if (! $variableId) {
                continue;
            }

            $variable = Variable::with('indicador')->find((int) $variableId);
            $tiposPorSlot[strtoupper((string) $clave)] = $variable?->indicador?->tipo_dato
                ?? FormulaTypeSystem::TIPO_DESCONOCIDO;
        }

        return $this->compatibilidad->validar($arbol, $tiposPorSlot);
    }

    /**
     * Tipo de dato que produce la fórmula según las variables asignadas.
     *
     * @param  array<string, int|null>  $asignaciones
     */
    public function tipoResultado(string $expresion, array $asignaciones): string
    {
        $analisis = $this->analizar($expresion);

        return $this->validarCompatibilidad($analisis['arbol'], $asignaciones)['tipoResultado'];
    }

    /**
     * Variables que aún no tienen datos cargados.
     *
     * @param  array<string, int|null>  $asignaciones
     * @return array<int, string>
     */
    public function variablesSinDatos(array $asignaciones): array
    {
        $variableIds = collect($asignaciones)->filter()->map(fn ($id) => (int) $id)->values();

        if ($variableIds->isEmpty()) {
            return [];
        }

        $conDatos = DB::table('dato_historicos')
            ->whereIn('variable_id', $variableIds)
            ->whereNotNull('valor')
            ->distinct()
            ->pluck('variable_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $faltantes = [];

        foreach ($variableIds as $variableId) {
            if (! in_array($variableId, $conDatos, true)) {
                $variable = Variable::find($variableId);
                $faltantes[] = $variable?->nombre_amigable ?? "Variable #{$variableId}";
            }
        }

        return $faltantes;
    }

    public function municipiosDe(array $municipioIds): array
    {
        if (empty($municipioIds)) {
            return [];
        }

        return Municipio::whereIn('id', $municipioIds)->pluck('nombre', 'id')->all();
    }
}
