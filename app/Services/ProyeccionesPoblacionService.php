<?php

namespace App\Services;

use App\Models\DatoHistorico;
use App\Models\ConfiguracionFicha;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\LoteDatos;
use App\Models\LoteDatoHistorico;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Output\OutputInterface;

class ProyeccionesPoblacionService
{
    private const DIMENSION_ID = 1;
    private const TEMATICA = 'proyecciones_demograficas';
    private const INDICADOR = 'proyecciones_poblacion_municipal_1990_2040';

    private const AGE_GROUPS = [
        '00_04' => '0 a 4 años',
        '05_09' => '5 a 9 años',
        '10_14' => '10 a 14 años',
        '15_19' => '15 a 19 años',
        '20_24' => '20 a 24 años',
        '25_29' => '25 a 29 años',
        '30_34' => '30 a 34 años',
        '35_39' => '35 a 39 años',
        '40_44' => '40 a 44 años',
        '45_49' => '45 a 49 años',
        '50_54' => '50 a 54 años',
        '55_59' => '55 a 59 años',
        '60_64' => '60 a 64 años',
        '65_69' => '65 a 69 años',
        '70_74' => '70 a 74 años',
        '75_79' => '75 a 79 años',
        '80_84' => '80 a 84 años',
        '85_mm' => '85 años y más',
    ];

    public function ensureCatalog(): array
    {
        $dimension = Dimension::findOrFail(self::DIMENSION_ID);
        if ($dimension->nombre !== 'Demográfica y Social') {
            throw new \RuntimeException("La dimensión #1 no es 'Demográfica y Social'.");
        }

        $tematica = Tematica::updateOrCreate(
            ['dimension_id' => $dimension->id, 'nombre_tecnico' => self::TEMATICA],
            [
                'nombre' => 'Proyecciones demográficas',
                'orden' => 99,
                'visible_en_ficha' => false,
            ],
        );
        $indicador = Indicador::updateOrCreate(
            ['nombre_tecnico' => self::INDICADOR],
            [
                'tematica_id' => $tematica->id,
                'nombre_amigable' => 'Proyecciones de población municipal 1990-2040',
                'descripcion' => 'Reconstrucción y proyección de la población municipal a mitad de año entre 1990 y 2040.',
                'fuente' => 'SGCONAPO (2024). Reconstrucción y proyecciones de la población de los municipios de México, 1990-2040.',
                'tipo_dato' => 'absoluto',
                'metodo_calculo' => 'Datos de población por grupo quinquenal, sexo y año.',
                'visible_en_ficha' => false,
                'solo_resumen' => false,
                'es_complejo' => false,
                'priorizar_total' => false,
                'orden' => 99,
            ],
        );

        $variables = ['HOMBRES' => [], 'MUJERES' => []];
        foreach ($variables as $sex => &$sexVariables) {
            $sexKey = strtolower($sex);
            foreach (self::AGE_GROUPS as $sourceKey => $label) {
                $sexVariables[$sourceKey] = $this->upsertVariable(
                    $indicador,
                    "proyeccion_poblacion_{$sexKey}_{$sourceKey}",
                    "Población proyectada {$sexKey} de {$label}",
                    count($sexVariables),
                );
            }
            $sexVariables['TOTAL'] = $this->upsertVariable(
                $indicador,
                "proyeccion_poblacion_{$sexKey}_total",
                "Población proyectada {$sexKey} total",
                count($sexVariables),
            );
        }
        unset($sexVariables);

        $total = $this->upsertVariable(
            $indicador,
            'proyeccion_poblacion_total',
            'Población proyectada total',
            99,
            true,
            [
                'variable_ids' => [$variables['HOMBRES']['TOTAL']->id, $variables['MUJERES']['TOTAL']->id],
            ],
        );

        return compact('dimension', 'tematica', 'indicador', 'variables', 'total');
    }

    public function import(string $path, User $user, ?OutputInterface $output = null): LoteDatos
    {
        if (!is_file($path)) {
            throw new \RuntimeException("No existe el archivo: {$path}");
        }

        $catalog = $this->ensureCatalog();
        $municipios = Municipio::pluck('id', 'cvegeo')->mapWithKeys(
            fn ($id, $cvegeo) => [(string) $cvegeo => (int) $id]
        );
        $sourceVariableIds = collect($catalog['variables'])
            ->flatten()
            ->pluck('id')
            ->all();
        $existing = DatoHistorico::whereIn('variable_id', $sourceVariableIds)
            ->get(['municipio_id', 'variable_id', 'anio', 'valor', 'motivo_sin_dato_id', 'updated_at'])
            ->keyBy(fn ($row) => "{$row->municipio_id}:{$row->variable_id}:{$row->anio}");

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();
        $headers = array_map(
            fn ($value) => strtoupper(trim((string) $value)),
            $sheet->rangeToArray('A1:Y1', null, true, true, false)[0],
        );
        $headerIndex = array_flip($headers);
        foreach (['CLAVE', 'SEXO', 'AÑO', 'POB_TOTAL'] as $required) {
            if (!isset($headerIndex[$required])) {
                throw new \RuntimeException("Falta la columna {$required}.");
            }
        }

        $lote = LoteDatos::create([
            'tipo' => 'datos_historicos',
            'estado' => LoteDatos::BORRADOR,
            'archivo_original' => basename($path),
            'archivo_path' => $path,
            'archivo_hash' => hash_file('sha256', $path),
            'usuario_carga_id' => $user->id,
        ]);

        $buffer = [];
        $totalRows = 0;
        $insertRows = 0;
        $updateRows = 0;
        $errors = [];
        $now = now();

        foreach ($sheet->getRowIterator(2) as $row) {
            $values = [];
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            foreach ($cells as $cell) {
                $values[$cell->getColumn()] = $cell->getValue();
            }

            $sourceRow = $row->getRowIndex();
            $cvegeo = $this->normalizeCvegeo($values[$this->column($headerIndex, 'CLAVE')] ?? null);
            $sex = strtoupper(trim((string) ($values[$this->column($headerIndex, 'SEXO')] ?? '')));
            $year = (int) ($values[$this->column($headerIndex, 'AÑO')] ?? 0);
            $municipioId = $municipios[$cvegeo] ?? null;

            if (!$municipioId || !isset($catalog['variables'][$sex]) || $year < 1990 || $year > 2040) {
                $errors[] = "Fila {$sourceRow}: municipio, sexo o año inválido.";
                if (count($errors) >= 20) break;
                continue;
            }

            $sourceMap = self::AGE_GROUPS + ['TOTAL' => ''];
            foreach ($sourceMap as $sourceKey => $unused) {
                $sourceColumn = $sourceKey === 'TOTAL' ? 'POB_TOTAL' : strtoupper('POB_' . $sourceKey);
                $value = $values[$this->column($headerIndex, $sourceColumn)] ?? null;
                if ($value === null || $value === '') continue;
                if (!is_numeric($value)) {
                    $errors[] = "Fila {$sourceRow}: {$sourceColumn} no es numérico.";
                    break;
                }

                $variable = $catalog['variables'][$sex][$sourceKey];
                $key = "{$municipioId}:{$variable->id}:{$year}";
                $original = $existing->get($key);
                $buffer[] = [
                    'lote_datos_id' => $lote->id,
                    'fila_origen' => $sourceRow,
                    'municipio_id' => $municipioId,
                    'variable_id' => $variable->id,
                    'anio' => $year,
                    'valor' => (float) $value,
                    'motivo_sin_dato_id' => null,
                    'accion' => $original ? 'actualizar' : 'insertar',
                    'valor_original' => $original?->valor,
                    'motivo_sin_dato_original_id' => $original?->motivo_sin_dato_id,
                    'dato_historico_updated_at' => $original?->updated_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalRows++;
                $original ? $updateRows++ : $insertRows++;

                if (count($buffer) >= 1000) {
                    DB::table('lote_dato_historicos')->insert($buffer);
                    $buffer = [];
                    $output?->write('.');
                }
            }
        }

        if ($buffer) DB::table('lote_dato_historicos')->insert($buffer);
        if ($errors) {
            $lote->delete();
            throw new \RuntimeException(implode(PHP_EOL, $errors));
        }

        $lote->update([
            'total_filas' => $totalRows,
            'filas_insertar' => $insertRows,
            'filas_actualizar' => $updateRows,
        ]);

        return $lote;
    }

    public function approve(LoteDatos $lote, User $reviewer): void
    {
        DB::transaction(function () use ($lote, $reviewer) {
            $locked = LoteDatos::lockForUpdate()->findOrFail($lote->id);
            if ($locked->estado !== LoteDatos::EN_REVISION) {
                throw new \RuntimeException('El lote debe estar en revisión.');
            }

            LoteDatoHistorico::where('lote_datos_id', $locked->id)
                ->orderBy('id')
                ->chunkById(1000, function ($rows) use ($locked) {
                    DatoHistorico::upsert(
                        $rows->map(fn ($row) => [
                            'municipio_id' => $row->municipio_id,
                            'variable_id' => $row->variable_id,
                            'anio' => $row->anio,
                            'valor' => $row->valor,
                            'motivo_sin_dato_id' => null,
                            'lote_datos_id' => $locked->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])->all(),
                        ['municipio_id', 'variable_id', 'anio'],
                        ['valor', 'motivo_sin_dato_id', 'lote_datos_id', 'updated_at'],
                    );
                });

            $locked->update([
                'estado' => LoteDatos::APROBADO,
                'usuario_revision_id' => $reviewer->id,
                'revisado_at' => now(),
                'aplicado_at' => now(),
            ]);
        });
    }

    public function generateTotal(array $catalog, User $user): int
    {
        $totalVariable = $catalog['total'];
        $sexIds = [
            $catalog['variables']['HOMBRES']['TOTAL']->id,
            $catalog['variables']['MUJERES']['TOTAL']->id,
        ];
        $rows = DB::table('dato_historicos')
            ->whereIn('variable_id', $sexIds)
            ->select('municipio_id', 'anio', DB::raw('SUM(valor) as valor'))
            ->groupBy('municipio_id', 'anio')
            ->get();
        $lote = LoteDatos::create([
            'tipo' => 'construido',
            'estado' => LoteDatos::APROBADO,
            'archivo_original' => 'Proyecciones19902040Puebla',
            'archivo_path' => 'generated://proyeccion_poblacion_total',
            'usuario_carga_id' => $user->id,
            'total_filas' => $rows->count(),
            'filas_insertar' => $rows->count(),
        ]);

        $rows->chunk(1000)->each(function ($chunk) use ($totalVariable, $lote) {
            DatoHistorico::upsert(
                $chunk->map(fn ($row) => [
                    'municipio_id' => $row->municipio_id,
                    'variable_id' => $totalVariable->id,
                    'anio' => $row->anio,
                    'valor' => $row->valor,
                    'lote_datos_id' => $lote->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all(),
                ['municipio_id', 'variable_id', 'anio'],
                ['valor', 'lote_datos_id', 'updated_at'],
            );
        });

        return $rows->count();
    }

    public function createFortamunPerCapita(array $catalog, User $user): array
    {
        $indicator = Indicador::where('nombre_tecnico', 'recursos_devengados_del_fortamun_per_capita')->firstOrFail();
        $numerator = Variable::where('nombre_amigable', 'FORTAMUN DEVENGADO')->firstOrFail();
        $variable = $this->upsertVariable(
            $indicator,
            'fortamun_devengado_per_capita_proyectado',
            'FORTAMUN (Dev) Per Cápita Proyectado',
            2,
            true,
            [
                'numerador_variable_id' => $numerator->id,
                'denominador_variable_id' => $catalog['total']->id,
                'multiplicador' => 1000,
            ],
            'division',
        );
        $variable->update([
            'unidad_medida' => 'Pesos por habitante',
            'visible_en_ficha' => true,
            'es_kpi' => true,
        ]);

        $rows = DB::table('dato_historicos as numerador')
            ->join('dato_historicos as poblacion', function ($join) use ($catalog) {
                $join->on('numerador.municipio_id', '=', 'poblacion.municipio_id')
                    ->on('numerador.anio', '=', 'poblacion.anio')
                    ->where('poblacion.variable_id', $catalog['total']->id);
            })
            ->where('numerador.variable_id', $numerator->id)
            ->whereNotNull('numerador.valor')
            ->whereNotNull('poblacion.valor')
            ->where('poblacion.valor', '>', 0)
            ->select(
                'numerador.municipio_id',
                'numerador.anio',
                DB::raw('ROUND(numerador.valor * 1000 / poblacion.valor, 4) as valor'),
            )
            ->get();

        $lote = LoteDatos::create([
            'tipo' => 'construido',
            'estado' => LoteDatos::APROBADO,
            'archivo_original' => 'Proyecciones19902040Puebla',
            'archivo_path' => 'generated://fortamun_per_capita_proyectado',
            'usuario_carga_id' => $user->id,
            'total_filas' => $rows->count(),
            'filas_insertar' => $rows->count(),
        ]);

        $rows->chunk(1000)->each(function ($chunk) use ($variable, $lote) {
            DatoHistorico::upsert(
                $chunk->map(fn ($row) => [
                    'municipio_id' => $row->municipio_id,
                    'variable_id' => $variable->id,
                    'anio' => $row->anio,
                    'valor' => $row->valor,
                    'lote_datos_id' => $lote->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all(),
                ['municipio_id', 'variable_id', 'anio'],
                ['valor', 'lote_datos_id', 'updated_at'],
            );
        });

        $config = ConfiguracionFicha::where('titulo_reporte', 'Recursos FORTAMUN per cápita')->firstOrFail();
        $config->variables()->sync([$variable->id]);

        return ['variable' => $variable, 'filas' => $rows->count()];
    }

    private function upsertVariable(Indicador $indicador, string $technical, string $name, int $order, bool $constructed = false, ?array $formula = null, ?string $formulaType = null): Variable
    {
        return Variable::updateOrCreate(
            ['nombre_tecnico' => $technical],
            [
                'indicador_id' => $indicador->id,
                'nombre_amigable' => $name,
                'unidad_medida' => 'Habitantes',
                'orden' => $order,
                'visible_en_ficha' => false,
                'es_kpi' => false,
                'es_destacada' => false,
                'es_construida' => $constructed,
                'formula_tipo' => $constructed ? ($formulaType ?? 'sumatoria') : null,
                'formula_config' => $formula,
            ],
        );
    }

    private function column(array $headers, string $name): string
    {
        $index = $headers[$name] ?? null;
        if ($index === null) throw new \RuntimeException("Falta la columna {$name}.");
        return chr(65 + $index);
    }

    private function normalizeCvegeo(mixed $value): string
    {
        $value = trim((string) $value);
        if (str_ends_with($value, '.0')) $value = substr($value, 0, -2);
        return str_pad($value, 5, '0', STR_PAD_LEFT);
    }
}
