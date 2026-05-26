<?php
namespace App\Imports;

use App\Models\Indicador;
use App\Models\Tematica;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class IndicadorsImport implements ToModel, WithHeadingRow, WithChunkReading
{
    private $tematicasCachePorNombre;
    private $tematicasCachePorTecnico; // <-- Mapa para el nuevo campo

    public function __construct()
    {
        // Mantenemos el mapa por nombre para retrocompatibilidad
        $this->tematicasCachePorNombre = Tematica::pluck('id', 'nombre');
        // Creamos el nuevo mapa de traducción: ['nombre_tecnico_tematica' => id_tematica]
        $this->tematicasCachePorTecnico = Tematica::pluck('id', 'nombre_tecnico');
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $tematicaId = null;

        // --- LÓGICA DE DETECCIÓN MEJORADA ---

        // Prioridad #1: ¿Nos dieron el nuevo 'nombre_tecnico' de la temática? (La mejor opción)
        if (! empty($row['tematica_tecnico'])) {
            $tematicaId = $this->tematicasCachePorTecnico[$row['tematica_tecnico']] ?? null;
        }
        // Prioridad #2: Si no, ¿nos dieron el ID de la temática? (Para archivos viejos)
        else if (! empty($row['tematica_id'])) {
            $tematicaId = $row['tematica_id'];
        }
        // Prioridad #3: Como último recurso, ¿nos dieron el nombre amigable?
        else if (! empty($row['tematica_nombre'])) {
            $tematicaId = $this->tematicasCachePorNombre[$row['tematica_nombre']] ?? null;
        }

        // Si después de todo no encontramos un ID de temática, ignoramos la fila.
        if (! $tematicaId) {
            return null;
        }

        // Usamos firstOrCreate con el 'nombre_tecnico' del PROPIO indicador para evitar duplicados
        return Indicador::firstOrCreate(
            [
                'nombre_tecnico' => $row['nombre_tecnico'],
            ],
            [
                // Estos campos solo se llenarán si se está creando un nuevo registro
                'tematica_id'          => $tematicaId,
                'nombre_amigable'      => $row['nombre_amigable'],
                'descripcion'          => $row['descripcion'] ?? null,
                'fuente'               => $row['fuente'] ?? null,
                'tipo_dato'            => $row['tipo_dato'] ?? 'absoluto',
                'tipo_grafico_default' => trim($row['tipo_grafico_default'] ?? 'Barras'),
                'metodo_calculo'       => $row['metodo_calculo'] ?? null,
                'solo_resumen'         => filter_var($row['solo_resumen'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'es_complejo'          => filter_var($row['es_complejo'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'priorizar_total'      => filter_var($row['priorizar_total'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'orden'                => $row['orden'] ?? 0,
            ]
        );
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
