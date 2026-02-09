<?php

namespace App\Exports;

use App\Models\DatoHistorico;
use App\Models\Dimension;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DatosHistoricosExport implements FromQuery, WithHeadings, WithMapping
{
    protected $dimension;
    protected $anio;
    public function __construct(Dimension $dimension = null, $anio = null)
    {
        $this->dimension = $dimension;
        $this->anio = $anio;
    }
    /**
     * Define la consulta a la base de datos.
     * Usamos joins para obtener los nombres en lugar de solo los IDs.
     */
    public function query()
    {
        // Inicia la consulta base
        $query = DatoHistorico::query();

        // CASO 1: Si se pasaron los filtros de "Datos Abiertos"...
        if ($this->dimension && $this->anio) {
            $dimensionId = $this->dimension->id;
            $anio = $this->anio;

            $query->whereHas('variable.indicador.tematica', function ($q) use ($dimensionId) {
                $q->where('dimension_id', $dimensionId);
            })
                ->where('anio', $anio);
        }
        // CASO 2: Si NO se pasaron filtros (es el botón "Exportar Todo" del admin)...
        // ... no se añade ningún 'where', por lo que la consulta traerá TODO.

        // La consulta final se completa y se ejecuta
        return $query->with([
            'variable.indicador.tematica.dimension',
            'municipio'
        ])->orderBy('id', 'asc');
    }

    /**
     * Define los encabezados de las columnas en el archivo Excel/CSV.
     */
    public function headings(): array
    {
        return [
            'ID Dato',
            'Dimensión',
            'Temática',
            'Indicador',
            'Variable (ID)',
            'Nombre Variable',
            'Municipio (ID)',
            'Nombre Municipio',
            'Año',
            'Valor',
        ];
    }

    /**
     * Mapea cada fila de la consulta a las columnas del archivo.
     * @param \App\Models\DatoHistorico $dato
     */
    public function map($dato): array
    {
        $variable = $dato->variable;
        $indicador = $variable->indicador ?? null;
        $tematica = $indicador->tematica ?? null;
        $dimension = $tematica->dimension ?? null;

        return [
            $dato->id,
            $dimension->nombre ?? 'N/A', // Nueva columna
            $tematica->nombre ?? 'N/A',  // Nueva columna
            $indicador->nombre_amigable ?? 'N/A', // Nueva columna
            $dato->variable_id,
            $variable->nombre_amigable ?? 'N/A',
            $dato->municipio_id,
            $dato->municipio->nombre ?? 'N/A',
            $dato->anio,
            $dato->valor,
        ];
    }
}
