<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


class DiccionarioSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        // Cabeceras para tu diccionario.
        return ['Campo', 'Descripción', 'Valores Permitidos / Ejemplo'];
    }

    public function title(): string
    {
        // El nombre de la segunda pestaña.
        return 'Diccionario de Datos';
    }
}