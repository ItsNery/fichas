<?php

namespace App\Services;

use App\Models\DatoHistorico;
use App\Models\Indicador;
use App\Models\Variable;
use Illuminate\Support\Facades\DB;

/**
 * Diagnósticos del panel "Salud de los datos".
 */
class DataHealthService
{
    public const UMBRAL_ATIPICOS = 1000;

    public const LIMITE_CASOS_FUERA_DE_RANGO = 100;

    /**
     * Periodicidades declarables para un indicador.
     */
    public const PERIODICIDADES = Indicador::PERIODICIDADES;

    /**
     * Tolerancia por defecto (en años) para series cuya cadencia no se puede inferir.
     */
    public const TOLERANCIA_IRREGULAR = 3;

    public function resumen(): array
    {
        $ultimoAnio = (int) DatoHistorico::max('anio');

        return [
            'latestYear' => $ultimoAnio,
            'indicadoresVacios' => Indicador::doesntHave('variables')->get(),
            'polaridadResumen' => Indicador::select('polaridad', DB::raw('COUNT(*) as total'))
                ->groupBy('polaridad')
                ->pluck('total', 'polaridad'),
            'indicadoresSinPolaridad' => Indicador::whereNull('polaridad')->orWhere('polaridad', '')->count(),
            'coberturaTemporal' => $this->coberturaTemporal($ultimoAnio),
            'datosAtipicos' => $this->datosAtipicos(),
            'threshold' => self::UMBRAL_ATIPICOS,
            'variablesParaAsignar' => $this->variablesParaAsignar(),
            'celdasSinMotivo' => $this->celdasSinMotivo(),
            'fueraDeRango' => $this->valoresFueraDeRango(),
        ];
    }

    /**
     * Cobertura temporal de cada indicador: contra su propia cadencia y no
     * contra un corte global, para no marcar como rezagados a las series
     * quinquenales (Censo) que simplemente no tienen todavía el nuevo corte.
     *
     * @return array{resumen: array<string, int>, filas: array<int, array<string, mixed>>}
     */
    private function coberturaTemporal(int $ultimoAnio): array
    {
        // Años con datos por variable: un solo recorrido ligero (variable, año)
        // en lugar de unir las 184 mil filas con el catálogo.
        $aniosPorVariable = DB::table('dato_historicos')
            ->select('variable_id', 'anio')
            ->distinct()
            ->orderBy('variable_id')
            ->orderBy('anio')
            ->get();

        $indicadorDeVariable = Variable::get(['id', 'indicador_id'])
            ->mapWithKeys(fn (Variable $variable) => [$variable->id => $variable->indicador_id]);

        $aniosPorIndicador = [];

        foreach ($aniosPorVariable as $fila) {
            $indicadorId = $indicadorDeVariable[(int) $fila->variable_id] ?? null;

            if (! $indicadorId) {
                continue;
            }

            $aniosPorIndicador[$indicadorId][(int) $fila->anio] = true;
        }

        $indicadores = Indicador::has('variables')->get();

        $filas = [];

        // Primera pasada: cadencias de los indicadores con dos o más años de serie.
        $cadenciasPorCorte = [];

        foreach ($indicadores as $indicador) {
            $anios = array_keys($aniosPorIndicador[$indicador->id] ?? []);
            sort($anios);

            $cadencia = $this->inferirCadencia($anios, $indicador->periodicidad);

            if (count($anios) >= 2 && $cadencia['intervalo']) {
                $cadenciasPorCorte[max($anios)][] = $cadencia['intervalo'];
            }
        }

        // Segunda pasada: se completan las cadencias de las series de un solo año
        // con la que se observa en los indicadores que comparten su último corte.
        foreach ($indicadores as $indicador) {
            $anios = array_keys($aniosPorIndicador[$indicador->id] ?? []);
            sort($anios);

            $cadencia = $this->inferirCadencia($anios, $indicador->periodicidad);

            if (count($anios) < 2 && ! $indicador->periodicidad && $anios) {
                $corte = max($anios);
                $intervalo = $this->intervaloMasFrecuente($cadenciasPorCorte[$corte] ?? []);

                if ($intervalo) {
                    $cadencia = [
                        'intervalo' => $intervalo,
                        'etiqueta' => $this->etiquetaCadencia($intervalo),
                    ];
                }
            }

            $intervalo = $cadencia['intervalo'];
            $ultimo = $anios ? max($anios) : null;

            if ($ultimo === null) {
                $filas[] = [
                    'indicador_id' => $indicador->id,
                    'indicador' => $indicador->nombre_amigable,
                    'tipo_dato' => $indicador->tipo_dato,
                    'periodicidad' => $indicador->periodicidad,
                    'cadencia' => 'Sin datos',
                    'anios' => 0,
                    'primero' => null,
                    'ultimo' => null,
                    'esperado' => $ultimoAnio ?: null,
                    'retraso' => null,
                    'estado' => 'sin_datos',
                ];
                continue;
            }

            $esperado = $this->corteEsperado($anios, $intervalo, $ultimoAnio);
            $retraso = $esperado - $ultimo;
            $tolerancia = $intervalo ?? self::TOLERANCIA_IRREGULAR;

            $estado = match (true) {
                $retraso <= 0 => 'al_dia',
                $retraso <= $tolerancia => 'pendiente',
                default => 'atrasado',
            };

            $filas[] = [
                'indicador_id' => $indicador->id,
                'indicador' => $indicador->nombre_amigable,
                'tipo_dato' => $indicador->tipo_dato,
                'periodicidad' => $indicador->periodicidad,
                'cadencia' => $cadencia['etiqueta'],
                'anios' => count($anios),
                'primero' => min($anios),
                'ultimo' => $ultimo,
                'esperado' => $esperado,
                'retraso' => $retraso,
                'estado' => $estado,
            ];
        }

        usort($filas, function (array $a, array $b) {
            $orden = ['atrasado' => 0, 'sin_datos' => 1, 'pendiente' => 2, 'al_dia' => 3];

            return [$orden[$a['estado']] ?? 9, $b['retraso'] ?? 0] <=> [$orden[$b['estado']] ?? 9, $a['retraso'] ?? 0];
        });

        $resumen = [
            'al_dia' => count(array_filter($filas, fn ($f) => $f['estado'] === 'al_dia')),
            'pendiente' => count(array_filter($filas, fn ($f) => $f['estado'] === 'pendiente')),
            'atrasado' => count(array_filter($filas, fn ($f) => $f['estado'] === 'atrasado')),
            'sin_datos' => count(array_filter($filas, fn ($f) => $f['estado'] === 'sin_datos')),
        ];

        return ['resumen' => $resumen, 'filas' => $filas];
    }

    /**
     * Cadencia de la serie: la declarada por el administrador si existe, si no
     * la que se infiere de los años presentes.
     *
     * @param  array<int, int>  $anios
     * @return array{intervalo: ?int, etiqueta: string}
     */
    private function inferirCadencia(array $anios, ?string $periodicidad): array
    {
        if ($periodicidad && array_key_exists($periodicidad, self::PERIODICIDADES)) {
            $definicion = self::PERIODICIDADES[$periodicidad];

            return [
                'intervalo' => $definicion['intervalo'],
                'etiqueta' => $definicion['etiqueta'],
            ];
        }

        if (count($anios) < 2) {
            return [
                'intervalo' => null,
                'etiqueta' => self::PERIODICIDADES['Irregular']['etiqueta'],
            ];
        }

        $diferencias = [];

        for ($i = 1; $i < count($anios); $i++) {
            $diferencias[] = $anios[$i] - $anios[$i - 1];
        }

        $intervalo = min($diferencias);

        if ($intervalo <= 0) {
            return ['intervalo' => null, 'etiqueta' => self::PERIODICIDADES['Irregular']['etiqueta']];
        }

        return [
            'intervalo' => $intervalo,
            'etiqueta' => $this->etiquetaCadencia($intervalo),
        ];
    }

    /**
     * @param  array<int, int>  $intervalos
     */
    private function intervaloMasFrecuente(array $intervalos): ?int
    {
        if (empty($intervalos)) {
            return null;
        }

        $conteo = array_count_values($intervalos);
        arsort($conteo);

        return (int) array_key_first($conteo);
    }

    private function etiquetaCadencia(int $intervalo): string
    {
        foreach (self::PERIODICIDADES as $definicion) {
            if ($definicion['intervalo'] === $intervalo) {
                return $definicion['etiqueta'];
            }
        }

        return 'Cada ' . $intervalo . ' años';
    }

    /**
     * Corte más reciente que le corresponde a la serie según su cadencia.
     *
     * @param  array<int, int>  $anios
     */
    private function corteEsperado(array $anios, ?int $intervalo, int $ultimoAnio): int
    {
        if (! $ultimoAnio) {
            return $anios ? max($anios) : 0;
        }

        if (! $intervalo) {
            return $ultimoAnio;
        }

        $referencia = min($anios);
        $desfase = ($ultimoAnio - $referencia) % $intervalo;
        $corte = $ultimoAnio - $desfase;

        return max($corte, max($anios));
    }

    /**
     * Variación superior al umbral respecto del año anterior.
     */
    private function datosAtipicos(): array
    {
        return DB::select("
            SELECT
                actual.dato_id, actual.anio, actual.valor as valor_actual,
                actual.valor_anterior,
                actual.variable_id, actual.municipio_id,
                v.nombre_amigable as variable_nombre,
                m.nombre as municipio_nombre
            FROM (
                SELECT
                    dh.id as dato_id,
                    dh.anio,
                    dh.valor,
                    dh.variable_id,
                    dh.municipio_id,
                    LAG(dh.valor) OVER (PARTITION BY dh.variable_id, dh.municipio_id ORDER BY dh.anio) as valor_anterior,
                    LAG(dh.anio) OVER (PARTITION BY dh.variable_id, dh.municipio_id ORDER BY dh.anio) as anio_anterior
                FROM dato_historicos dh
                WHERE dh.valor IS NOT NULL
            ) AS actual
            JOIN variables AS v ON actual.variable_id = v.id
            JOIN municipios AS m ON actual.municipio_id = m.id
            WHERE actual.valor_anterior IS NOT NULL
                AND actual.anio_anterior = actual.anio - 1
                AND actual.valor_anterior > 0
                AND (((actual.valor - actual.valor_anterior) / actual.valor_anterior) * 100) > ?
        ", [self::UMBRAL_ATIPICOS]);
    }

    /**
     * Variables disponibles para asignar a un indicador, con su indicador actual
     * para que el administrador vea si está moviéndola de otro lado.
     */
    private function variablesParaAsignar(): array
    {
        return Variable::with('indicador')
            ->orderBy('nombre_amigable')
            ->get()
            ->map(fn (Variable $variable) => [
                'id' => $variable->id,
                'nombre_amigable' => $variable->nombre_amigable,
                'indicador_actual' => $variable->indicador?->nombre_amigable,
            ])
            ->values()
            ->all();
    }

    /**
     * Celdas sin valor y sin motivo de "sin dato": huecos sin explicación.
     */
    private function celdasSinMotivo(): array
    {
        return DB::table('dato_historicos')
            ->join('variables', 'variables.id', '=', 'dato_historicos.variable_id')
            ->leftJoin('indicadors', 'indicadors.id', '=', 'variables.indicador_id')
            ->whereNull('dato_historicos.valor')
            ->whereNull('dato_historicos.motivo_sin_dato_id')
            ->groupBy('variables.id', 'variables.nombre_amigable', 'indicadors.nombre_amigable')
            ->select(
                'variables.id as variable_id',
                'variables.nombre_amigable as variable',
                'indicadors.nombre_amigable as indicador',
                DB::raw('COUNT(*) as celdas'),
                DB::raw('COUNT(DISTINCT dato_historicos.municipio_id) as municipios'),
                DB::raw('MIN(dato_historicos.anio) as desde'),
                DB::raw('MAX(dato_historicos.anio) as hasta')
            )
            ->orderByDesc('celdas')
            ->get()
            ->map(fn ($fila) => (array) $fila)
            ->all();
    }

    /**
     * Valores que no pueden ser ciertos según el tipo de dato del indicador.
     */
    private function valoresFueraDeRango(): array
    {
        $condiciones = "(indicadors.tipo_dato = 'porcentaje' AND (dato_historicos.valor > 100 OR dato_historicos.valor < 0))"
            . " OR (indicadors.tipo_dato IN ('absoluto', 'tasa', 'indice') AND dato_historicos.valor < 0)";

        $resumen = DB::table('dato_historicos')
            ->join('variables', 'variables.id', '=', 'dato_historicos.variable_id')
            ->join('indicadors', 'indicadors.id', '=', 'variables.indicador_id')
            ->whereRaw($condiciones)
            ->groupBy('indicadors.id', 'indicadors.nombre_amigable', 'indicadors.tipo_dato')
            ->select(
                'indicadors.id as indicador_id',
                'indicadors.nombre_amigable as indicador',
                'indicadors.tipo_dato as tipo_dato',
                DB::raw('COUNT(*) as casos'),
                DB::raw('MIN(dato_historicos.valor) as minimo'),
                DB::raw('MAX(dato_historicos.valor) as maximo')
            )
            ->orderByDesc('casos')
            ->get()
            ->map(fn ($fila) => (array) $fila)
            ->all();

        $casos = DB::table('dato_historicos')
            ->join('variables', 'variables.id', '=', 'dato_historicos.variable_id')
            ->join('indicadors', 'indicadors.id', '=', 'variables.indicador_id')
            ->join('municipios', 'municipios.id', '=', 'dato_historicos.municipio_id')
            ->whereRaw($condiciones)
            ->orderByDesc('dato_historicos.valor')
            ->select(
                'dato_historicos.id as dato_id',
                'dato_historicos.variable_id as variable_id',
                'dato_historicos.municipio_id as municipio_id',
                'dato_historicos.anio',
                'dato_historicos.valor',
                'indicadors.nombre_amigable as indicador',
                'indicadors.tipo_dato as tipo_dato',
                'variables.nombre_amigable as variable',
                'municipios.nombre as municipio'
            )
            ->limit(self::LIMITE_CASOS_FUERA_DE_RANGO)
            ->get()
            ->map(function ($fila) {
                $fila = (array) $fila;

                $fila['regla'] = match (true) {
                    $fila['tipo_dato'] === 'porcentaje' && $fila['valor'] > 100 => 'Porcentaje mayor a 100',
                    $fila['tipo_dato'] === 'porcentaje' && $fila['valor'] < 0 => 'Porcentaje negativo',
                    $fila['tipo_dato'] === 'absoluto' => 'Negativo en un conteo',
                    $fila['tipo_dato'] === 'tasa' => 'Tasa negativa',
                    default => 'Índice negativo',
                };

                return $fila;
            })
            ->all();

        $total = array_sum(array_column($resumen, 'casos'));

        return [
            'resumen' => $resumen,
            'casos' => $casos,
            'total' => $total,
            'limite' => self::LIMITE_CASOS_FUERA_DE_RANGO,
        ];
    }
}
