<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\FormulaException;
use App\Http\Controllers\Controller;
use App\Models\Formula;
use App\Models\Variable;
use App\Services\FormulaImporter;
use App\Services\FormulaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class FormulaController extends Controller
{
    public function __construct(private readonly FormulaService $formulas)
    {
    }

    public function index()
    {
        Gate::authorize('catalogos.ver');

        return view('admin.formulas.index', [
            'formulas' => Formula::orderBy('nombre')->get(),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('catalogos.crear');

        return view('admin.formulas.form', [
            'formula' => null,
            'slots' => [],
            'expresionSugerida' => $request->input('expresion', ''),
            'variables' => $this->variablesDisponibles(),
            'variablesJson' => $this->variablesParaJs(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('catalogos.crear');

        $datos = $this->validar($request);

        $formula = Formula::create($datos + ['usuario_creacion_id' => auth()->id()]);

        return redirect()
            ->route('admin.formulas.show', $formula)
            ->with('success', 'Fórmula creada correctamente. Ya puedes asignarle variables y probarla.');
    }

    public function edit(Formula $formula)
    {
        Gate::authorize('catalogos.editar');

        return view('admin.formulas.form', [
            'formula' => $formula,
            'slots' => $formula->slots(),
            'expresionSugerida' => $formula->expresion,
            'variables' => $this->variablesDisponibles(),
            'variablesJson' => $this->variablesParaJs(),
        ]);
    }

    public function update(Request $request, Formula $formula)
    {
        Gate::authorize('catalogos.editar');

        $datos = $this->validar($request, $formula);

        $formula->update($datos);

        return redirect()
            ->route('admin.formulas.show', $formula)
            ->with('success', 'Fórmula actualizada correctamente.');
    }

    public function destroy(Formula $formula)
    {
        Gate::authorize('catalogos.eliminar');

        $formula->delete();

        return redirect()
            ->route('admin.formulas.index')
            ->with('success', 'Fórmula eliminada.');
    }

    /**
     * Pantalla "usar la fórmula": asignar variables fuente y ver resultados.
     */
    public function show(Request $request, Formula $formula)
    {
        Gate::authorize('catalogos.ver');

        $asignaciones = $this->asignacionesDesdeRequest($request, $formula);
        $filas = [];
        $error = null;
        $tipoResultado = null;

        if ($asignaciones && ! array_filter($asignaciones)) {
            $asignaciones = [];
        }

        if ($asignaciones) {
            try {
                $filas = $this->formulas->previsualizar(
                    $formula,
                    $asignaciones,
                    $request->integer('anio') ?: null
                );
                $tipoResultado = $this->formulas->tipoResultado($formula->expresion, $asignaciones);
            } catch (FormulaException $e) {
                $error = $e->getMessage();
                $tipoResultado = null;
            }
        }

        return view('admin.formulas.show', [
            'formula' => $formula,
            'slots' => $formula->slots(),
            'variables' => $this->variablesDisponibles(),
            'variablesConDatos' => $this->variablesConDatos(),
            'asignaciones' => $asignaciones,
            'filas' => $filas,
            'error' => $error,
            'tipoResultado' => $tipoResultado,
            'municipios' => $this->municipiosPorFila($filas),
        ]);
    }

    /**
     * Previsualización asíncrona (JSON) para el constructor de la vista.
     */
    public function preview(Request $request)
    {
        Gate::authorize('catalogos.ver');

        $expresion = (string) $request->input('expresion', '');
        $asignaciones = [];

        foreach ((array) $request->input('asignaciones', []) as $clave => $variableId) {
            if ($variableId) {
                $asignaciones[strtoupper((string) $clave)] = (int) $variableId;
            }
        }

        try {
            $slots = $this->formulas->slotsDeExpresion($expresion);
            $formula = new Formula([
                'expresion' => $expresion,
                'ambito' => $request->input('ambito', Formula::AMBITO_ESTATAL),
            ]);

            $filas = empty($asignaciones)
                ? []
                : $this->formulas->previsualizar($formula, $asignaciones, $request->integer('anio') ?: null);

            return response()->json([
                'success' => true,
                'slots' => $slots,
                'rows' => $filas,
                'total' => count($filas),
                'tipoResultado' => empty($asignaciones)
                    ? null
                    : $this->formulas->tipoResultado($expresion, $asignaciones),
            ]);
        } catch (FormulaException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Genera los datos de la fórmula sobre una variable destino.
     */
    public function generar(Request $request, Formula $formula)
    {
        Gate::authorize('catalogos.crear');

        $asignaciones = $this->asignacionesDesdeRequest($request, $formula);

        $request->validate([
            'variable_resultado_id' => 'required|exists:variables,id',
        ]);

        $variableResultado = Variable::findOrFail($request->integer('variable_resultado_id'));

        try {
            $total = $this->formulas->generar($formula, $asignaciones, $variableResultado);
        } catch (FormulaException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.formulas.show', $formula)
            ->with('success', "Se generaron {$total} registros en la variable \"{$variableResultado->nombre_amigable}\".");
    }

    /**
     * Importa al catálogo las fórmulas que ya existen en variables construidas.
     * No modifica las variables de origen.
     */
    public function importar(Request $request, FormulaImporter $importer)
    {
        Gate::authorize('catalogos.crear');

        $ambito = $request->input('ambito', Formula::AMBITO_MUNICIPAL);

        if (! in_array($ambito, [Formula::AMBITO_MUNICIPAL, Formula::AMBITO_ESTATAL], true)) {
            $ambito = Formula::AMBITO_MUNICIPAL;
        }

        $resultado = $importer->importar($ambito);

        $creadas = count($resultado['creadas']);
        $omitidas = count($resultado['omitidas']);

        $mensaje = "Importación terminada: {$creadas} fórmulas creadas"
            . ($omitidas > 0 ? ", {$omitidas} omitidas." : '.');

        return redirect()
            ->route('admin.formulas.index')
            ->with('success', $mensaje)
            ->with('omitidas', $resultado['omitidas']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Formula $formula = null): array
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'ambito' => 'required|in:' . implode(',', [
                Formula::AMBITO_MUNICIPAL,
                Formula::AMBITO_ESTATAL,
                Formula::AMBITO_REGIONAL,
            ]),
            'expresion' => 'required|string|max:1000',
            'slots' => 'nullable|array',
            'slots.*.clave' => 'required|string|max:10',
            'slots.*.nombre' => 'nullable|string|max:255',
            'activa' => 'nullable|boolean',
        ]);

        try {
            $analisis = $this->formulas->analizar($datos['expresion']);
        } catch (FormulaException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'expresion' => $e->getMessage(),
            ]);
        }

        $slots = [];

        foreach ((array) ($datos['slots'] ?? []) as $slot) {
            $clave = strtoupper(trim((string) ($slot['clave'] ?? '')));

            if ($clave === '' || ! in_array($clave, $analisis['slots'], true)) {
                continue;
            }

            $slots[] = [
                'clave' => $clave,
                'nombre' => trim((string) ($slot['nombre'] ?? '')),
            ];
        }

        foreach ($analisis['slots'] as $clave) {
            if (! collect($slots)->contains('clave', $clave)) {
                $slots[] = ['clave' => $clave, 'nombre' => ''];
            }
        }

        $datos['slots'] = $slots;
        $datos['activa'] = $request->has('activa');
        $datos['nombre_tecnico'] = $formula && $formula->nombre_tecnico
            ? $formula->nombre_tecnico
            : $this->nombreTecnico($datos['nombre'], $formula);

        return $datos;
    }

    private function nombreTecnico(string $nombre, ?Formula $formula = null): string
    {
        $base = Str::slug(Str::ascii($nombre), '_') ?: 'formula';
        $nombreTecnico = $base;
        $sufijo = 2;

        while (Formula::where('nombre_tecnico', $nombreTecnico)
            ->when($formula, fn ($query) => $query->where('id', '!=', $formula->id))
            ->exists()) {
            $nombreTecnico = $base . '_' . $sufijo++;
        }

        return $nombreTecnico;
    }

    /**
     * @return array<string, int|null>
     */
    private function asignacionesDesdeRequest(Request $request, Formula $formula): array
    {
        $asignaciones = [];
        $envioElUsuario = $request->has('asignaciones');

        foreach ($formula->slots() as $slot) {
            $clave = $slot['clave'];

            if ($envioElUsuario) {
                $valor = $request->input('asignaciones.' . $clave);
                $asignaciones[$clave] = $valor ? (int) $valor : null;
                continue;
            }

            // Sin envío explícito se usan las variables sugeridas por la importación.
            $asignaciones[$clave] = $slot['variable_id'] ?? null;
        }

        return $asignaciones;
    }

    private function municipiosPorFila(array $filas): array
    {
        $ids = collect($filas)->pluck('municipio_id')->filter()->unique()->values()->all();

        if (empty($ids)) {
            return [];
        }

        return \App\Models\Municipio::whereIn('id', $ids)->pluck('nombre', 'id')->all();
    }

    /**
     * IDs de las variables que ya tienen datos históricos cargados.
     *
     * @return array<int, int>
     */
    private function variablesConDatos(): array
    {
        return \Illuminate\Support\Facades\DB::table('dato_historicos')
            ->whereNotNull('valor')
            ->distinct()
            ->pluck('variable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function variablesDisponibles()
    {
        return Variable::with('indicador')->orderBy('nombre_amigable')->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function variablesParaJs(): array
    {
        return $this->variablesDisponibles()
            ->map(fn (Variable $variable) => [
                'id' => $variable->id,
                'nombre' => $variable->nombre_amigable,
                'indicador' => $variable->indicador?->nombre_amigable,
                'tipo' => $variable->indicador?->tipo_dato,
            ])
            ->values()
            ->all();
    }
}
