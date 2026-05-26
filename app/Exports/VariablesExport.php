<?php
namespace App\Exports;

use App\Models\Variable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VariablesExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Variable::query()->with('indicador.tematica.dimension');
    }
    public function headings(): array
    {
        // He ajustado la capitalización para que sea consistente
        return [
            'ID Variable',
            'ID Dimensión',
            'Nombre Dimensión',
            'ID Temática',
            'Nombre Temática',
            'ID Indicador Padre',
            'Nombre Indicador Padre', 
            'Nombre Técnico Variable',
            'Nombre Amigable Variable',
            'Unidad de Medida',
            'Mapeo de Valores (JSON)', 
            'Es KPI',
            'Orden',
        ];
    }

/** @param Variable $variable */
    public function map($variable): array
    {
        // --- CORRECCIÓN ---
        // Accedemos a la jerarquía a través del indicador
        return [
            $variable->id,
            $variable->indicador->tematica->dimension->id,
            $variable->indicador->tematica->dimension->nombre,
            $variable->indicador->tematica->id,
            $variable->indicador->tematica->nombre,
            $variable->indicador->id,
            $variable->indicador->nombre_amigable,
            $variable->nombre_tecnico,
            $variable->nombre_amigable,
            $variable->unidad_medida,
            $variable->mapeo_valores,         
            $variable->es_kpi ? 'Sí' : 'No', 
            $variable->orden,
        ];
    }
}
