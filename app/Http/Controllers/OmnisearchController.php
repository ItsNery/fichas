<?php

namespace App\Http\Controllers;

use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * OmnisearchController
 *
 * Endpoint unificado de búsqueda que consulta múltiples entidades
 * (municipios, indicadores, regiones) y devuelve resultados agrupados
 * por tipo, compatibles con TomSelect.
 */
class OmnisearchController extends Controller
{
    /**
     * Busca en municipios, indicadores, microrregiones y macrorregiones.
     * Devuelve un JSON con resultados tipados para el rendering personalizado del TomSelect.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $q = $request->query('q', '');

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $results = collect();

        if (Str::contains(Str::lower(Str::ascii($q)), 'puebla')) {
            $results->push([
                'id'   => 'estado_puebla',
                'text' => 'Puebla',
                'type' => 'Estado',
                'icon' => 'fa-landmark',
                'url'  => route('regiones.estatal.perfil'),
            ]);
        }

        // 1. Municipios (prioridad alta, límite 5)
        $municipios = Municipio::where('nombre', 'LIKE', "%{$q}%")
            ->select('id', 'nombre', 'slug')
            ->orderBy('nombre')
            ->limit(5)
            ->get()
            ->map(fn($m) => [
                'id'   => 'muni_' . $m->slug,
                'text' => $m->nombre,
                'type' => 'Municipio',
                'icon' => 'fa-map-marker-alt',
                'url'  => route('ficha-municipal.perfil', $m->slug),
            ]);
        $results = $results->merge($municipios);

        // 2. Indicadores (límite 5)
        $indicadores = Indicador::where('visible_en_ficha', true)
            ->whereHas('tematica', fn($tematica) => $tematica
                ->where('visible_en_ficha', true)
                ->whereHas('dimension', fn($dimension) => $dimension->where('visible_en_ficha', true)))
            ->where('nombre_amigable', 'LIKE', "%{$q}%")
            ->select('id', 'nombre_amigable')
            ->orderBy('nombre_amigable')
            ->limit(5)
            ->get()
            ->map(fn($i) => [
                'id'   => 'ind_' . $i->id,
                'text' => $i->nombre_amigable,
                'type' => 'Indicador',
                'icon' => 'fa-chart-line',
                'url'  => route('banco-indicadores.index', [
                    'indicador_id'  => $i->id,
                    'municipio_ids' => 'estatal',
                ]),
            ]);
        $results = $results->merge($indicadores);

        // 3. Microrregiones (límite 3) — Excluimos las que comparten nombre con municipios
        $microrregiones = Microrregion::where('nombre', 'LIKE', "%{$q}%")
            ->whereNotIn('nombre', ['Tehuacán', 'Puebla', 'Cuautlancingo'])
            ->select('id', 'nombre', 'slug')
            ->orderBy('nombre')
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'id'   => 'micro_' . $r->id,
                'text' => $r->nombre,
                'type' => 'Microrregión',
                'icon' => 'fa-layer-group',
                'url'  => route('regiones.micro.perfil', $r->slug),
            ]);
        $results = $results->merge($microrregiones);

        // 4. Macrorregiones (límite 3)
        $macrorregiones = Macrorregion::where('nombre', 'LIKE', "%{$q}%")
            ->select('id', 'nombre', 'slug')
            ->orderBy('nombre')
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'id'   => 'macro_' . $r->id,
                'text' => $r->nombre,
                'type' => 'Macrorregión',
                'icon' => 'fa-globe-americas',
                'url'  => route('regiones.macro.perfil', $r->slug),
            ]);
        $results = $results->merge($macrorregiones);

        return response()->json($results->values());
    }
}
