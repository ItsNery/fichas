<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CatalogoGeograficoBaseExport implements FromArray, WithHeadings
{
    public function __construct(
        private array $columns,
        private array $rows,
    ) {
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
