<?php

namespace App\Services;

use App\Models\Municipio;
use App\Models\Variable;
use App\Models\DatoHistorico;

class FichaProfilerService
{
    /**
     * Obtiene y optimiza las estadísticas del Hero en 2 consultas principales en lugar de 12.
     *
     * @param Municipio $municipio
     * @return array
     */
    public static function getHeroStats(Municipio $municipio)
    {
        // 1. Obtener las variables necesarias en una sola consulta
        $variables = Variable::whereIn('nombre_amigable', [
            'Población total',
            'Grado de Marginación',
            'Superficie territorial (Hectáreas)',
            'FORTAMUN APROBADO',
            'FAISMUN APROBADO',
            'Porcentaje de población en situación de pobreza',
            'Población Económicamente Activa (PEA)'
        ])->get();

        $varPob = $variables->first(fn($v) => $v->nombre_amigable === 'Población total');
        $varMarg = $variables->first(fn($v) => $v->nombre_amigable === 'Grado de Marginación');
        $varSup = $variables->first(fn($v) => $v->nombre_amigable === 'Superficie territorial (Hectáreas)');
        $varPobreza = $variables->first(fn($v) => $v->nombre_amigable === 'Porcentaje de población en situación de pobreza');
        $varPea = $variables->first(fn($v) => $v->nombre_amigable === 'Población Económicamente Activa (PEA)');
        
        $varsPresupuestoIds = $variables->filter(fn($v) => in_array($v->nombre_amigable, ['FORTAMUN APROBADO', 'FAISMUN APROBADO']))->pluck('id');

        // 2. Obtener todos los datos históricos de estas variables para este municipio en 1 sola consulta
        $datos = DatoHistorico::with('variable')
            ->where('municipio_id', $municipio->id)
            ->whereIn('variable_id', $variables->pluck('id'))
            ->get();

        // Población Total
        $poblacionTotal = 0;
        if ($varPob) {
            $datoPob = $datos->where('variable_id', $varPob->id)->sortByDesc('anio')->first();
            $poblacionTotal = $datoPob->valor ?? 0;
        }

        // Grado de Marginación
        $gradoMarginacion = 'N/D';
        if ($varMarg) {
            $datoMarg = $datos->where('variable_id', $varMarg->id)->sortByDesc('anio')->first();
            $gradoMarginacion = $datoMarg->valor_display ?? 'N/D';
        }

        // Superficie territorial (Hectáreas) -> km2
        $superficieKm2 = 0;
        if ($varSup) {
            $datoSup = $datos->where('variable_id', $varSup->id)->sortByDesc('anio')->first();
            if ($datoSup && $datoSup->valor > 0) {
                $superficieKm2 = $datoSup->valor / 100;
            }
        }

        // Presupuesto (FORTAMUN + FAISMUN)
        $ultimoAnioPres = null;
        $presupuesto = 0;
        $datosPres = $datos->whereIn('variable_id', $varsPresupuestoIds);
        if ($datosPres->isNotEmpty()) {
            $ultimoAnioPres = $datosPres->max('anio');
            $presupuesto = $datosPres->where('anio', $ultimoAnioPres)->sum('valor');
        }

        // Porcentaje de población en situación de pobreza
        $porcentajePobreza = 'N/D';
        if ($varPobreza) {
            $datoPobreza = $datos->where('variable_id', $varPobreza->id)->sortByDesc('anio')->first();
            if ($datoPobreza && $datoPobreza->valor) {
                $porcentajePobreza = number_format($datoPobreza->valor, 1) . '%';
            }
        }

        // PEA
        $pea = 0;
        if ($varPea) {
            $datoPea = $datos->where('variable_id', $varPea->id)->sortByDesc('anio')->first();
            $pea = $datoPea->valor ?? 0;
        }

        return [
            'poblacionTotal' => $poblacionTotal,
            'gradoMarginacion' => $gradoMarginacion,
            'superficieKm2' => $superficieKm2,
            'presupuesto' => $presupuesto,
            'ultimoAnioPres' => $ultimoAnioPres,
            'porcentajePobreza' => $porcentajePobreza,
            'pea' => $pea
        ];
    }
}
