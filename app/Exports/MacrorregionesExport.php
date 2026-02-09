<?php
namespace App\Exports;

use App\Models\Macrorregion;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MacrorregionesExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Macrorregion::query()->orderBy('nombre', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
        ];
    }

    /**
     * @param Macrorregion $macrorregion
     */
    public function map($macrorregion): array
    {
        return [
            $macrorregion->id,
            $macrorregion->nombre,
        ];
    }
}
