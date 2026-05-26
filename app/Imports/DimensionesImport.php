<?php
namespace App\Imports;

use App\Models\Dimension;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DimensionesImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Usamos firstOrCreate con el 'nombre_tecnico' para evitar duplicados.
        // Si ya existe una dimensión con ese nombre técnico, la encontrará.
        // Si no, la creará con todos los datos.
        return Dimension::firstOrCreate(
            [
                // La clave única para buscar
                'nombre_tecnico' => $row['nombre_tecnico'],
            ],
            [
                // Los datos que se usarán si se crea un nuevo registro
                'nombre' => $row['nombre'],
                'color'  => $row['color'] ?? '#6c757d', // Un color por defecto si está vacío
                'orden'  => $row['orden'] ?? 0,
            ]
        );
    }
}
