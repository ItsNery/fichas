<?php

namespace App\Services;

use App\Exports\DatosComplejosExport;
use App\Exports\IndicadorExport;
use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Municipio;
use App\Models\Variable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportService
{
    public function exportChartData(array $chartData)
    {
        $export = new IndicadorExport($chartData);
        $fileName = Str::slug($chartData['titulo'] ?? 'export-datos') . '.csv';

        return Excel::download($export, $fileName);
    }

    public function exportResumenPDF(Municipio $municipio)
    {
        $variablesKPI = Variable::with('indicador.tematica.dimension')
            ->whereNotIn('id', [200])
            ->where('es_kpi', true)
            ->get();

        $datosPorDimension = [];

        foreach ($variablesKPI as $variable) {
            $dato = DatoHistorico::where('variable_id', $variable->id)
                ->where('municipio_id', $municipio->id)
                ->orderBy('anio', 'desc')
                ->first();

            $dimension = $variable->indicador->tematica->dimension;
            $tematicaNombre = $variable->indicador->tematica->nombre;

            if (!isset($datosPorDimension[$dimension->id])) {
                $datosPorDimension[$dimension->id] = [
                    'nombre'    => $dimension->nombre,
                    'color'     => $dimension->color,
                    'slug'      => Str::slug($dimension->nombre),
                    'tematicas' => [],
                ];
            }

            $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = [
                'indicador_id'  => $variable->indicador->id,
                'nombre'        => $variable->nombre_amigable,
                'valor'         => $dato->valor ?? 'N/D',
                'anio'          => $dato->anio ?? 'N/D',
                'valor_display' => $dato ? $dato->valor_display : 'N/D',
                'unidad'        => $variable->unidad_medida,
                'solo_resumen'  => $variable->indicador->solo_resumen,
            ];
        }

        if ($municipio->instrumentos->isNotEmpty()) {
            $dimensionNombre = 'Geográfica y Medio Ambiente';
            $tematicaNombre = 'Ordenamiento Territorial';
            $indicadorNombre = 'Tipo de instrumentos de planeación en materia territorial';

            $dimension = Dimension::where('nombre', $dimensionNombre)->first();

            if ($dimension) {
                if (!isset($datosPorDimension[$dimension->id])) {
                    $datosPorDimension[$dimension->id] = [
                        'nombre' => $dimension->nombre,
                        'color' => $dimension->color,
                        'slug' => Str::slug($dimension->nombre),
                        'tematicas' => [],
                    ];
                }

                $kpiFantasma = [
                    'indicador_id'  => null,
                    'nombre'        => $indicadorNombre,
                    'valor'         => 'lista',
                    'anio'          => '',
                    'valor_display' => $municipio->instrumentos,
                    'unidad'        => '',
                    'solo_resumen'  => true,
                ];

                $datosPorDimension[$dimension->id]['tematicas'][$tematicaNombre][] = $kpiFantasma;
            }
        }

        $datosAgrupados = array_values($datosPorDimension);

        $pdf = PDF::loadView('municipios.resumen_pdf', compact('municipio', 'datosAgrupados'));

        $fileName = 'resumen-' . Str::slug($municipio->nombre) . '.pdf';
        return $pdf->download($fileName);
    }

    public function exportDatosComplejos(Indicador $indicador, $anio)
    {
        if (!$indicador->es_complejo || !Indicador::visiblePublicamente()->whereKey($indicador->id)->exists()) {
            abort(404, 'Exportación no disponible para este indicador.');
        }

        $fileName = 'exportacion-' . Str::slug($indicador->nombre_tecnico) . '-anio-' . $anio . '.csv';

        return Excel::download(new DatosComplejosExport($indicador, $anio), $fileName);
    }

    public function exportComparativaPDF(
        Municipio $municipio1,
        Municipio $municipio2,
        array $hero1,
        array $hero2,
        $configuraciones,
        array $comparativa
    ) {
        $pdf = PDF::loadView('municipios.comparar_pdf', compact(
            'municipio1', 'municipio2',
            'hero1', 'hero2',
            'comparativa',
            'configuraciones'
        ));

        $fileName = 'comparativa-' . Str::slug($municipio1->nombre) . '-vs-' . Str::slug($municipio2->nombre) . '.pdf';
        return $pdf->download($fileName);
    }
}
