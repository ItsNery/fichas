<?php

namespace App\Exports;

use App\Models\DatoIndicadorComplejo;
use App\Models\Indicador;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DatosComplejosExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $indicador;
    protected $anio;

    public function __construct(Indicador $indicador, $anio)
    {
        $this->indicador = $indicador;
        $this->anio = $anio;
    }
    public function collection()
    {
        // 1. Obtenemos los datos, PERO AHORA FILTRADOS POR AÑO
        $datos = DatoIndicadorComplejo::where('indicador_id', $this->indicador->id)
            ->where('anio', $this->anio) // <-- EL NUEVO FILTRO
            ->with('municipio')
            ->orderBy('municipio_id', 'asc')
            ->get();

        $filasAplanadas = collect();

        // 2. "Aplanamos" los datos del JSON (sin cambios)
        foreach ($datos as $registro) {
            $datosCultivos = $registro->datos ?? [];

            foreach ($datosCultivos as $cultivoNombre => $valor) {
                $filasAplanadas->push([
                    'municipio_id'   => $registro->municipio_id,
                    'municipio_nombre' => $registro->municipio->nombre ?? 'N/A',
                    'anio'           => $registro->anio,
                    'cultivo'        => $cultivoNombre,
                    'valor'          => (float) $valor,
                ]);
            }
        }

        return $filasAplanadas;
    }

    /**
     * Define los encabezados de las columnas en el archivo CSV.
     */
    public function headings(): array
    {
        return [
            'Municipio (ID)',
            'Nombre Municipio',
            'Año',
            'Cultivo/Categoría',
            'Valor',
        ];
    }
}
