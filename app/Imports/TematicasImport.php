<?php

namespace App\Imports;

use App\Models\Dimension;
use App\Models\Tematica;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TematicasImport implements ToModel, WithHeadingRow, WithChunkReading
{
    private $dimensionesCachePorNombre;
    private $dimensionesCachePorTecnico; // <-- Mapa para el nuevo campo

    public function __construct()
    {
        // Mantenemos el mapa por nombre para retrocompatibilidad
        $this->dimensionesCachePorNombre = Dimension::pluck('id', 'nombre');
        // Creamos el nuevo mapa de traducción: ['nombre_tecnico_dimension' => id_dimension]
        $this->dimensionesCachePorTecnico = Dimension::pluck('id', 'nombre_tecnico');
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $dimensionId = null;

        // Si no viene el nombre de la temática, ignoramos la fila (puede ser fila vacía)
        if (empty($row['nombre'])) {
            return null;
        }

        // --- LÓGICA DE DETECCIÓN MEJORADA ---

        // Prioridad #1: ¿Nos dieron el nuevo 'nombre_tecnico' de la dimensión? (La mejor opción)
        if (! empty($row['dimension_tecnico'])) {
            $dimensionId = $this->dimensionesCachePorTecnico[$row['dimension_tecnico']] ?? null;
        }
        // Prioridad #2: Si no, ¿nos dieron el ID de la dimensión? (Para archivos viejos)
        else if (! empty($row['dimension_id'])) {
            $dimensionId = $row['dimension_id'];
        }
        // Prioridad #3: Como último recurso, ¿nos dieron el nombre amigable?
        else if (! empty($row['dimension_nombre'])) {
            $dimensionId = $this->dimensionesCachePorNombre[$row['dimension_nombre']] ?? null;
        }

        // Si después de todo no encontramos un ID de dimensión, lanzamos una excepción.
        if (! $dimensionId) {
            throw new \Exception("No se encontró la dimensión especificada en la fila con nombre: " . ($row['nombre'] ?? 'Desconocido'));
        }

        // Generar slug si no viene en el archivo
        $slug = !empty($row['nombre_tecnico'])
            ? $row['nombre_tecnico']
            : \Illuminate\Support\Str::slug($row['nombre'], '_');

        // Usamos firstOrCreate con el 'nombre_tecnico' para evitar duplicados
        return Tematica::firstOrCreate(
            [
                'nombre_tecnico' => $slug,
            ],
            [
                'dimension_id' => $dimensionId,
                'nombre'       => $row['nombre'],
                'orden'        => $row['orden'] ?? 0,
            ]
        );
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
