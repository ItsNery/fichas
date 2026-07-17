<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ConfiguracionFicha;
use App\Models\Indicador;
use App\Models\Variable;
use App\Models\DatoHistorico;
use App\Services\CorrelationService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConfiguracionFichaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:configuracion-fichas.ver')->only([
            'index', 'getVariablesPorIndicador', 'getAllVariables', 'getAniosDisponibles', 'calcularCorrelacion',
        ]);
        $this->middleware('permission:configuracion-fichas.crear')->only(['create', 'store']);
        $this->middleware('permission:configuracion-fichas.editar')->only(['edit', 'update']);
        $this->middleware('permission:configuracion-fichas.eliminar')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = ConfiguracionFicha::with('indicador.tematica.dimension');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('titulo_reporte', 'like', "%{$search}%")
                    ->orWhere('subtitulo_reporte', 'like', "%{$search}%")
                    ->orWhereHas('indicador', function ($indicator) use ($search) {
                        $indicator->where('nombre_amigable', 'like', "%{$search}%")
                            ->orWhere('nombre_tecnico', 'like', "%{$search}%")
                            ->orWhereHas('tematica', function ($tematica) use ($search) {
                                $tematica->where('nombre', 'like', "%{$search}%")
                                    ->orWhereHas('dimension', fn($dimension) => $dimension->where('nombre', 'like', "%{$search}%"));
                            });
                    });
            });
        }

        if ($visualizacion = $request->input('visualizacion')) {
            $query->where('tipo_visualizacion', $visualizacion);
        }

        if ($request->input('estado') === 'activo') {
            $query->where('activo', true);
        } elseif ($request->input('estado') === 'inactivo') {
            $query->where('activo', false);
        }

        $configuraciones = $query
            ->orderBy('orden')
            ->paginate(10)
            ->withQueryString();

        $visualizaciones = ['kpi', 'piramide', 'treemap', 'barras', 'lineas', 'mapa', 'scatter'];
            
        return view('admin.configuracion_fichas.index', compact('configuraciones', 'visualizaciones'));
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
            'subtitulo_reporte' => 'nullable|string|max:255',
            'plantilla_narrativa' => 'nullable|string',
            'clase_grid' => 'required|string',
            'icono' => 'nullable|string',
            'anios_historial' => 'required|integer|min:1',
            'ajustes_visuales' => 'nullable|json',
            'variables_ids' => 'nullable|array',
            'variables_ids.*' => 'integer|distinct|exists:variables,id',
        ]);

        $this->validateScatterVariables($request);

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
        $variablesIndicador = $configuracion->tipo_visualizacion === 'scatter'
            ? collect()
            : $configuracion->indicador->variables()->orderBy('orden')->get();
        $indicadores = Indicador::where('es_complejo', false)->orderBy('nombre_amigable')->get();
        $total_indicadores = $indicadores->count();
        $visualizaciones = ['kpi', 'piramide', 'treemap', 'barras', 'lineas', 'mapa', 'scatter'];
        
        return view('admin.configuracion_fichas.form', compact('configuracion', 'indicadores', 'visualizaciones', 'variablesIndicador', 'total_indicadores'));
    }

    public function update(Request $request, $id)
    {
        $configuracion = ConfiguracionFicha::findOrFail($id);
        
        $validated = $request->validate([
            'indicador_id' => 'required|exists:indicadors,id',
            'orden' => 'required|integer',
            'tipo_visualizacion' => 'required|string',
            'titulo_reporte' => 'nullable|string',
            'subtitulo_reporte' => 'nullable|string|max:255',
            'plantilla_narrativa' => 'nullable|string',
            'clase_grid' => 'required|string',
            'icono' => 'nullable|string',
            'anios_historial' => 'required|integer|min:1',
            'ajustes_visuales' => 'nullable|json',
            'variables_ids' => 'nullable|array',
            'variables_ids.*' => 'integer|distinct|exists:variables,id',
        ]);

        $this->validateScatterVariables($request);

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
        $variables = $indicador->variables()->orderBy('orden')->get(['id', 'indicador_id', 'nombre_amigable', 'unidad_medida']);
        
        $mapped = $variables->map(function($var) use ($indicador) {
            return [
                'id' => $var->id,
                'text' => $var->nombre_amigable,
                'indicador' => $indicador->nombre_amigable,
                'unidad' => $var->unidad_medida,
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
                        'indicador' => $ind->nombre_amigable,
                        'unidad' => $var->unidad_medida,
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

    public function calcularCorrelacion(Request $request, CorrelationService $correlationService)
    {
        $validated = $request->validate([
            'variable_x_id' => 'required|integer|different:variable_y_id|exists:variables,id',
            'variable_y_id' => 'required|integer|different:variable_x_id|exists:variables,id',
            'incluir_spearman' => 'sometimes|boolean',
        ]);

        $variableX = Variable::findOrFail($validated['variable_x_id']);
        $variableY = Variable::findOrFail($validated['variable_y_id']);
        $yearX = DatoHistorico::where('variable_id', $variableX->id)->max('anio');
        $yearY = DatoHistorico::where('variable_id', $variableY->id)->max('anio');

        if (!$yearX || !$yearY) {
            return response()->json([
                'success' => false,
                'message' => 'Una de las variables no tiene datos históricos disponibles.',
            ], 422);
        }

        $valuesX = DatoHistorico::where('variable_id', $variableX->id)
            ->where('anio', $yearX)
            ->pluck('valor', 'municipio_id');
        $valuesY = DatoHistorico::where('variable_id', $variableY->id)
            ->where('anio', $yearY)
            ->pluck('valor', 'municipio_id');
        $points = [];

        foreach ($valuesX as $municipalityId => $valueX) {
            if ($valuesY->has($municipalityId)) {
                $points[] = [(float) $valueX, (float) $valuesY->get($municipalityId)];
            }
        }

        $pearson = $correlationService->pearson($points);
        $includeSpearman = $request->boolean('incluir_spearman');
        $spearman = $includeSpearman ? $correlationService->spearman($points) : null;
        $strongestCoefficient = max(abs($pearson ?? 0), abs($spearman ?? 0));
        $diagnosis = $strongestCoefficient >= 0.7
            ? 'La relación es estadísticamente clara y puede aportar una visualización informativa.'
            : ($strongestCoefficient >= 0.4
                ? 'La relación es moderada; puede ser útil si existe una justificación temática.'
                : 'La relación es débil; revisa si el cruce aporta contexto antes de publicarlo.');

        if ($includeSpearman && $pearson !== null && $spearman !== null && abs($pearson - $spearman) >= 0.3) {
            $diagnosis .= ' La diferencia entre Pearson y Spearman sugiere revisar valores atípicos o una relación no lineal.';
        }

        return response()->json([
            'success' => true,
            'n' => count($points),
            'anio_x' => $yearX,
            'anio_y' => $yearY,
            'pearson' => $pearson,
            'pearson_lectura' => $correlationService->describe($pearson),
            'spearman' => $spearman,
            'spearman_lectura' => $includeSpearman ? $correlationService->describe($spearman, true) : null,
            'diagnostico' => $diagnosis,
            'advertencia' => count($points) < 5
                ? 'La muestra tiene menos de cinco municipios; interpreta el resultado con cautela.'
                : 'La correlación describe asociación, no causalidad.',
        ]);
    }

    private function validateScatterVariables(Request $request): void
    {
        if ($request->input('tipo_visualizacion') !== 'scatter') {
            return;
        }

        if (count($request->input('variables_ids', [])) !== 2) {
            throw ValidationException::withMessages([
                'variables_ids' => 'La gráfica de dispersión requiere exactamente dos variables: eje X y eje Y.',
            ]);
        }
    }
}
