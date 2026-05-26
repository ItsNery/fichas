<?php
namespace App\Exports;

use App\Models\Indicador;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class IndicadoresExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Indicador::query()->with('tematica.dimension');
    }

    public function headings(): array
    {
        return [
            'ID Indicador',
            'ID Dimensión',
            'Nombre Dimensión',
            'ID Temática Padre',
            'Nombre Temática',
            'Nombre Indicador',
            'Nombre Técnico (único)',
            'Descripción',
            'Fuente',
            'Tipo de Dato',
            'Tipo de Gráfico por Defecto',
            'Método de Cálculo',
            'Solo Resumen',
            'Es Complejo',
            'Priorizar Total',
            'Orden',
        ];
    }
    /** @param Indicador $indicador */
    public function map($indicador): array
    {
        return [
            $indicador->id,
            $indicador->tematica->dimension->id,
            $indicador->tematica->dimension->nombre,
            $indicador->tematica->id,
            $indicador->tematica->nombre,
            $indicador->nombre_amigable,
            $indicador->nombre_tecnico,
            $indicador->descripcion,
            $indicador->fuente,
            $indicador->tipo_dato,
            $indicador->tipo_grafico_default,
            $indicador->metodo_calculo,
            $indicador->solo_resumen ? 'Sí' : 'No',
            $indicador->es_complejo ? 'Sí' : 'No',
            $indicador->priorizar_total ? 'Sí' : 'No',
            $indicador->orden,
        ];
    }
}
