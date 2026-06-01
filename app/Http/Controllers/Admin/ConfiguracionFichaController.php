<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ConfiguracionFicha;
use App\Models\Indicador;
use Illuminate\Support\Str;

class ConfiguracionFichaController extends Controller
{
    public function index()
    {
        $configuraciones = ConfiguracionFicha::with('indicador')
            ->orderBy('seccion')
            ->orderBy('orden')
            ->paginate(10);
            
        return view('admin.configuracion_fichas.index', compact('configuraciones'));
    }

    public function create()
    {
        $configuracion = new ConfiguracionFicha();
        $indicadores = Indicador::where('es_complejo', false)->orderBy('nombre_amigable')->get();
        $total_indicadores= $indicadores->count();
        $secciones = [
            'demografia' => 'Demografía',
            'economia' => 'Economía',
            'salud' => 'Salud',
            'educacion' => 'Educación',
            'vivienda' => 'Vivienda',
            'seguridad' => 'Seguridad',
            'medio_ambiente' => 'Medio Ambiente'
        ];
        $visualizaciones = ['kpi', 'piramide', 'treemap', 'barras', 'lineas', 'mapa'];
        
        return view('admin.configuracion_fichas.form', compact('configuracion', 'indicadores', 'secciones', 'visualizaciones', 'total_indicadores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'indicador_id' => 'required|exists:indicadors,id',
            'seccion' => 'required|string',
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
        $secciones = [
            'demografia' => 'Demografía',
            'economia' => 'Economía',
            'salud' => 'Salud',
            'educacion' => 'Educación',
            'vivienda' => 'Vivienda',
            'seguridad' => 'Seguridad',
            'medio_ambiente' => 'Medio Ambiente'
        ];
        $visualizaciones = ['kpi', 'piramide', 'treemap', 'barras', 'lineas', 'mapa'];
        
        return view('admin.configuracion_fichas.form', compact('configuracion', 'indicadores', 'secciones', 'visualizaciones', 'variablesIndicador'));
    }

    public function update(Request $request, $id)
    {
        $configuracion = ConfiguracionFicha::findOrFail($id);
        
        $validated = $request->validate([
            'indicador_id' => 'required|exists:indicadors,id',
            'seccion' => 'required|string',
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
}
