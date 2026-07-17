<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoteDatos;
use App\Services\LoteDatosService;
use Illuminate\Http\Request;

class LoteDatosController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:datos.ver')->only(['index', 'show']);
        $this->middleware('permission:datos.aprobar')->only(['aprobar', 'rechazar']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
        $estado = $request->query('estado');

        $lotes = LoteDatos::with(['usuarioCarga', 'usuarioRevision'])
            ->when($estado, fn($query, $estado) => $query->where('estado', $estado))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.lotes_datos.index', compact('lotes', 'estado', 'perPage'));
    }

    public function show(LoteDatos $lote)
    {
        $lote->load(['usuarioCarga', 'usuarioRevision']);
        $filas = $lote->tipo === 'datos_complejos'
            ? $lote->filasComplejas()->with(['municipio', 'indicador'])->orderBy('fila_origen')->paginate(50)
            : $lote->filas()->with(['municipio', 'variable.indicador', 'motivoSinDato'])->orderBy('fila_origen')->paginate(50);

        return view('admin.lotes_datos.show', compact('lote', 'filas'));
    }

    public function aprobar(LoteDatos $lote, LoteDatosService $service)
    {
        $service->aprobar($lote, auth()->user());

        return redirect()->route('admin.lotes-datos.show', $lote)
            ->with('success', 'El lote fue aprobado y sus datos ya están publicados.');
    }

    public function rechazar(Request $request, LoteDatos $lote, LoteDatosService $service)
    {
        $validated = $request->validate([
            'observaciones' => 'required|string|min:10|max:2000',
        ]);

        $service->rechazar($lote, auth()->user(), $validated['observaciones']);

        return redirect()->route('admin.lotes-datos.show', $lote)
            ->with('success', 'El lote fue rechazado y no se publicaron cambios.');
    }
}
