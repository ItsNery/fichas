<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RegionExport implements WithMultipleSheets
{
    use Exportable;

    protected $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Agregar una hoja principal con la información general
        $sheets[] = new RegionGeneralSheet($this->datos);

        // Agregar una hoja por cada dimensión
        foreach ($this->datos['perfil'] as $seccion => $items) {
            if ($seccion != 'general') {
                $sheets[] = new RegionDimensionSheet($seccion, $items, $this->datos['region'], $this->datos['tipoRegion']);
            }
        }

        return $sheets;
    }
}
