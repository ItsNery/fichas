<?php

namespace App\Services;

use App\Models\DatoHistorico;
use App\Models\LoteDatos;
use App\Models\Variable;
use Illuminate\Support\Facades\DB;

class IndicadorConstruidoService
{
    public function calcularPrevisualizacion(Variable $variable): array
    {
        $config = $variable->formula_config;

        if ($variable->formula_tipo === 'tasa_crecimiento') {
            $variableId = $config['variable_id'];
            $mult = (float) ($config['multiplicador'] ?? 100);
            return $this->calcularTasaCrecimiento($variableId, $mult);
        }

        if ($variable->formula_tipo === 'sumatoria') {
            return $this->calcularSumatoria($config['variable_ids'] ?? []);
        }

        $numVarId = $config['numerador_variable_id'];
        $denVarId = $config['denominador_variable_id'];
        $mult = (float) ($config['multiplicador'] ?? 1);
        return $this->calcularDivision($numVarId, $denVarId, $mult);
    }

    public function generarDatosHistoricos(Variable $variable, ?LoteDatos $lote = null): int
    {
        $preview = $this->calcularPrevisualizacion($variable);
        $outputVarId = $variable->id;
        $count = 0;

        foreach ($preview as $row) {
            DatoHistorico::updateOrCreate(
                [
                    'municipio_id' => $row['municipio_id'],
                    'variable_id'  => $outputVarId,
                    'anio'         => $row['anio'],
                ],
                [
                    'valor'         => $row['valor'],
                    'lote_datos_id' => $lote?->id,
                ]
            );
            $count++;
        }

        return $count;
    }

    public function regenerar(Variable $variable): int
    {
        DatoHistorico::where('variable_id', $variable->id)
            ->whereNotNull('lote_datos_id')
            ->delete();

        $lote = $this->crearLote($variable, 'regenerado');
        return $this->generarDatosHistoricos($variable, $lote);
    }

    public function crearLote(Variable $variable, string $accion = 'generado'): LoteDatos
    {
        return LoteDatos::create([
            'tipo'             => 'construido',
            'estado'           => LoteDatos::APROBADO,
            'usuario_carga_id' => auth()->id(),
            'observaciones'    => "Datos {$accion}s automáticamente de la variable construida #{$variable->id} ({$variable->nombre_amigable})",
            'archivo_original' => '',
            'archivo_path'     => '',
        ]);
    }

    private function calcularDivision(int $numVarId, int $denVarId, float $mult): array
    {
        $rows = DB::table('dato_historicos as dh_num')
            ->join('dato_historicos as dh_den', function ($j) {
                $j->on('dh_num.municipio_id', '=', 'dh_den.municipio_id')
                  ->on('dh_num.anio', '=', 'dh_den.anio');
            })
            ->join('municipios', 'dh_num.municipio_id', '=', 'municipios.id')
            ->where('dh_num.variable_id', $numVarId)
            ->where('dh_den.variable_id', $denVarId)
            ->whereNotNull('dh_num.valor')
            ->whereNotNull('dh_den.valor')
            ->where('dh_den.valor', '!=', 0)
            ->select(
                'dh_num.municipio_id',
                'municipios.nombre as municipio',
                'dh_num.anio',
                'dh_num.valor as valor_numerador',
                'dh_den.valor as valor_denominador',
                DB::raw("ROUND(dh_num.valor / dh_den.valor * {$mult}, 4) as valor")
            )
            ->orderBy('municipios.nombre')
            ->orderBy('dh_num.anio')
            ->get()
            ->toArray();

        return json_decode(json_encode($rows), true);
    }

    private function calcularTasaCrecimiento(int $variableId, float $mult): array
    {
        $rows = DB::table('dato_historicos as dh_actual')
            ->join('dato_historicos as dh_anterior', function ($j) {
                $j->on('dh_actual.municipio_id', '=', 'dh_anterior.municipio_id')
                  ->on(DB::raw('dh_actual.anio'), '=', DB::raw('dh_anterior.anio + 1'));
            })
            ->join('municipios', 'dh_actual.municipio_id', '=', 'municipios.id')
            ->where('dh_actual.variable_id', $variableId)
            ->where('dh_anterior.variable_id', $variableId)
            ->whereNotNull('dh_actual.valor')
            ->whereNotNull('dh_anterior.valor')
            ->where('dh_anterior.valor', '!=', 0)
            ->select(
                'dh_actual.municipio_id',
                'municipios.nombre as municipio',
                'dh_actual.anio',
                'dh_anterior.valor as valor_anterior',
                'dh_actual.valor as valor_actual',
                DB::raw("ROUND((dh_actual.valor - dh_anterior.valor) / dh_anterior.valor * {$mult}, 4) as valor")
            )
            ->orderBy('municipios.nombre')
            ->orderBy('dh_actual.anio')
            ->get()
            ->toArray();

        return json_decode(json_encode($rows), true);
    }

    private function calcularSumatoria(array $variableIds): array
    {
        $variableIds = collect($variableIds)->filter()->map(fn($id) => (int) $id)->unique()->values();
        if ($variableIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('dato_historicos as dh')
            ->join('municipios', 'dh.municipio_id', '=', 'municipios.id')
            ->whereIn('dh.variable_id', $variableIds)
            ->whereNotNull('dh.valor')
            ->select(
                'dh.municipio_id',
                'municipios.nombre as municipio',
                'dh.anio',
                DB::raw('SUM(dh.valor) as valor')
            )
            ->groupBy('dh.municipio_id', 'municipios.nombre', 'dh.anio')
            ->orderBy('municipios.nombre')
            ->orderBy('dh.anio')
            ->get()
            ->toArray();

        return json_decode(json_encode($rows), true);
    }
}
