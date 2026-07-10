<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ConfiguracionFicha;
use App\Models\Indicador;
use App\Models\Variable;
use App\Models\DatoHistorico;
use Illuminate\Support\Str;

class ConfiguracionFichaController extends Controller
{
    public function index()
    {
        $configuraciones = ConfiguracionFicha::with('indicador')
            ->orderBy('orden')
            ->paginate(10);
            
        return view('admin.configuracion_fichas.index', compact('configuraciones'));
    }

    public function create()
    {
        $configuracion = new ConfiguracionFicha();
        $indicadores = Indicador::where('es_complejo', false)->orderBy('nombre_amigable')->get();
        $total_indicadores= $indicadores->count();
        $visualizaciones = ['kpi', 'piramide', 'treemap', 'barras', 'lineas', 'mapa', 'scatter'];
        
        return view('admin.configuracion_fichas.form', compact('configuracion', 'indicadores', 'visualizaciones', 'total_indicadores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'indicador_id' => 'required|exists:indicadors,id',
            'orden' => 'required|integer',
            'tipo_visualizacion' => 'required|string',
            'titulo_reporte' => 'nullable|string',
            'subtitulo_reporte' => 'nullable|string',
            'plantilla_narrativa' => 'nullable|string',
            'clase_grid' => 'required|string',
            'icono' => 'nullable|string',
            'anios_historial' => 'required|integer|min:1',
            'ajustes_visuales' => 'nullable|json',
        ]);

        $validated['mostrar_comparativa'] = $request->has('mostrar_comparativa');
        $validated['activo'] = $request->has('activo');
        
        if ($request->filled('ajustes_visuales')) {
            $validated['ajustes_visuales'] = json_decode($request->ajustes_visuales, true);
        } else {
            $validated['ajustes_visuales'] = [];
        }
        $validated['ajustes_visuales']['benchmark_mode'] = $request->input('benchmark_mode', 'avg');

        $indicador = Indicador::with('tematica.dimension')->findOrFail($request->indicador_id);
        $validated['seccion'] = Str::lower($indicador->tematica->dimension->nombre_tecnico ?? 'general');

        $configuracion = ConfiguracionFicha::create($validated);

        if ($request->has('variables_ids')) {
            $configuracion->variables()->sync($request->variables_ids);
        }

        return redirect()->route('admin.configuracion-fichas.index')
            ->with('success', 'Configuración creada correctamente.');
    }

    public function edit($id)
    {
        $configuracion = ConfiguracionFicha::with('variables')->findOrFail($id);
        $variablesIndicador = $configuracion->indicador->variables()->orderBy('orden')->get();
        $indicadores = Indicador::where('es_complejo', false)->orderBy('nombre_amigable')->get();
        $visualizaciones = ['kpi', 'piramide', 'treemap', 'barras', 'lineas', 'mapa', 'scatter'];
        
        return view('admin.configuracion_fichas.form', compact('configuracion', 'indicadores', 'visualizaciones', 'variablesIndicador'));
    }

    public function update(Request $request, $id)
    {
        $configuracion = ConfiguracionFicha::findOrFail($id);
        
        $validated = $request->validate([
            'indicador_id' => 'required|exists:indicadors,id',
            'orden' => 'required|integer',
            'tipo_visualizacion' => 'required|string',
            'titulo_reporte' => 'nullable|string',
            'subtitulo_reporte' => 'nullable|string',
            'plantilla_narrativa' => 'nullable|string',
            'clase_grid' => 'required|string',
            'icono' => 'nullable|string',
            'anios_historial' => 'required|integer|min:1',
            'ajustes_visuales' => 'nullable|json',
        ]);

        $validated['mostrar_comparativa'] = $request->has('mostrar_comparativa');
        $validated['activo'] = $request->has('activo');
        
        if ($request->filled('ajustes_visuales')) {
            $validated['ajustes_visuales'] = json_decode($request->ajustes_visuales, true);
        } else {
            $validated['ajustes_visuales'] = [];
        }
        $validated['ajustes_visuales']['benchmark_mode'] = $request->input('benchmark_mode', 'avg');

        $indicador = Indicador::with('tematica.dimension')->findOrFail($request->indicador_id);
        $validated['seccion'] = Str::lower($indicador->tematica->dimension->nombre_tecnico ?? 'general');

        $configuracion->update($validated);

        if ($request->has('variables_ids')) {
            $configuracion->variables()->sync($request->variables_ids);
        } else {
            $configuracion->variables()->detach();
        }

        return redirect()->route('admin.configuracion-fichas.index')
            ->with('success', 'Configuración actualizada correctamente.');
    }

    public function destroy($id)
    {
        $configuracion = ConfiguracionFicha::findOrFail($id);
        $configuracion->delete();

        return redirect()->route('admin.configuracion-fichas.index')
            ->with('success', 'Configuración eliminada correctamente.');
    }

    public function getVariablesPorIndicador(Indicador $indicador)
    {
        $variables = $indicador->variables()->orderBy('orden')->get(['id', 'nombre_amigable']);
        
        $mapped = $variables->map(function($var) {
            return [
                'id' => $var->id,
                'text' => $var->nombre_amigable,
                'tag_valor' => '{' . Str::slug($var->nombre_amigable, '_') . '_valor}',
                'tag_nombre' => '{' . Str::slug($var->nombre_amigable, '_') . '_nombre}'
            ];
        });

        return response()->json($mapped);
    }

    public function getAllVariables()
    {
        $indicadores = Indicador::with(['variables' => function($q) {
            $q->orderBy('orden');
        }])->where('es_complejo', false)->orderBy('nombre_amigable')->get();

        $mapped = [];
        foreach ($indicadores as $ind) {
            if ($ind->variables->count() > 0) {
                $options = $ind->variables->map(function($var) use ($ind) {
                    return [
                        'id' => $var->id,
                        'text' => $ind->nombre_amigable . ' - ' . $var->nombre_amigable,
                        'tag_valor' => '{' . Str::slug($var->nombre_amigable, '_') . '_valor}',
                        'tag_nombre' => '{' . Str::slug($var->nombre_amigable, '_') . '_nombre}'
                    ];
                })->toArray();
                
                $mapped = array_merge($mapped, $options);
            }
        }

        return response()->json($mapped);
    }

    public function getAniosDisponibles(Request $request)
    {
        $variablesIds = $request->input('variables_ids');
        $indicadorId = $request->input('indicador_id');

        if (!is_array($variablesIds)) {
            $variablesIds = $variablesIds ? explode(',', $variablesIds) : [];
        }
        $variablesIds = array_filter(array_map('intval', $variablesIds));

        if (empty($variablesIds) && $indicadorId) {
            $variablesIds = Variable::where('indicador_id', $indicadorId)->pluck('id')->toArray();
        }

        if (empty($variablesIds)) {
            return response()->json([
                'success' => true,
                'anios_disponibles' => 0,
                'anios' => []
            ]);
        }

        $anios = DatoHistorico::whereIn('variable_id', $variablesIds)
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();

        return response()->json([
            'success' => true,
            'anios_disponibles' => count($anios),
            'anios' => $anios
        ]);
    }
}
