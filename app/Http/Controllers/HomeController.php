<?php

namespace App\Http\Controllers;

use App\Models\DatoHistorico;
use App\Models\Variable;
use App\Models\Indicador;
use App\Models\Dimension;

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
        $variablesDestacadas = Variable::with('indicador')
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
                    'link'      => route('fichas.index', [
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
        $indicadoresComplejos = Indicador::where('es_complejo', '1')->orderBy('nombre_amigable')->get();
        $dimensiones = Dimension::orderBy('nombre')->get();
        return view('datos-abiertos', ['dimensiones' => $dimensiones], ['indicadoresComplejos' => $indicadoresComplejos]);
    }
}
