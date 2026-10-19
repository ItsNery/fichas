<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatoHistorico;
use App\Models\Indicador;
use App\Models\Variable;
use App\Services\DashboardService;
use App\Services\DataHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard.ejecutivo')->only(['dashboard']);
        $this->middleware('permission:salud-datos.ver')->only(['dataHealth']);
        $this->middleware('permission:catalogos.editar')->only(['actualizarPeriodicidad']);
    }

    public function dashboard(DashboardService $service)
    {
        $resumen = Cache::remember('dashboard:resumen', now()->addMinutes(5), fn () => $service->resumen());

        return view('admin.dashboard', $resumen);
    }

    public function dataHealth(DataHealthService $service)
    {
        $resumen = Cache::remember('salud-datos:resumen', now()->addMinutes(10), fn () => $service->resumen());

        return view('datos.data_health', $resumen);
    }

    /**
     * Declara la periodicidad de un indicador para evaluar su cobertura temporal.
     */
    public function actualizarPeriodicidad(Request $request, Indicador $indicador)
    {
        $validated = $request->validate([
            'periodicidad' => 'required|string|in:' . implode(',', array_keys(DataHealthService::PERIODICIDADES)),
        ]);

        $indicador->update(['periodicidad' => $validated['periodicidad']]);

        Cache::forget('salud-datos:resumen');

        return redirect()
            ->route('admin.salud-datos')
            ->with('success', "«{$indicador->nombre_amigable}» ahora se declara como {$validated['periodicidad']}.");
    }
}
