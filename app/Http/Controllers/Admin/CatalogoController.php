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
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CatalogoController extends Controller
{
    /**
     * Display a listing of the resource (e.g., a catalog index page).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('catalogo.index', [
            'dimensiones' => Dimension::with([
                'tematicas' => function ($q) { $q->orderBy('orden')->orderBy('nombre'); },
                'tematicas.indicadores' => function ($q) { $q->orderBy('orden')->orderBy('nombre_amigable'); },
                'tematicas.indicadores.variables' => function ($q) { $q->orderBy('orden')->orderBy('nombre_amigable'); }
            ])->orderBy('orden')->orderBy('nombre')->get(),
            'tematicas'   => Tematica::orderBy('orden')->orderBy('nombre')->get(),           // Para el form de Indicadores
            'indicadores' => Indicador::orderBy('orden')->orderBy('nombre_amigable')->get(), // Para el form de Variables
        ]);
    }

    /**
     * Exporta el catálogo a Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        // Genera un nombre de archivo con la fecha actual, ej: catalogo_indicadores_2025-07-28.xlsx
        $fileName = 'catalogo_indicadores_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new CatalogoExport, $fileName);
    }

    // --- MÉTODOS CRUD PARA DIMENSIONES ---
    /**
     * Store a newly created Dimension resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse // ¡CAMBIO CLAVE AQUÍ!
     */
    public function storeDimension(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|unique:dimensions,nombre',
            'nombre_tecnico' => 'required|string|unique:dimensions,nombre_tecnico',
            'color'          => 'required|string|max:7',
            'orden'          => 'nullable|integer',
        ]);
        $dimension = Dimension::create($validated);
        return response()->json($dimension);
    }

    /**
     * Update the specified Dimension resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Dimension  $dimension // Asume que el modelo se llama 'Dimension'
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDimension(Request $request, Dimension $dimension)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|unique:dimensions,nombre,' . $dimension->id,
            'nombre_tecnico' => 'required|string|unique:dimensions,nombre_tecnico,' . $dimension->id,
            'color'          => 'nullable|string|max:7',
            'orden'          => 'nullable|integer',
        ]);
        $dimension->update($validated);
        return response()->json($dimension);
    }

    /**
     * Remove the specified Dimension resource from storage via API/AJAX.
     *
     * @param  \App\Models\Dimension  $dimension // Asume que el modelo se llama 'Dimension'
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyDimension(Dimension $dimension)
    {
        $dimension->delete();
        return response()->json(['success' => true]);
    }

    // --- MÉTODOS CRUD PARA  TEMÁTICAS---
    /**
     * Store a newly created Tematica resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeTematica(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:255',
            'nombre_tecnico' => 'required|string|unique:tematicas,nombre_tecnico',
            'parent_id'      => 'required|exists:dimensions,id',
            'orden'          => 'nullable|integer',
        ]);
        $tematica = Tematica::create([
            'nombre'         => $validated['nombre'],
            'nombre_tecnico' => $validated['nombre_tecnico'],
            'dimension_id'   => $validated['parent_id'],
            'orden'          => $validated['orden'] ?? 0,
        ]);
        return response()->json($tematica);
    }

    /**
     * Update the specified Tematica resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tematica  $tematica // Asume que el modelo se llama 'Tematica'
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTematica(Request $request, Tematica $tematica)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:255',
            'nombre_tecnico' => 'required|string|unique:tematicas,nombre_tecnico,' . $tematica->id,
            'orden'          => 'nullable|integer',
        ]);

        $tematica->update($validated);
        return response()->json($tematica);
    }

    /**
     * Remove the specified Tematica resource from storage via API/AJAX.
     *
     * @param  \App\Models\Tematica  $tematica // Asume que el modelo se llama 'Tematica'
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyTematica(Tematica $tematica)
    {
        $tematica->delete();
        return response()->json(['success' => true]);
    }

    // --- MÉTODOS CRUD PARA  INDICADORES---
    /**
     * Store a newly created Indicador resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeIndicador(Request $request)
    {
        $validated = $request->validate([
            'nombre_amigable'      => 'required|string|max:255',
            'nombre_tecnico'       => 'required|string|unique:indicadors,nombre_tecnico',
            'parent_id'            => 'required|exists:tematicas,id',
            'descripcion'          => 'nullable|string',
            'fuente'               => 'nullable|string',
            'tipo_dato'            => 'required|string',
            'metodo_calculo'       => 'nullable|string',
            'tipo_grafico_default' => 'nullable|string|in:Barras,Lineal,Piramide',
            'solo_resumen'         => 'nullable|boolean',
            'priorizar_total'      => 'nullable|boolean',
            'es_complejo'          => 'nullable|boolean',
            'polaridad'            => 'nullable|string|in:asendente,descendente,neutro',
            'orden'                => 'nullable|integer',
        ]);

        $indicador = Indicador::create([
            'nombre_amigable'      => $validated['nombre_amigable'],
            'nombre_tecnico'       => $validated['nombre_tecnico'],
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
        ]);
        return response()->json($indicador);
    }

    /**
     * Update the specified Indicador resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Indicador  $indicador // Asume que el modelo se llama 'Indicador'
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateIndicador(Request $request, Indicador $indicador)
    {
        $validated = $request->validate([
            'nombre_amigable'      => 'required|string|max:255',
            'nombre_tecnico'       => 'required|string|unique:indicadors,nombre_tecnico,' . $indicador->id,
            'tematica_id'          => 'required|exists:tematicas,id',
            'descripcion'          => 'nullable|string',
            'fuente'               => 'nullable|string',
            'metodo_calculo'       => 'nullable|string',
            'tipo_dato'            => 'required|string',
            'tipo_grafico_default' => 'nullable|string|in:Barras,Lineal,Piramide',
            'polaridad'            => 'nullable|string|in:asendente,descendente,neutro',
            'orden'                => 'nullable|integer',
        ]);

        // 1. Actualiza los campos que vienen del formulario validado
        $indicador->update($validated);

        // 2. Actualiza TODOS los campos booleanos (checkboxes) de forma consistente
        $indicador->solo_resumen    = $request->has('solo_resumen');
        $indicador->es_complejo     = $request->has('es_complejo');
        $indicador->priorizar_total = $request->has('priorizar_total'); // <-- AÑADIDO

        // 3. Guarda todos los cambios en la base de datos
        $indicador->save();

        return response()->json($indicador);
    }

    /**
     * Remove the specified Indicador resource from storage via API/AJAX.
     *
     * @param  \App\Models\Indicador  $indicador // Asume que el modelo se llama 'Indicador'
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyIndicador(Indicador $indicador)
    {
        $indicador->delete();
        return response()->json(['success' => true]);
    }

    // --- MÉTODOS CRUD PARA  VARIABLES---
    /**
     * Store a newly created Variable resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeVariable(Request $request)
    {
        $validated = $request->validate([
            'nombre_tecnico'  => 'required|string|unique:variables,nombre_tecnico',
            'nombre_amigable' => 'required|string',
            'parent_id'       => 'required|exists:indicadors,id', // parent_id es el indicador_id
            'unidad_medida'   => 'nullable|string',
            'es_destacada'    => 'nullable|boolean',
            'es_kpi'          => 'nullable|boolean',
            'orden'           => 'nullable|integer',
        ]);
        $variable = Variable::create([
            'nombre_tecnico'  => $validated['nombre_tecnico'],
            'nombre_amigable' => $validated['nombre_amigable'],
            'indicador_id'    => $validated['parent_id'],
            'unidad_medida'   => $validated['unidad_medida'],
            'es_destacada'    => $request->has('es_destacada'),
            'es_kpi'          => $request->has('es_kpi'),
            'orden'           => $validated['orden'] ?? 0,
        ]);
        return response()->json($variable);
    }

    /**
     * Update the specified Variable resource in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Variable  $variable // Asume que el modelo se llama 'Variable'
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVariable(Request $request, Variable $variable)
    {
        $data = $request->validate([
            'nombre_tecnico'  => 'required|string|unique:variables,nombre_tecnico,' . $variable->id,
            'nombre_amigable' => 'required|string',
            'indicador_id'    => 'required|exists:indicadors,id',
            'unidad_medida'   => 'nullable|string',
            'orden'           => 'nullable|integer',
        ]);

        // 2. Manejamos los checkboxes explícitamente después de la validación
        $data['es_destacada'] = $request->has('es_destacada');
        $data['es_kpi']       = $request->has('es_kpi');

        // 3. Hacemos un solo llamado a update con todos los datos ya preparados
        $variable->update($data);

        return response()->json($variable);
    }

    /**
     * Remove the specified Variable resource from storage via API/AJAX.
     *
     * @param  \App\Models\Variable  $variable // Asume que el modelo se llama 'Variable'
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyVariable(Variable $variable)
    {
        $variable->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Exporta el catálogo de Dimensiones a un archivo Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportDimensiones()
    {
        return Excel::download(new DimensionesExport, 'catalogo-dimensiones.xlsx');
    }

    /**
     * Exporta el catálogo de Temáticas a un archivo Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportTematicas()
    {
        return Excel::download(new TematicasExport, 'catalogo-tematicas.xlsx');
    }

    /**
     * Exporta el catálogo de Indicadores a un archivo Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportIndicadores()
    {
        return Excel::download(new IndicadoresExport, 'catalogo-indicadores.xlsx');
    }

    /**
     * Exporta el catálogo de Variables a un archivo Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportVariables()
    {
        return Excel::download(new VariablesExport, 'catalogo-variables.xlsx');
    }

    /**
     * Exporta el catálogo público de datos a un archivo Excel basado en el tipo especificado.
     *
     * @param  string  $tipo  El tipo de catálogo a exportar ('dimensiones', 'tematicas', 'municipios', etc.).
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportarCatalogoPublico($tipo)
    {
        switch ($tipo) {
            case 'dimensiones':
                return $this->exportDimensiones();
            case 'tematicas':
                return $this->exportTematicas();
            case 'indicadores':
                return $this->exportIndicadores();
            case 'variables':
                return $this->exportVariables();
            case 'municipios':
                return Excel::download(new MunicipiosExport, 'catalogo-municipios.xlsx');
            case 'microrregiones':
                return Excel::download(new MicrorregionesExport, 'catalogo-microrregiones.xlsx');
            case 'macrorregiones':
                return Excel::download(new MacrorregionesExport, 'catalogo-macrorregiones.xlsx');
            default:
                // Si el tipo no es válido, redirige de vuelta con un error.
                return redirect()->route('datos-abiertos.index')
                    ->with('error', 'El tipo de catálogo solicitado no es válido.');
        }
    }
}
