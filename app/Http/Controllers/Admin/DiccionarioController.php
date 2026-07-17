<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Indicador;
use Illuminate\Http\Request;

class DiccionarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:diccionario.ver');
        $this->middleware('permission:diccionario.editar')->only(['edit', 'update']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $indicadores = Indicador::with('tematica.dimension', 'variables')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nombre_amigable', 'like', "%{$search}%")
                        ->orWhere('responsable', 'like', "%{$search}%")
                        ->orWhere('periodicidad', 'like', "%{$search}%")
                        ->orWhere('cobertura_geografica', 'like', "%{$search}%")
                        ->orWhereHas('tematica', function ($query) use ($search) {
                            $query->where('nombre', 'like', "%{$search}%")
                                ->orWhereHas('dimension', fn($query) => $query->where('nombre', 'like', "%{$search}%"));
                        });
                });
            })
            ->orderBy('nombre_amigable')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($ind) => [
                'indicador' => $ind,
                'completitud' => $this->calcularCompletitud($ind),
            ]);

        return view('admin.diccionario.index', compact('indicadores', 'search', 'perPage'));
    }

    public function edit(Indicador $indicador)
    {
        return view('admin.diccionario.edit', compact('indicador'));
    }

    public function update(Request $request, Indicador $indicador)
    {
        $validated = $request->validate([
            'responsable' => 'nullable|string|max:255',
            'periodicidad' => 'nullable|string|max:100',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after_or_equal:fecha_vigencia_inicio',
            'metodologia' => 'nullable|string',
            'metodologia_url' => 'nullable|url|max:500',
            'clasificacion' => 'nullable|in:publica,uso_interno,confidencial',
            'estado_publicacion' => 'nullable|in:borrador,en_revision,publicado,deprecado',
            'cobertura_geografica' => 'nullable|string|max:255',
            'unidad_responsable' => 'nullable|string|max:255',
            'notas_metodologicas' => 'nullable|string',
            'norma_tecnica' => 'nullable|string|max:255',
        ]);

        $indicador->update($validated);

        return redirect()->route('admin.diccionario.index')
            ->with('success', 'Metadatos del indicador actualizados correctamente.');
    }

    private function calcularCompletitud(Indicador $ind): int
    {
        $campos = ['responsable', 'periodicidad', 'metodologia',
                    'cobertura_geografica', 'unidad_responsable', 'norma_tecnica'];
        $llenos = collect($campos)->filter(fn($c) => !empty($ind->$c))->count();
        return intval(($llenos / count($campos)) * 100);
    }
}
