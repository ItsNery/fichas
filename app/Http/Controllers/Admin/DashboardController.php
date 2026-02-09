<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatoHistorico;
use App\Models\Indicador;
use App\Models\Dimension;
use App\Models\Tematica;
use App\Models\Variable;
use App\Models\SiteEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function dashboard(){
// 1. Estadísticas Generales (KPIs)
        // Usamos cache para no saturar la BD si hay muchos datos
        $stats = [
            'total_datos'       => DatoHistorico::count(),
            'total_indicadores' => Indicador::count(),
            'total_variables' => Variable::count(),
            'total_dimensiones' => Dimension::count(),
            'total_tematicas' => Tematica::count(),
            'total_usuarios'    => User::count(),
        ];

        // 2. Actividad Reciente (Últimos datos subidos o modificados)
        $datosRecientes = DatoHistorico::with(['variable', 'municipio'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        // 3. Evaluación del Sitio (Feedback)
        $votosFeliz     = SiteEvaluation::where('score', '3')->count();
        $votosNeutral   = SiteEvaluation::where('score', '2')->count();
        $votosTriste    = SiteEvaluation::where('score', '1')->count();
        return view('dashboard', compact('stats', 'datosRecientes', 'votosFeliz','votosNeutral','votosTriste'));
    }
    /**
     * Executes a series of data health checks (empty indicators, orphan variables,
     * outdated indicators, and atypical data) and displays the results.
     *
     * @return \Illuminate\View\View
     */
    public function dataHealth()
    {
        //    indicadores que NO TIENEN ('doesntHave') ninguna relación con 'variables'.
        $indicadoresVacios = Indicador::doesntHave('variables')->get();

        // Chequeo 2: Variables Huérfanas
        $variablesHuerfanas = Variable::whereNull('indicador_id')->get();

                                                 // Chequeo 3: Indicadores Desactualizados
        $indicadoresDesactualizados = collect(); // Inicializamos como colección vacía

        // Primero, encontramos cuál es el año más reciente con datos en TODO el sistema.
        $latestYear = DatoHistorico::max('anio');

        if ($latestYear) {
            // Luego, obtenemos los IDs de los indicadores que SÍ tienen datos para ese año.
            $updatedIndicatorIds = Indicador::whereHas('variables.datosHistoricos', function ($query) use ($latestYear) {
                $query->where('anio', $latestYear);
            })->pluck('id');

            // Finalmente, buscamos los indicadores cuyo ID NO ESTÁ en la lista anterior.
            // También nos aseguramos de que no sean indicadores vacíos para no duplicar alertas.
            $indicadoresDesactualizados = Indicador::whereNotIn('id', $updatedIndicatorIds)
                ->has('variables') // Solo checa indicadores que sí tienen variables
                ->get();
        }

        // --- Chequeo 4: Datos Atípicos ---
        // Definimos un umbral. Alertaremos si un dato crece más de 1000% en un año.
        // Puedes ajustar este valor según tus necesidades.
        $threshold = 1000;
        // 2. Escribimos la consulta para encontrar los datos atípicos
        $query = "
            SELECT
                current.id as dato_id,
                current.anio,
                current.valor as valor_actual,
                previous.valor as valor_anterior,
                v.nombre_amigable as variable_nombre,
                m.nombre as municipio_nombre
            FROM
                dato_historicos AS current
            JOIN
                dato_historicos AS previous ON current.variable_id = previous.variable_id
                                           AND current.municipio_id = previous.municipio_id
                                           AND current.anio = previous.anio + 1
            JOIN
                variables AS v ON current.variable_id = v.id
            JOIN
                municipios AS m ON current.municipio_id = m.id
            WHERE
                previous.valor IS NOT NULL
                AND previous.valor > 0 -- Evitamos la división por cero
                AND (((current.valor - previous.valor) / previous.valor) * 100) > ?
        ";

        // 3. Ejecutamos la consulta
        $datosAtipicos = DB::select($query, [$threshold]);

        return view('datos.data_health', [
            'indicadoresVacios'          => $indicadoresVacios,
            'variablesHuerfanas'         => $variablesHuerfanas,
            'indicadoresDesactualizados' => $indicadoresDesactualizados,
            'latestYear'                 => $latestYear,
            'datosAtipicos'              => $datosAtipicos,
            'threshold'                  => $threshold,
        ]);
    }
}
