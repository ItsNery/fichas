<?php

namespace App\Exports;

use Illuminate\Support\Facades\Log; // Asegúrate de que esta línea esté
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class IndicadorExport implements FromArray, WithHeadings, WithTitle
{
    protected $chartData;

    public function __construct(array $chartData)
    {
        $this->chartData = $chartData;
    }

    public function title(): string
    {
        return substr(preg_replace('/[\\*\\:\\/\\?\\s\\[\\]]+/', '_', $this->chartData['titulo'] ?? 'Export'), 0, 31);
    }

    public function headings(): array
    {
        // Los encabezados de metadata se quedan igual
        return [
            ['Indicador:', $this->chartData['titulo'] ?? 'N/A'],
            ['Descripción:', $this->chartData['descripcion'] ?? 'N/A'],
            ['Fuente:', $this->chartData['fuente'] ?? 'N/A'],
            ['Método de cálculo:', $this->chartData['metodo_calculo'] ?? 'N/A'],
            [''], // Fila vacía
        ];
    }

    public function array(): array
    {
        $dataRows = [];
        $dataHeadings = [];

        $tipoGrafico = $this->chartData['tipo_grafico'] ?? 'line';
        $series = $this->chartData['series'] ?? [];
        $ejeX = $this->chartData['eje_x'] ?? [];

        if (in_array($tipoGrafico, ['bar', 'piramide']) && isset($ejeX['categorias'])) {

            $dataHeadings = [$ejeX['titulo'] ?? 'Categoría'];
            foreach ($series as $serie) {
                $dataHeadings[] = $serie['name'];
            }

            $categorias = $ejeX['categorias'];

            // Iteramos por cada categoría 
            foreach ($categorias as $index => $categoria) {
                $fila = [$categoria];
                // Para cada categoría, obtenemos el valor de cada serie
                foreach ($series as $serie) {
                    // Tomamos el valor de la serie y lo limpiamos (Math.abs)
                    $valor = $serie['data'][$index] ?? 0;
                    $fila[] = abs((float)$valor);
                }
                $dataRows[] = $fila;
            }
        } elseif ($tipoGrafico === 'line') {
            // Caso: Gráfico de Líneas 
            $dataHeadings = ['Año', 'Variable/Serie', 'Valor'];
            foreach ($series as $serie) {
                $nombreSerie = $serie['name'];
                foreach ($serie['data'] as $punto) {
                    $dataRows[] = [
                        $punto[0],
                        $nombreSerie,
                        $punto[1],
                    ];
                }
            }
        } else {
            $dataHeadings = ['Aviso'];
            $dataRows[] = ['El formato de este gráfico no es compatible con la exportación.'];
        }

        return array_merge([$dataHeadings], $dataRows);
    }
}
