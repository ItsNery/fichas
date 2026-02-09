<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DatosHistoricosExport;
use App\Http\Controllers\Controller;
use App\Models\DatoHistorico;
use App\Models\Municipio;
use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Dimension;
use App\Exports\DatosComplejosExport;
use App\Models\Indicador;

class DatoHistoricoController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Iniciamos la consulta a la base de datos
        $query = DatoHistorico::with('municipio', 'variable');

        // Aplicamos los filtros si existen
        if ($request->filled('municipio_id')) {
            $query->where('municipio_id', $request->municipio_id);
        }
        if ($request->filled('variable_id')) {
            $query->where('variable_id', $request->variable_id);
        }

        // Ordenamos por los más recientes primero
        $query->orderBy('anio', 'desc')->orderBy('municipio_id');

        // --- ¡AQUÍ ESTÁ LA MAGIA! ---
        // En lugar de ->get(), usamos ->paginate() para obtener solo un lote de resultados.
        // withQueryString() asegura que los links de paginación conserven los filtros aplicados.
        $datos = $query->paginate(10)->onEachSide(1);

        // Pasamos los datos y los filtros a la vista
        return view('datos.index', [
            'datos'      => $datos,
            'municipios' => Municipio::orderBy('nombre')->get(),
            'variables'  => Variable::orderBy('nombre_amigable')->get(),
            'filters'    => $request->only(['municipio_id', 'variable_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        // --- IGNORE ---
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request)
    {
        // --- IGNORE ---
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DatoHistorico  $datoHistorico
     * @return void
     */
    public function show(DatoHistorico $datoHistorico)
    {
        // --- IGNORE ---
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DatoHistorico  $datoHistorico
     * @return \Illuminate\View\View
     */
    public function edit(DatoHistorico $dato)
    {
        return view('datos.edit', ['dato' => $dato]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DatoHistorico  $datoHistorico
     * @return \Illuminate\Http\Response
     */
    /**
     * Actualiza un dato histórico en la base de datos.
     */
    public function update(Request $request, DatoHistorico $dato)
    {
        // Validamos que el valor sea numérico
        $validated = $request->validate([
            'valor' => 'required|numeric',
        ]);

        // Actualizamos el registro
        $dato->update($validated);

        // Devolvemos una respuesta JSON para la petición AJAX
        return response()->json([
            'success'  => '¡Dato actualizado correctamente!',
            'newValue' => $dato->valor,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DatoHistorico  $datoHistorico
     *      * @return void
     */
    public function destroy(DatoHistorico $datoHistorico)
    {
        // --- IGNORE ---
    }

    /**
     * Exporta todos los datos históricos a un archivo CSV.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        $fileName = 'exportacion-datos-historicos-completa-' . now()->format('Y-m-d') . '.csv';

        // Llamamos al constructor VACÍO para que exporte todo
        return Excel::download(new DatosHistoricosExport(), $fileName);
    }
    public function exportOld()
    {
        $fileName = 'exportacion-datos-historicos-' . now()->format('Y-m-d') . '.csv';
        return Excel::download(new DatosHistoricosExport, $fileName);
    }
    public function exportPorDimensionAnio(Dimension $dimension, $anio)
    {
        $fileName = 'exportacion-' . Str::slug($dimension->nombre_tecnico) . '-anio-' . $anio . '.csv';

        // Le pasamos la dimensión y el año a la clase de exportación
        return Excel::download(new DatosHistoricosExport($dimension, $anio), $fileName);
    }

    public function exportComplejos(Indicador $indicador)
    {
        // Solo permitimos la descarga si el indicador es realmente complejo
        if (!$indicador->es_complejo) {
            abort(404, 'Exportación no disponible para este indicador.');
        }

        $fileName = 'exportacion-' . Str::slug($indicador->nombre_tecnico) . '.csv';
        return Excel::download(new DatosComplejosExport($indicador), $fileName);
    }
}
