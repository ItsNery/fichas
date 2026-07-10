<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegionGeneralSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    private $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function view(): View
    {
        return view('exports.region_general', [
            'datos' => $this->datos
        ]);
    }

    public function title(): string
    {
        return 'Información General';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}
