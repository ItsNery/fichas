<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PlantillaSheet implements FromArray, WithHeadings, WithTitle
{
    protected $headings;

    public function __construct(array $headings)
    {
        $this->headings = $headings;
    }

    public function array(): array
    {
        // Devolvemos un array vacío porque solo queremos las cabeceras.
        return [];
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        // Este será el nombre de la pestaña en Excel.
        return 'Plantilla';
    }
}