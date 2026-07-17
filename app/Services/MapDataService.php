<?php

namespace App\Services;

use App\Models\DatoHistorico;
use App\Models\Indicador;
use Illuminate\Support\Facades\DB;

class MapDataService
{
    public function getMapData(Indicador $indicador, $anio): array
    {
        $variables = $indicador->variables->where('visible_en_ficha', true);

        $variableTotal = $variables->first(function ($variable) {
            return str_contains(mb_strtolower($variable->nombre_amigable, 'UTF-8'), 'total');
        });

        $variableIds = $variableTotal ? [$variableTotal->id] : $variables->pluck('id')->all();

        return DatoHistorico::whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->join('municipios', 'dato_historicos.municipio_id', '=', 'municipios.id')
            ->select('municipios.cvegeo', DB::raw('SUM(valor) as total_valor'))
            ->groupBy('municipios.cvegeo')
            ->pluck('total_valor', 'municipios.cvegeo')
            ->mapWithKeys(function ($value, $key) {
                return [(string) $key => $value];
            })
            ->all();
    }
}
