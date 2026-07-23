<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatoHistorico;
use App\Models\Indicador;
use App\Models\Dimension;
use App\Models\Tematica;
use App\Models\Variable;
use App\Models\Municipio;
use App\Models\LoteDatos;
use App\Models\SiteEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard.ejecutivo')->only(['dashboard']);
        $this->middleware('permission:salud-datos.ver')->only(['dataHealth']);
    }

    public function dashboard()
    {
        $stats = [
            'total_datos'       => DatoHistorico::count(),
            'total_indicadores' => Indicador::count(),
            'total_variables'   => Variable::count(),
            'total_dimensiones' => Dimension::count(),
            'total_tematicas'   => Tematica::count(),
            'total_usuarios'    => User::count(),
            'total_municipios'  => Municipio::count(),
            'lotes_pendientes'  => LoteDatos::where('estado', LoteDatos::EN_REVISION)->count(),
        ];

        $datosRecientes = DatoHistorico::with(['variable', 'municipio'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        $votosFeliz   = SiteEvaluation::where('score', '3')->count();
        $votosNeutral = SiteEvaluation::where('score', '2')->count();
        $votosTriste  = SiteEvaluation::where('score', '1')->count();

        $latestYear = DatoHistorico::max('anio');

        $datosPorAnio = DatoHistorico::select('anio', DB::raw('COUNT(*) as total'))
            ->whereNotNull('anio')
            ->groupBy('anio')
            ->orderBy('anio')
            ->pluck('total', 'anio');

        $dimensionConteo = Dimension::select('dimensions.nombre', DB::raw('COUNT(indicadors.id) as total'))
            ->join('tematicas', 'dimensions.id', '=', 'tematicas.dimension_id')
            ->join('indicadors', 'tematicas.id', '=', 'indicadors.tematica_id')
            ->groupBy('dimensions.id', 'dimensions.nombre')
            ->get();

        $metadataFields = ['responsable', 'periodicidad', 'metodologia', 'cobertura_geografica', 'unidad_responsable', 'norma_tecnica'];
        $totalIndicadores = Indicador::count();
        $metadataCompletos = Indicador::all()->filter(function ($ind) use ($metadataFields) {
            foreach ($metadataFields as $field) {
                if (empty($ind->$field)) return false;
            }
            return true;
        })->count();

        $estadosPublicacion = Indicador::select('estado_publicacion', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_publicacion')
            ->pluck('total', 'estado_publicacion');

        $actividadReciente = Activity::with('causer')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($log) => [
                'usuario' => $log->causer?->name ?? 'Sistema',
                'evento' => $log->description,
                'fecha' => $log->created_at->diffForHumans(),
            ]);

        $dataCompletitud = 0;
        if ($latestYear && $stats['total_indicadores'] > 0) {
            $conDatos = Indicador::whereHas('variables.datosHistoricos', fn($q) => $q->where('anio', $latestYear))
                ->count();
            $dataCompletitud = $stats['total_indicadores'] > 0
                ? round(($conDatos / $stats['total_indicadores']) * 100)
                : 0;
        }

        return view('dashboard', compact(
            'stats', 'datosRecientes', 'votosFeliz', 'votosNeutral', 'votosTriste',
            'datosPorAnio', 'dimensionConteo', 'metadataCompletos', 'totalIndicadores',
            'estadosPublicacion', 'actividadReciente', 'dataCompletitud', 'latestYear'
        ));
    }

    public function dataHealth()
    {
        $indicadoresVacios = Indicador::doesntHave('variables')->get();
        $variablesHuerfanas = Variable::whereNull('indicador_id')->get();
        $polaridadResumen = Indicador::select('polaridad', DB::raw('COUNT(*) as total'))
            ->groupBy('polaridad')
            ->pluck('total', 'polaridad');
        $indicadoresSinPolaridad = Indicador::whereNull('polaridad')
            ->orWhere('polaridad', '')
            ->count();
        $indicadoresDesactualizados = collect();
        $latestYear = DatoHistorico::max('anio');

        if ($latestYear) {
            $updatedIndicatorIds = Indicador::whereHas('variables.datosHistoricos', function ($query) use ($latestYear) {
                $query->where('anio', $latestYear);
            })->pluck('id');

            $indicadoresDesactualizados = Indicador::whereNotIn('id', $updatedIndicatorIds)
                ->has('variables')
                ->get();
        }

        $threshold = 1000;
        $datosAtipicos = DB::select("
            SELECT
                current.id as dato_id, current.anio, current.valor as valor_actual,
                previous.valor as valor_anterior,
                v.nombre_amigable as variable_nombre,
                m.nombre as municipio_nombre
            FROM dato_historicos AS current
            JOIN dato_historicos AS previous
                ON current.variable_id = previous.variable_id
                AND current.municipio_id = previous.municipio_id
                AND current.anio = previous.anio + 1
            JOIN variables AS v ON current.variable_id = v.id
            JOIN municipios AS m ON current.municipio_id = m.id
            WHERE previous.valor IS NOT NULL AND previous.valor > 0
                AND (((current.valor - previous.valor) / previous.valor) * 100) > ?
        ", [$threshold]);

        return view('datos.data_health', compact(
            'indicadoresVacios', 'variablesHuerfanas',
            'polaridadResumen', 'indicadoresSinPolaridad',
            'indicadoresDesactualizados', 'latestYear',
            'datosAtipicos', 'threshold'
        ));
    }
}
