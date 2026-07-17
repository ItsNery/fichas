<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instrumento;
use Illuminate\Http\Request;

class InstrumentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:instrumentos.ver')->only(['index', 'show']);
        $this->middleware('permission:instrumentos.crear')->only(['create', 'store']);
        $this->middleware('permission:instrumentos.editar')->only(['edit', 'update']);
        $this->middleware('permission:instrumentos.eliminar')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $instrumentos = Instrumento::latest()->paginate(10);
        return view('instrumentos.index', compact('instrumentos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.instrumentos.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255|unique:instrumentos_planeacion']);
        Instrumento::create($request->all());
        return redirect()->route('admin.instrumentos.index')->with('success', 'Instrumento creado con éxito.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Instrumento  $instrumento
     *      * @return void
     */
    public function show(Instrumento $instrumento)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Instrumento  $instrumento
     * @return \Illuminate\View\View
     */
    public function edit(Instrumento $instrumento)
    {
        return view('admin.instrumentos.edit', compact('instrumento'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Instrumento  $instrumento
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Instrumento $instrumento)
    {
        $request->validate(['nombre' => 'required|string|max:255|unique:instrumentos_planeacion,nombre,' . $instrumento->id]);
        $instrumento->update($request->all());
        return redirect()->route('admin.instrumentos.index')->with('success', 'Instrumento actualizado con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Instrumento  $instrumento
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Instrumento $instrumento)
    {
        $instrumento->delete();
        return back()->with('success', 'Instrumento eliminado con éxito.');
    }
}
