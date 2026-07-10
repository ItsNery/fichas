<?php

namespace App\Services;

use App\Models\Municipio;
use App\Models\DatoHistorico;
use App\Models\Variable;

class FichaDataStore
{
    /** @var Municipio */
    public $municipio;

    /** @var array */
    public $allVariableIds;

    /** @var \Illuminate\Support\Collection */
    public $muniData;

    /** @var \Illuminate\Support\Collection */
    public $globalData;

    /** @var \Illuminate\Support\Collection */
    public $macrorregionIds;

    /** @var \Illuminate\Support\Collection */
    public $allVariables;

    /**
     * FichaDataStore constructor.
     *
     * @param Municipio $municipio
     * @param array $allVariableIds
     * @param \Illuminate\Support\Collection|null $globalData
     */
    public function __construct(Municipio $municipio, array $allVariableIds, $globalData = null)
    {
        $this->municipio = $municipio;
        $this->allVariableIds = $allVariableIds;

        // Cargar variables correspondientes
        $this->allVariables = Variable::whereIn('id', $allVariableIds)->get()->keyBy('id');

        // Cargar datos históricos del municipio objetivo con su relación variable precargada
        $this->muniData = DatoHistorico::with('variable')
            ->where('municipio_id', $municipio->id)
            ->whereIn('variable_id', $allVariableIds)
            ->get();

        // Cargar datos históricos globales solo de las columnas necesarias en una sola consulta
        $this->globalData = $globalData ?: \Illuminate\Support\Facades\DB::table('dato_historicos')
            ->whereIn('variable_id', $allVariableIds)
            ->select('municipio_id', 'variable_id', 'anio', 'valor')
            ->get();

        // Obtener municipios de la misma macrorregión
        $macrorregionId = $municipio->microrregion->macrorregion_id ?? null;
        if ($macrorregionId) {
            $this->macrorregionIds = Municipio::whereHas('microrregion', function ($q) use ($macrorregionId) {
                $q->where('macrorregion_id', $macrorregionId);
            })->pluck('id');
        } else {
            $this->macrorregionIds = collect();
        }
    }
    /**
     * Extrae todos los IDs de variables involucrados en una colección de configuraciones.
     *
     * @param \Illuminate\Support\Collection $configuraciones
     * @return array
     */
    public static function extractVariableIds($configuraciones)
    {
        $allVariableIds = collect();
        foreach ($configuraciones as $config) {
            $vars = $config->variables->isNotEmpty() 
                ? $config->variables->pluck('id') 
                : ($config->indicador ? $config->indicador->variables->pluck('id') : collect());
            $allVariableIds = $allVariableIds->merge($vars);
        }
        return $allVariableIds->unique()->values()->toArray();
    }
}
