<?php
namespace App\Exports;

use App\Models\Tematica;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TematicasExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        // Obtenemos las temáticas y cargamos su relación 'dimension' para ser eficientes
        return Tematica::query()->with('dimension');
    }

    public function headings(): array
    {
        return [
            'ID Temática',
            'ID Dimensión Padre',
            'Nombre Dimensión Padre',
            'Nombre Temática',
            'Nombre Técnico (único)',
        ];
    }

    /** @param Tematica $tematica */
    public function map($tematica): array
    {
        return [
            $tematica->id,
            $tematica->dimension->id,
            $tematica->dimension->nombre,
            $tematica->nombre,
            $tematica->nombre_tecnico,
        ];
    }
}
