<?php
namespace App\Exports;

use App\Models\Municipio;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MunicipiosExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        // Obtiene todos los municipios ordenados por nombre
        return Municipio::query()->orderBy('nombre', 'asc');
    }

    /**
     * Define los encabezados de las columnas del archivo Excel.
     */
    public function headings(): array
    {
        return [
            'ID',
            'CVEGEO',
            'Nombre',
        ];
    }

    /**
     * Mapea los datos de cada municipio a las columnas del archivo.
     * @param Municipio $municipio
     */
    public function map($municipio): array
    {
        return [
            $municipio->id,
            $municipio->cvegeo,
            $municipio->nombre,
        ];
    }
}
