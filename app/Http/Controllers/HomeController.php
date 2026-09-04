<?php

namespace App\Http\Controllers;

use App\Models\DatoHistorico;
use App\Models\Variable;
use App\Models\Indicador;
use App\Models\Dimension;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Retrieves Key Featured Variables, aggregates their historical data at the state level (estatal),
     * calculates the latest total value, and prepares data for sparkline charts to display on the homepage.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. Buscamos directamente las VARIABLES marcadas como destacadas.
        $variablesDestacadas = Variable::where('visible_en_ficha', true)
            ->whereHas('indicador', fn($indicador) => $indicador->where('visible_en_ficha', true)
                ->whereHas('tematica', fn($tematica) => $tematica->where('visible_en_ficha', true)
                    ->whereHas('dimension', fn($dimension) => $dimension->where('visible_en_ficha', true))))
            ->with('indicador')
            ->where('es_destacada', true)
            ->get();

        $indicadoresDestacados = [];

        // 2. Recorremos las variables encontradas para preparar sus datos.
        foreach ($variablesDestacadas as $variable) {
            // Obtenemos los datos históricos para la tendencia de ESTA variable
            $datosHistoricos = DatoHistorico::where('variable_id', $variable->id)
                ->selectRaw('anio, SUM(valor) as valor_total')
                ->groupBy('anio')
                ->orderBy('anio', 'asc')
                ->get();

            if ($datosHistoricos->isNotEmpty()) {
                $ultimoDato    = $datosHistoricos->last();
                $sparklineData = $datosHistoricos->map(fn($dato) => (float) $dato->valor_total)->all();

                $indicadoresDestacados[] = [
                    'titulo'    => $variable->nombre_amigable, // El título es el nombre de la variable
                    'valor'     => number_format($ultimoDato->valor_total, 0, '.', ','),
                    'anio'      => $ultimoDato->anio,
                    'sparkline' => $sparklineData,
                    'link'      => route('banco-indicadores.index', [
                        'indicador_id'  => $variable->indicador->id,
                        'municipio_ids' => 'estatal',
                    ]),
                ];
            }
        }

        return view('inicio', ['indicadoresDestacados' => $indicadoresDestacados]);
    }

    public function datosAbiertos()
    {
        $indicadoresComplejos = Indicador::where('visible_en_ficha', true)
            ->where('es_complejo', '1')->orderBy('nombre_amigable')->get();
        $dimensiones = Dimension::where('visible_en_ficha', true)->orderBy('nombre')->get();

        return view('datos-abiertos', compact('dimensiones', 'indicadoresComplejos'));
    }

    public function exportarCoyuntura()
    {
        $datos = DB::table('sei.indicadores_estrategicos_coyuntura as indicador')
            ->leftJoin('sei.cat_coyuntura_tipos_indicador as tipo', 'tipo.id', '=', 'indicador.tipo_indicador_id')
            ->leftJoin('sei.cat_coyuntura_tematicas_snieg as snieg', 'snieg.id', '=', 'indicador.tematica_snieg_id')
            ->leftJoin('sei.cat_coyuntura_tematicas as tematica', 'tematica.id', '=', 'indicador.tematica_id')
            ->leftJoin('sei.cat_coyuntura_pilares_desarrollo as pilar', 'pilar.id', '=', 'indicador.pilar_desarrollo_id')
            ->leftJoin('sei.cat_coyuntura_periodicidades as periodicidad', 'periodicidad.id', '=', 'indicador.periodicidad_id')
            ->leftJoin('sei.cat_coyuntura_unidades_medida as unidad', 'unidad.id', '=', 'indicador.unidad_medida_id')
            ->leftJoin('sei.datos_indicadores_estrategicos_coyuntura as dato', 'dato.indicador_id', '=', 'indicador.id')
            ->leftJoin('sei.periodos_indicadores_estrategicos_coyuntura as periodo', 'periodo.id', '=', 'dato.periodo_id')
            ->where('indicador.activo', true)
            ->select([
                'indicador.nombre as indicador',
                'tipo.nombre as tipo_indicador',
                'snieg.nombre as tematica_snieg',
                'tematica.nombre as tematica',
                'pilar.nombre as pilar_desarrollo',
                'periodicidad.nombre as periodicidad',
                'unidad.nombre as unidad_medida',
                'indicador.fuente',
                'indicador.url_fuente',
                'periodo.etiqueta as periodo',
                'periodo.anio_inicio',
                'periodo.anio_fin',
                'periodo.fecha_inicio',
                'periodo.fecha_fin',
                'dato.valor_dato',
                'dato.valor_texto',
                'dato.fecha_actualizacion',
                'dato.observaciones',
            ])
            ->orderBy('indicador.orden')
            ->orderBy('indicador.nombre')
            ->orderBy('periodo.fecha_fin');

        return response()->streamDownload(function () use ($datos) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, [
                'indicador', 'tipo_indicador', 'tematica_snieg', 'tematica', 'pilar_desarrollo',
                'periodicidad', 'unidad_medida', 'fuente', 'url_fuente',
                'periodo', 'anio_inicio', 'anio_fin', 'fecha_inicio', 'fecha_fin', 'valor_dato',
                'valor_texto', 'fecha_actualizacion', 'observaciones',
            ]);

            foreach ($datos->cursor() as $fila) {
                fputcsv($salida, (array) $fila);
            }

            fclose($salida);
        }, 'indicadores-coyuntura.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
