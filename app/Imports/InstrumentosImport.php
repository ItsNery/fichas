<?php
namespace App\Imports;

use App\Models\Instrumento;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InstrumentosImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Buscamos el nombre sin importar si la clave es 'nombre' o 'Nombre'.
        $nombre = $row['nombre'] ?? $row['Nombre'] ?? null;

        // 2. Si la fila está vacía o el nombre no se encontró, ignoramos la fila.
        if (empty($nombre)) {
            return null;
        }

        // 3. Usamos firstOrCreate para evitar duplicados.
        //    trim() elimina espacios en blanco al principio o al final del nombre.
        return Instrumento::firstOrCreate(
            ['nombre' => trim($nombre)]
        );
    }
}
