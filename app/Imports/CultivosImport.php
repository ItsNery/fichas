<?php

namespace App\Imports;

use App\Models\DatoIndicadorComplejo;
use App\Models\Municipio;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CultivosImport implements ToCollection, WithChunkReading
{
    private $indicadorId;
    private $headers;
    private $municipiosMap;

    public function __construct(int $indicadorId)
    {
        $this->indicadorId = $indicadorId;
        $this->municipiosMap = Municipio::pluck('id', 'cvegeo')->toArray();
    }

    /**
     * Este método se llamará por cada "lote" de filas.
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // 1. Si aún no hemos capturado los encabezados, lo hacemos ahora.
        //    Esto solo ocurrirá en el primer lote.
        if (! $this->headers) {
            $this->headers = $rows->first()->toArray();
            // Eliminamos la fila de encabezados del primer lote para no procesarla como datos.
            $rows->shift();
        }

        // 2. Iteramos sobre las filas de datos del lote actual.
        foreach ($rows as $row) {
            // Combinamos los encabezados que guardamos con la fila actual.
            $currentRow = $row->toArray();
            if (count($this->headers) !== count($currentRow)) {
                \Illuminate\Support\Facades\Log::error('Mismatch in CultivosImport', [
                    'header_count' => count($this->headers),
                    'row_count' => count($currentRow),
                    'headers' => $this->headers,
                    'row' => $currentRow
                ]);
            }
            $rowData = array_combine($this->headers, $currentRow);

            // Buscamos las claves 'municipio_id' y 'anio' sin importar mayúsculas.
            $keysLower = array_change_key_case($rowData, CASE_LOWER);

            $municipioIdentifier = $keysLower['municipio_cvegeo']
                ?? $keysLower['municipio_id']
                ?? null;

            $anio = $keysLower['anio'] ?? null;

            if (empty($municipioIdentifier) || empty($anio)) {
                throw new \Exception('Fila sin municipio o año');
            }

            if (! isset($this->municipiosMap[$municipioIdentifier])) {
                throw new \Exception(
                    'Municipio no encontrado. cvegeo: ' . $municipioIdentifier
                );
            }

            $municipioId = $this->municipiosMap[$municipioIdentifier];
            $excluded = [
                'municipio_id',
                'municipio_cvegeo',
                'anio',
            ];
            $datosCultivos = [];
            foreach ($rowData as $header => $value) {

                if (! in_array(strtolower($header), $excluded)) {
                    if (is_numeric($value)) {
                        $datosCultivos[$header] = (float) $value;
                    }
                }
            }

            if (! empty($datosCultivos)) {
                DatoIndicadorComplejo::updateOrCreate(
                    ['indicador_id' => $this->indicadorId, 'municipio_id' => $municipioId, 'anio' => $anio],
                    ['datos' => $datosCultivos]
                );
            }
        }
    }

    /**
     * Define el tamaño del lote.
     */
    public function chunkSize(): int
    {
        return 500;
    }
}
