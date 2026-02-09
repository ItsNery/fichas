<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CultivosExcelImport implements WithMultipleSheets
{
    protected int $indicadorId;

    public function __construct(int $indicadorId)
    {
        $this->indicadorId = $indicadorId;
    }

    /**
     * Definimos qué hojas se van a procesar
     */
    public function sheets(): array
    {
        return [
            0 => new CultivosImport($this->indicadorId),
        ];
    }
}
