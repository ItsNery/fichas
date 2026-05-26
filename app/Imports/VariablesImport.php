<?php
namespace App\Imports;

use App\Models\Indicador;
use App\Models\Variable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
// Recomendado añadir validación

class VariablesImport implements ToModel, WithHeadingRow, WithChunkReading
{
    private $indicadoresCachePorNombre;
    private $indicadoresCachePorTecnico; // <-- Mapa para el nuevo campo

    public function __construct()
    {
        // Mantenemos el mapa por nombre_amigable para retrocompatibilidad
        $this->indicadoresCachePorNombre = Indicador::pluck('id', 'nombre_amigable');
        // Creamos el nuevo mapa de traducción: ['nombre_tecnico_indicador' => id_indicador]
        $this->indicadoresCachePorTecnico = Indicador::pluck('id', 'nombre_tecnico');
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $indicadorId = null;

        // --- LÓGICA DE DETECCIÓN MEJORADA ---

        // Prioridad #1: ¿Nos dieron el nuevo 'nombre_tecnico' del indicador? (La mejor opción)
        if (! empty($row['indicador_tecnico'])) {
            $indicadorId = $this->indicadoresCachePorTecnico[$row['indicador_tecnico']] ?? null;
        }
        // Prioridad #2: Si no, ¿nos dieron el ID del indicador? (Para archivos viejos)
        else if (! empty($row['indicador_id'])) {
            $indicadorId = $row['indicador_id'];
        }
        // Prioridad #3: Como último recurso, ¿nos dieron el nombre amigable?
        else if (! empty($row['indicador_nombre'])) {
            $indicadorId = $this->indicadoresCachePorNombre[$row['indicador_nombre']] ?? null;
        }

        // Si después de todo no encontramos un ID de indicador, ignoramos la fila.
        if (! $indicadorId) {
            return null;
        }

        // La lógica para crear/actualizar la variable se queda igual, pero añadimos los nuevos campos
        return Variable::firstOrCreate(
            [
                'nombre_tecnico' => $row['nombre_tecnico'],
            ],
            [
                'indicador_id'    => $indicadorId,
                'nombre_amigable' => $row['nombre_amigable'],
                'unidad_medida'   => $row['unidad_medida'] ?? 'N/D',
                'es_kpi'          => filter_var($row['es_kpi'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'es_destacada'    => filter_var($row['es_destacada'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'mapeo_valores'   => $row['mapeo_valores'] ?? null,
                'orden'           => $row['orden'] ?? 0,
            ]
        );
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
