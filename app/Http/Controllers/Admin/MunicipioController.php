<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instrumento;
use App\Models\Municipio;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Obtenemos los municipios, paginados para no cargar todos de golpe
        $municipios = Municipio::orderBy('nombre')->get();

        return view('municipios.index', compact('municipios'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Municipio  $municipio
     * @return void
     */
    public function show(Municipio $municipio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Municipio  $municipio
     * @return \Illuminate\View\View
     */
    public function edit(Municipio $municipio)
    {
        return view('municipios.edit', compact('municipio'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Municipio  $municipio
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Municipio $municipio)
    {
        $request->validate([
            'nombre'                => 'required|string|max:255',
            'cvegeo'                => 'required|string|max:10',
            'cabecera'              => 'nullable|string|max:255',
            'presidente_municipal'  => 'nullable|string|max:255',
            'periodo_gobierno'      => 'nullable|string|max:100',
            'banner_image_url'      => 'nullable|url|max:255',
            'logo_url'              => 'nullable|url|max:255',
            'clima'                 => 'nullable|string|max:255',
            'superficie'            => 'nullable|numeric',
        ]);

        $municipio->update($request->all());

        return redirect()->route('admin.municipios.index')
            ->with('success', 'Información del municipio ' . $municipio->nombre . ' actualizada correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Municipio  $municipio
     * @return void
     */
    public function destroy(Municipio $municipio)
    {
        //
    }

    /**
     * Retrieves the catalog of all available 'Instrumentos' and the list of
     * 'Instrumentos' already assigned to the given Municipio, returning the data as JSON.
     *
     * @param  \App\Models\Municipio  $municipio // Asume que el modelo se llama 'Municipio'
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInstrumentosJson(Municipio $municipio)
    {
        // 1. Obtenemos todos los instrumentos disponibles en el catálogo
        $catalogo = Instrumento::orderBy('nombre')->get(['id', 'nombre']);

        // 2. Obtenemos los instrumentos que este municipio ya tiene asignados, incluyendo el año
        $asignados = $municipio->instrumentos()->get()->mapWithKeys(function ($item) {
            // Creamos un mapa de [id_instrumento => anio] para fácil acceso en JavaScript
            return [$item->id => $item->pivot->anio];
        });

        // 3. Devolvemos todo como una respuesta JSON
        return response()->json([
            'catalogo'  => $catalogo,
            'asignados' => $asignados,
        ]);
    }

    /**
     * Synchronizes the 'Instrumentos' assigned to the specified Municipio using the given year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Municipio  $municipio // Asume que el modelo se llama 'Municipio'
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncInstrumentos(Request $request, Municipio $municipio)
    {
        $request->validate([
            'instrumentos'      => 'nullable|array',
            'anio_instrumentos' => 'required|integer|min:1900|max:2100',
        ]);

        $instrumentosIds = $request->input('instrumentos', []);
        $anio            = $request->input('anio_instrumentos');

        $datosParaSync = [];
        foreach ($instrumentosIds as $id) {
            $datosParaSync[$id] = ['anio' => $anio];
        }

        $municipio->instrumentos()->sync($datosParaSync);

        return back()->with('success', 'Instrumentos para ' . $municipio->nombre . ' actualizados con éxito.');
    }
}
