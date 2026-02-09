<?php

namespace App\Imports;

use App\Models\Municipio;
use App\Models\Instrumento;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class InstrumentoMunicipioImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $municipiosCachePorCvegeo;
    private $instrumentosCache;

    public function __construct()
    {
        $this->municipiosCachePorCvegeo = Municipio::pluck('id', 'cvegeo');
        $this->instrumentosCache = Instrumento::pluck('id', 'nombre');
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $municipioId = null;
            $instrumentoId = null;
            $anio = $row['anio'] ?? null;

            // --- LÓGICA INTELIGENTE PARA MUNICIPIO (sin cambios) ---
            if (!empty($row['municipio_cvegeo'])) {
                $municipioId = $this->municipiosCachePorCvegeo[$row['municipio_cvegeo']] ?? null;
            } else if (!empty($row['municipio_id'])) {
                $municipioId = $row['municipio_id'];
            }

            // --- INICIO DE LA NUEVA LÓGICA INTELIGENTE PARA INSTRUMENTO ---
            // Prioridad #1: Buscar por 'instrumento_nombre'
            if (!empty($row['instrumento_nombre'])) {
                $instrumentoId = $this->instrumentosCache[$row['instrumento_nombre']] ?? null;
            } 
            // Prioridad #2: Si no, buscar por 'nombre' (una alternativa común)
            else if (!empty($row['nombre'])) {
                $instrumentoId = $this->instrumentosCache[$row['nombre']] ?? null;
            }
            // --- FIN DE LA NUEVA LÓGICA ---

            // Si después de todo no encontramos los datos clave, ignoramos la fila.
            if ($municipioId && $instrumentoId && $anio) {
                $municipio = Municipio::find($municipioId);
                if ($municipio) {
                    $municipio->instrumentos()->syncWithoutDetaching([
                        $instrumentoId => ['anio' => $anio]
                    ]);
                }
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}