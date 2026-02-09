<?php
namespace App\Exports;

use App\Models\Microrregion;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MicrorregionesExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        // Usamos 'with' para cargar la relación y evitar consultas N+1
        return Microrregion::query()->with('macrorregion')->orderBy('nombre', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Macrorregión a la que pertenece',
        ];
    }

    /**
     * @param Microrregion $microrregion
     */
    public function map($microrregion): array
    {
        return [
            $microrregion->id,
            $microrregion->nombre,
            $microrregion->macrorregion->nombre ?? 'N/A',
        ];
    }
}
