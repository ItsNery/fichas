<?php

namespace App\Exports;

use App\Models\Variable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CatalogoExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * Prepara la consulta a la base de datos.
    */
    public function collection()
    {
        // Obtenemos todas las variables con sus relaciones de jerarquía
        return Variable::with('indicador.tematica.dimension')->get();
    }

    /**
    * Define los encabezados de las columnas en el Excel.
    */
    public function headings(): array
    {
        return [
            'Dimension',
            'Tematica',
            'Indicador',
            'Variable (Amigable)',
            'Variable (Técnico)',
            'Unidad de Medida',
            'Es KPI',
        ];
    }

    /**
    * Mapea los datos de cada fila.
    * @param Variable $variable
    */
    public function map($variable): array
    {
        return [
            $variable->indicador->tematica->dimension->nombre,
            $variable->indicador->tematica->nombre,
            $variable->indicador->nombre_amigable,
            $variable->nombre_amigable,
            $variable->nombre_tecnico,
            $variable->unidad_medida,
            $variable->es_kpi ? 'Sí' : 'No',
        ];
    }
}