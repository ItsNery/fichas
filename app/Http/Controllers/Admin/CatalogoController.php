<?php
namespace App\Http\Controllers\Admin;

use App\Exports\CatalogoExport;
use App\Exports\DimensionesExport;
use App\Exports\IndicadoresExport;
use App\Exports\MacrorregionesExport;
use App\Exports\MicrorregionesExport;
use App\Exports\MunicipiosExport;
use App\Exports\TematicasExport;
use App\Exports\VariablesExport;
use App\Http\Controllers\Controller;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\Variable;
use App\Services\IndicadorConstruidoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CatalogoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:catalogos.ver')->only([
            'index', 'export', 'exportDimensiones', 'exportTematicas', 'exportIndicadores', 'exportVariables',
            'crearIndicador', 'editarIndicador', 'previewConstruido',
        ]);
        $this->middleware('permission:catalogos.crear')->only([
            'storeDimension', 'storeTematica', 'storeIndicador', 'storeVariable', 'guardarIndicador', 'generarConstruido',
        ]);
        $this->middleware('permission:catalogos.editar')->only([
            'updateDimension', 'updateTematica', 'updateIndicador', 'updateVariable', 'actualizarIndicador', 'regenerarConstruido',
        ]);
        $this->middleware('permission:catalogos.eliminar')->only([
            'destroyDimension', 'destroyTematica', 'destroyIndicador', 'destroyVariable',
        ]);
    }

    public function index()
    {
        return view('catalogo.index', [
            'dimensiones' => Dimension::with([
                'tematicas' => function ($q) { $q->orderBy('orden')->orderBy('nombre'); },
                'tematicas.indicadores' => function ($q) { $q->orderBy('orden')->orderBy('nombre_amigable'); },
                'tematicas.indicadores.variables' => function ($q) { $q->orderBy('orden')->orderBy('nombre_amigable'); }
            ])->orderBy('orden')->orderBy('nombre')->get(),
            'tematicas'   => Tematica::orderBy('orden')->orderBy('nombre')->get(),
            'indicadores' => Indicador::orderBy('orden')->orderBy('nombre_amigable')->get(),
            'variables'   => Variable::with('indicador')->orderBy('nombre_amigable')->get(),
        ]);
    }

    public function export()
    {
        $fileName = 'catalogo_indicadores_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new CatalogoExport, $fileName);
    }

    // --- DIMENSIONES ---
    public function storeDimension(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|unique:dimensions,nombre',
            'nombre_tecnico' => 'required|string|unique:dimensions,nombre_tecnico',
            'color'          => 'required|string|max:7',
            'orden'          => 'nullable|integer',
            'visible_en_ficha' => 'nullable|boolean',
        ]);
        $validated['visible_en_ficha'] = $request->has('visible_en_ficha');
        $dimension = Dimension::create($validated);
        return response()->json($dimension);
    }

    public function updateDimension(Request $request, Dimension $dimension)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|unique:dimensions,nombre,' . $dimension->id,
            'nombre_tecnico' => 'required|string|unique:dimensions,nombre_tecnico,' . $dimension->id,
            'color'          => 'nullable|string|max:7',
            'orden'          => 'nullable|integer',
            'visible_en_ficha' => 'nullable|boolean',
        ]);
        $validated['visible_en_ficha'] = $request->has('visible_en_ficha');
        $dimension->update($validated);
        return response()->json($dimension);
    }

    public function destroyDimension(Dimension $dimension)
    {
        $dimension->delete();
        return response()->json(['success' => true]);
    }

    // --- TEMÁTICAS ---
    public function storeTematica(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:255',
            'nombre_tecnico' => 'required|string|unique:tematicas,nombre_tecnico',
            'parent_id'      => 'required|exists:dimensions,id',
            'orden'          => 'nullable|integer',
            'visible_en_ficha' => 'nullable|boolean',
        ]);
        $validated['visible_en_ficha'] = $request->has('visible_en_ficha');
        $tematica = Tematica::create([
            'nombre'         => $validated['nombre'],
            'nombre_tecnico' => $validated['nombre_tecnico'],
            'dimension_id'   => $validated['parent_id'],
            'orden'          => $validated['orden'] ?? 0,
        ]);
        return response()->json($tematica);
    }

    public function updateTematica(Request $request, Tematica $tematica)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:255',
            'nombre_tecnico' => 'required|string|unique:tematicas,nombre_tecnico,' . $tematica->id,
            'orden'          => 'nullable|integer',
            'visible_en_ficha' => 'nullable|boolean',
        ]);
        $validated['visible_en_ficha'] = $request->has('visible_en_ficha');
        $tematica->update($validated);
        return response()->json($tematica);
    }

    public function destroyTematica(Tematica $tematica)
    {
        $tematica->delete();
        return response()->json(['success' => true]);
    }

    // --- INDICADORES (vía AJAX para el modal) ---
    public function storeIndicador(Request $request)
    {
        $validated = $request->validate([
            'nombre_amigable'      => 'required|string|max:255',
            'nombre_tecnico'       => 'nullable|string|max:255',
            'parent_id'            => 'required|exists:tematicas,id',
            'descripcion'          => 'nullable|string',
            'fuente'               => 'nullable|string',
            'tipo_dato'            => 'required|string|in:absoluto,porcentaje,tasa,indice',
            'metodo_calculo'       => 'nullable|string',
            'tipo_grafico_default' => 'nullable|string|in:Barras,Lineal,Piramide',
            'solo_resumen'         => 'nullable|boolean',
            'priorizar_total'      => 'nullable|boolean',
            'es_complejo'          => 'nullable|boolean',
            'polaridad'            => 'nullable|string|in:asendente,descendente,neutro',
            'orden'                => 'nullable|integer',
            'visible_en_ficha'    => 'nullable|boolean',
        ]);
        $validated['visible_en_ficha'] = $request->has('visible_en_ficha');

        $indicador = Indicador::create([
            'nombre_amigable'      => $validated['nombre_amigable'],
            'nombre_tecnico'       => $this->nombreTecnicoIndicador($validated['nombre_tecnico'] ?? '', $validated['nombre_amigable']),
            'tematica_id'          => $validated['parent_id'],
            'descripcion'          => $validated['descripcion'],
            'fuente'               => $validated['fuente'],
            'tipo_dato'            => $validated['tipo_dato'],
            'metodo_calculo'       => $validated['metodo_calculo'],
            'tipo_grafico_default' => $request->input('tipo_grafico_default'),
            'solo_resumen'         => $request->has('solo_resumen'),
            'priorizar_total'      => $request->has('priorizar_total'),
            'es_complejo'          => $request->has('es_complejo'),
            'polaridad'            => $validated['polaridad'] ?? 'neutro',
            'orden'                => $validated['orden'] ?? 0,
            'visible_en_ficha'     => $request->has('visible_en_ficha'),
        ]);
        return response()->json($indicador);
    }

    public function updateIndicador(Request $request, Indicador $indicador)
    {
        $validated = $request->validate([
            'nombre_amigable'      => 'required|string|max:255',
            'nombre_tecnico'       => 'nullable|string|max:255',
            'tematica_id'          => 'required|exists:tematicas,id',
            'descripcion'          => 'nullable|string',
            'fuente'               => 'nullable|string',
            'metodo_calculo'       => 'nullable|string',
            'tipo_dato'            => 'required|string|in:absoluto,porcentaje,tasa,indice',
            'tipo_grafico_default' => 'nullable|string|in:Barras,Lineal,Piramide',
            'polaridad'            => 'nullable|string|in:asendente,descendente,neutro',
            'orden'                => 'nullable|integer',
            'visible_en_ficha'    => 'nullable|boolean',
        ]);
        $validated['visible_en_ficha'] = $request->has('visible_en_ficha');

        $validated['nombre_tecnico'] = $this->nombreTecnicoIndicador(
            $validated['nombre_tecnico'] ?? '',
            $validated['nombre_amigable'],
            $indicador->id,
        );
        $indicador->update($validated);

        $indicador->solo_resumen    = $request->has('solo_resumen');
        $indicador->es_complejo     = $request->has('es_complejo');
        $indicador->priorizar_total = $request->has('priorizar_total');
        $indicador->save();

        return response()->json($indicador);
    }

    public function destroyIndicador(Indicador $indicador)
    {
        $indicador->delete();
        return response()->json(['success' => true]);
    }

    // --- PÁGINA DEDICADA: CREAR INDICADOR ---
    public function crearIndicador(Request $request)
    {
        $request->validate([
            'tematica_id' => 'nullable|exists:tematicas,id',
        ]);

        return view('catalogo.indicador-form', [
            'indicador'  => null,
            'tematicaId' => $request->input('tematica_id'),
            'tematicas'  => Tematica::with('dimension')->orderBy('nombre')->get(),
            'variables'  => Variable::with('indicador.tematica')->orderBy('nombre_amigable')->get(),
        ]);
    }

    public function guardarIndicador(Request $request)
    {
        $validated = $request->validate([
            'nombre_amigable'      => 'required|string|max:255',
            'nombre_tecnico'       => 'nullable|string|max:255',
            'tematica_id'          => 'required|exists:tematicas,id',
            'descripcion'          => 'nullable|string',
            'fuente'               => 'nullable|string',
            'tipo_dato'            => 'required|string|in:absoluto,porcentaje,tasa,indice',
            'metodo_calculo'       => 'nullable|string',
            'tipo_grafico_default' => 'nullable|string|in:Barras,Lineal,Piramide',
            'solo_resumen'         => 'nullable|boolean',
            'es_complejo'          => 'nullable|boolean',
            'priorizar_total'      => 'nullable|boolean',
            'polaridad'            => 'nullable|string|in:asendente,descendente,neutro',
            'orden'                => 'nullable|integer',
            'visible_en_ficha'     => 'nullable|boolean',
        ]);

        $indicador = Indicador::create([
            'nombre_amigable'      => $validated['nombre_amigable'],
            'nombre_tecnico'       => $this->nombreTecnicoIndicador($validated['nombre_tecnico'] ?? '', $validated['nombre_amigable']),
            'tematica_id'          => $validated['tematica_id'],
            'descripcion'          => $validated['descripcion'],
            'fuente'               => $validated['fuente'],
            'tipo_dato'            => $validated['tipo_dato'],
            'metodo_calculo'       => $validated['metodo_calculo'],
            'tipo_grafico_default' => $request->input('tipo_grafico_default'),
            'solo_resumen'         => $request->has('solo_resumen'),
            'es_complejo'          => $request->has('es_complejo'),
            'priorizar_total'      => $request->has('priorizar_total'),
            'polaridad'            => $validated['polaridad'] ?? 'neutro',
            'orden'                => $validated['orden'] ?? 0,
            'visible_en_ficha'     => $request->has('visible_en_ficha'),
        ]);

        $this->sincronizarVariables($request, $indicador);

        return redirect()->route('admin.catalogos.indicadores.editar', $indicador)
            ->with('success', 'Indicador creado correctamente.');
    }

    // --- PÁGINA DEDICADA: EDITAR INDICADOR ---
    public function editarIndicador(Indicador $indicador)
    {
        return view('catalogo.indicador-form', [
            'indicador'  => $indicador->load('variables'),
            'tematicas'  => Tematica::with('dimension')->orderBy('nombre')->get(),
            'variables'  => Variable::with('indicador.tematica')->orderBy('nombre_amigable')->get(),
        ]);
    }

    public function actualizarIndicador(Request $request, Indicador $indicador)
    {
        $validated = $request->validate([
            'nombre_amigable'      => 'required|string|max:255',
            'nombre_tecnico'       => 'nullable|string|max:255',
            'tematica_id'          => 'required|exists:tematicas,id',
            'descripcion'          => 'nullable|string',
            'fuente'               => 'nullable|string',
            'metodo_calculo'       => 'nullable|string',
            'tipo_dato'            => 'required|string|in:absoluto,porcentaje,tasa,indice',
            'tipo_grafico_default' => 'nullable|string|in:Barras,Lineal,Piramide',
            'polaridad'            => 'nullable|string|in:asendente,descendente,neutro',
            'orden'                => 'nullable|integer',
            'visible_en_ficha'     => 'nullable|boolean',
        ]);

        $validated['nombre_tecnico'] = $this->nombreTecnicoIndicador(
            $validated['nombre_tecnico'] ?? '',
            $validated['nombre_amigable'],
            $indicador->id,
        );
        $validated['visible_en_ficha'] = $request->has('visible_en_ficha');
        $indicador->update($validated);
        $indicador->solo_resumen    = $request->has('solo_resumen');
        $indicador->es_complejo     = $request->has('es_complejo');
        $indicador->priorizar_total = $request->has('priorizar_total');
        $indicador->save();

        $this->sincronizarVariables($request, $indicador);

        return redirect()->route('admin.catalogos.indicadores.editar', $indicador)
            ->with('success', 'Indicador actualizado correctamente.');
    }

    private function nombreTecnicoIndicador(string $candidate, string $fallback, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::ascii($candidate ?: $fallback), '_') ?: 'indicador';
        $name = $base;
        $suffix = 2;

        while (Indicador::where('nombre_tecnico', $name)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $name = $base . '_' . $suffix++;
        }

        return $name;
    }

    private function nombreTecnicoVariable(string $candidate, Indicador $indicador, string $fallback, ?int $ignoreId = null): string
    {
        $default = $indicador->nombre_tecnico . '_' . $fallback;
        $base = Str::slug(Str::ascii($candidate ?: $default), '_') ?: 'variable';
        $name = $base;
        $suffix = 2;

        while (Variable::where('nombre_tecnico', $name)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $name = $base . '_' . $suffix++;
        }

        return $name;
    }

    private function sincronizarVariables(Request $request, Indicador $indicador)
    {
        $variablesData = $request->input('variables', []);

        $keepIds = [];
        foreach ($variablesData as $idx => $varData) {
            if (empty($varData['nombre_tecnico']) && empty($varData['nombre_amigable'])) {
                continue;
            }

            $data = [
                'indicador_id'    => $indicador->id,
                'nombre_tecnico'  => $this->nombreTecnicoVariable(
                    $varData['nombre_tecnico'] ?? '',
                    $indicador,
                    $varData['nombre_amigable'] ?? 'Variable',
                    !empty($varData['id']) ? (int) $varData['id'] : null,
                ),
                'nombre_amigable' => $varData['nombre_amigable'] ?? 'Variable',
                'unidad_medida'   => $varData['unidad_medida'] ?? null,
                'orden'           => $varData['orden'] ?? $idx,
                'es_destacada'    => !empty($varData['es_destacada']),
                'es_kpi'          => !empty($varData['es_kpi']),
                'visible_en_ficha' => !empty($varData['visible_en_ficha']),
                'es_construida'   => !empty($varData['es_construida']),
                'tipo_valor'      => $varData['tipo_valor'] ?? null,
                'mapeo_valores'   => null,
            ];

            if (!empty($varData['es_construida'])) {
                $data['formula_tipo'] = $varData['formula_tipo'] ?? 'division';
                $config = match ($data['formula_tipo']) {
                    'sumatoria' => [
                        'variable_ids' => collect($varData['formula_variable_ids'] ?? [])
                            ->filter()
                            ->map(fn($id) => (int) $id)
                            ->unique()
                            ->values()
                            ->all(),
                    ],
                    'tasa_crecimiento' => [
                        'variable_id'   => (int) ($varData['formula_variable_id'] ?? 0),
                        'multiplicador' => (float) ($varData['formula_multiplicador'] ?? 100),
                    ],
                    default => [
                        'numerador_variable_id'   => (int) ($varData['formula_numerador_id'] ?? 0),
                        'denominador_variable_id' => (int) ($varData['formula_denominador_id'] ?? 0),
                        'multiplicador'           => (float) ($varData['formula_multiplicador'] ?? 1),
                    ],
                };
                $data['formula_config'] = $config;
            } else {
                $data['formula_tipo'] = null;
                $data['formula_config'] = null;
            }

            if (!empty($varData['id'])) {
                $variable = Variable::findOrFail($varData['id']);
                $variable->update($data);
                $keepIds[] = $variable->id;
            } else {
                $variable = Variable::create($data);
                $keepIds[] = $variable->id;
            }
        }

        Variable::where('indicador_id', $indicador->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    // --- PREVIEW / GENERAR / REGENERAR (ahora reciben Variable vía parámetro) ---
    public function previewConstruido(Variable $variable, IndicadorConstruidoService $service)
    {
        Gate::authorize('catalogos.ver');

        if (!$variable->es_construida || !$variable->formula_config) {
            return response()->json(['error' => 'La variable no es construida o no tiene fórmula.'], 422);
        }

        $data = $service->calcularPrevisualizacion($variable);

        return response()->json([
            'rows'  => $data,
            'total' => count($data),
        ]);
    }

    public function generarConstruido(Variable $variable, IndicadorConstruidoService $service)
    {
        Gate::authorize('catalogos.crear');

        if (!$variable->es_construida || !$variable->formula_config) {
            return response()->json(['error' => 'La variable no es construida o no tiene fórmula.'], 422);
        }

        $lote = $service->crearLote($variable);
        $count = $service->generarDatosHistoricos($variable, $lote);

        $lote->update(['total_filas' => $count]);

        return response()->json([
            'success' => true,
            'message' => "{$count} registros generados correctamente.",
            'lote_id' => $lote->id,
        ]);
    }

    public function regenerarConstruido(Variable $variable, IndicadorConstruidoService $service)
    {
        Gate::authorize('catalogos.editar');

        if (!$variable->es_construida || !$variable->formula_config) {
            return response()->json(['error' => 'La variable no es construida o no tiene fórmula.'], 422);
        }

        $count = $service->regenerar($variable);

        return response()->json([
            'success' => true,
            'message' => "{$count} registros regenerados correctamente.",
        ]);
    }

    // --- VARIABLES (modal AJAX) ---
    public function storeVariable(Request $request)
    {
        $validated = $request->validate([
            'nombre_tecnico'  => 'nullable|string|max:255',
            'nombre_amigable' => 'required|string',
            'parent_id'       => 'required|exists:indicadors,id',
            'unidad_medida'   => 'nullable|string',
            'es_destacada'    => 'nullable|boolean',
            'es_kpi'          => 'nullable|boolean',
            'orden'           => 'nullable|integer',
            'tipo_valor'      => 'nullable|string',
            'mapeo_valores'   => 'nullable|json',
        ]);

        $mapeo = $validated['mapeo_valores'] ?? null;
        if (is_string($mapeo)) { $decoded = json_decode($mapeo, true); $mapeo = is_array($decoded) ? $decoded : null; }
        $indicador = Indicador::findOrFail($validated['parent_id']);

        $variable = Variable::create([
            'nombre_tecnico'  => $this->nombreTecnicoVariable(
                $validated['nombre_tecnico'] ?? '',
                $indicador,
                $validated['nombre_amigable'],
            ),
            'nombre_amigable' => $validated['nombre_amigable'],
            'indicador_id'    => $validated['parent_id'],
            'unidad_medida'   => $validated['unidad_medida'],
            'es_destacada'    => $request->has('es_destacada'),
            'es_kpi'          => $request->has('es_kpi'),
            'visible_en_ficha' => $request->has('visible_en_ficha'),
            'orden'           => $validated['orden'] ?? 0,
            'tipo_valor'      => $validated['tipo_valor'] ?? null,
            'mapeo_valores'   => $mapeo,
        ]);
        return response()->json($variable);
    }

    public function updateVariable(Request $request, Variable $variable)
    {
        $data = $request->validate([
            'nombre_tecnico'  => 'nullable|string|max:255',
            'nombre_amigable' => 'required|string',
            'indicador_id'    => 'required|exists:indicadors,id',
            'unidad_medida'   => 'nullable|string',
            'orden'           => 'nullable|integer',
            'tipo_valor'      => 'nullable|string',
            'mapeo_valores'   => 'nullable|json',
        ]);

        if (isset($data['mapeo_valores']) && is_string($data['mapeo_valores'])) {
            $decoded = json_decode($data['mapeo_valores'], true);
            $data['mapeo_valores'] = is_array($decoded) ? $decoded : null;
        }

        $data['nombre_tecnico'] = $this->nombreTecnicoVariable(
            $data['nombre_tecnico'] ?? '',
            $variable->indicador,
            $data['nombre_amigable'],
            $variable->id,
        );

        $data['es_destacada'] = $request->has('es_destacada');
        $data['es_kpi']       = $request->has('es_kpi');
        $data['visible_en_ficha'] = $request->has('visible_en_ficha');

        $variable->update($data);

        return response()->json($variable);
    }

    public function destroyVariable(Variable $variable)
    {
        $variable->delete();
        return response()->json(['success' => true]);
    }

    // --- EXPORTS ---
    public function exportDimensiones() { return Excel::download(new DimensionesExport, 'catalogo-dimensiones.xlsx'); }
    public function exportTematicas() { return Excel::download(new TematicasExport, 'catalogo-tematicas.xlsx'); }
    public function exportIndicadores() { return Excel::download(new IndicadoresExport, 'catalogo-indicadores.xlsx'); }
    public function exportVariables() { return Excel::download(new VariablesExport, 'catalogo-variables.xlsx'); }

    public function exportarCatalogoPublico($tipo)
    {
        switch ($tipo) {
            case 'dimensiones': return $this->exportDimensiones();
            case 'tematicas': return $this->exportTematicas();
            case 'indicadores': return $this->exportIndicadores();
            case 'variables': return $this->exportVariables();
            case 'municipios': return Excel::download(new MunicipiosExport, 'catalogo-municipios.xlsx');
            case 'microrregiones': return Excel::download(new MicrorregionesExport, 'catalogo-microrregiones.xlsx');
            case 'macrorregiones': return Excel::download(new MacrorregionesExport, 'catalogo-macrorregiones.xlsx');
            default: return redirect()->route('datos-abiertos.index')->with('error', 'Tipo de catálogo no válido.');
        }
    }
}
