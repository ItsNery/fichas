<?php
namespace App\Exports;

use App\Models\Dimension;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DimensionesExport implements FromQuery, WithHeadings, WithMapping// <-- 2. La implementamos

{
    public function query()
    {
        return Dimension::query();
    }

    public function headings(): array
    {
        // Tus cabeceras están perfectas
        return [
            'ID Dimensión',
            'Nombre Dimensión',
            'Nombre tecnico (único)',
            'Color',
        ];
    }

    /**
     * 4. Añadimos el método map para definir qué columnas y en qué orden
     * @param Dimension $dimension
     */
    public function map($dimension): array
    {
        return [
            $dimension->id,
            $dimension->nombre,
            $dimension->nombre_tecnico,
            $dimension->color,
        ];
    }
}
