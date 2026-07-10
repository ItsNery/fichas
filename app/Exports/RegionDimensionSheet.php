<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegionDimensionSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    private $seccion;
    private $items;
    private $region;
    private $tipoRegion;

    public function __construct(string $seccion, array $items, $region, $tipoRegion)
    {
        $this->seccion = $seccion;
        $this->items = $items;
        $this->region = $region;
        $this->tipoRegion = $tipoRegion;
    }

    public function view(): View
    {
        return view('exports.region_dimension', [
            'seccion' => $this->seccion,
            'items' => $this->items,
            'region' => $this->region,
            'tipoRegion' => $this->tipoRegion
        ]);
    }

    public function title(): string
    {
        // El título de la hoja no puede exceder 31 caracteres ni contener ciertos caracteres especiales
        $title = ucwords(str_replace('_', ' ', $this->seccion));
        return substr($title, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'size' => 12]],
            2    => ['font' => ['bold' => true]],
        ];
    }
}
