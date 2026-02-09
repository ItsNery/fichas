<?php

namespace App\Exports;

use App\Exports\Sheets\DiccionarioSheet;
use App\Exports\Sheets\PlantillaSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlantillaExport implements WithMultipleSheets
{
    use Exportable;

    protected $headings;
    protected $diccionario;

    public function __construct(array $headings, array $diccionario)
    {
        $this->headings = $headings;
        $this->diccionario = $diccionario;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // Creamos la primera hoja con la plantilla
        $sheets[] = new PlantillaSheet($this->headings);

        // Creamos la segunda hoja con el diccionario de datos
        $sheets[] = new DiccionarioSheet($this->diccionario);

        return $sheets;
    }
}