<?php

namespace App\Http\Controllers;

use App\Http\Controllers\FichaController;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\Variable;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Throwable;

class PublicApiController extends Controller
{
    /**
     * Processes a request for indicator data, validates the parameters, calls the
     * internal data retrieval logic (from FichaController), and returns the results as JSON.
     *
     * This method now captures and logs any database-related errors (and
     * other exceptions) so that consuming clients always receive a JSON
     * response and we keep a record in the logs for diagnosis.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarDatos(Request $request)
    {
        // Log::info('API DEBUG: Inicia consultarDatos');
        // Log::info('API DEBUG: Headers', $request->headers->all());
        // Log::info('API DEBUG: Body', $request->all());

        try {
            // 1. Instanciamos FichaController
            // Log::info('API DEBUG: Instanciando FichaController');
            $fichaController = new FichaController();

            // 2. Validación
            // Log::info('API DEBUG: Iniciando validación');
            $validated = $request->validate([
                'indicador_id'        => 'required|integer|exists:indicadors,id',
                'nivel_de_agregacion' => 'required|string|in:municipio,microrregion,macrorregion',
                'municipio_ids'       => 'nullable|array',
                'municipio_ids.*'     => 'string',
                'region_id'           => 'nullable|integer',
                'anios'               => 'nullable|array',
                'anios.*'             => 'integer',
            ]);
            Log::info('API DEBUG: Validación exitosa', $validated);

            // 3. Llamada a lógica central
            Log::info('API DEBUG: Llamando a getChartData');
            $chartData = $fichaController->getChartData($validated);
            Log::info('API DEBUG: getChartData retornó exitosamente');

            // 4. Respuesta
            return response()->json([
                'success' => true,
                'data'    => $chartData,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log::warning('API DEBUG: Error de validación', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Los datos proporcionados no son válidos.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            // Log::error('API DEBUG: Error de BD', [
            //     'message'  => $e->getMessage(),
            //     'sql'      => $e->getSql(),
            //     'bindings' => $e->getBindings(),
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la consulta a la base de datos.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error interno.',
            ], 500);
        } catch (Throwable $e) {
            // Log::error('API DEBUG: Excepción no manejada', [
            //     'message' => $e->getMessage(),
            //     'file'    => $e->getFile(),
            //     'line'    => $e->getLine(),
            //     'trace'   => $e->getTraceAsString(),
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error inesperado.',
                'trace'   => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    public function municipios()
    {
        $data = Municipio::select('id', 'nombre', 'slug', 'cvegeo')->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function microrregiones()
    {
        $data = Microrregion::select('id', 'nombre')->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function macrorregiones()
    {
        $data = Macrorregion::select('id', 'nombre')->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function metadata()
    {
        $dimensiones = Dimension::where('visible_en_ficha', true)
            ->with(['tematicas' => function ($query) {
                $query->where('visible_en_ficha', true)->orderBy('orden')->orderBy('nombre');
            }])->orderBy('orden')->orderBy('nombre')->get();

        $indicadores = Indicador::where('visible_en_ficha', true)
            ->whereHas('tematica', fn($query) => $query->where('visible_en_ficha', true)
                ->whereHas('dimension', fn($dimension) => $dimension->where('visible_en_ficha', true)))
            ->with(['variables' => fn($variables) => $variables->where('visible_en_ficha', true)])
            ->orderBy('nombre_amigable')->get();
        $variables   = Variable::where('visible_en_ficha', true)
            ->select('id', 'indicador_id', 'nombre_amigable', 'unidad_medida', 'orden')
            ->orderBy('nombre_amigable')->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'dimensiones'  => $dimensiones,
                'indicadores'  => $indicadores,
                'variables'    => $variables,
            ],
        ]);
    }

    public function indicadores(Request $request)
    {
        $query = Indicador::where('visible_en_ficha', true)
            ->whereHas('tematica', fn($tematica) => $tematica->where('visible_en_ficha', true)
                ->whereHas('dimension', fn($dimension) => $dimension->where('visible_en_ficha', true)))
            ->with(['variables' => fn($variables) => $variables->where('visible_en_ficha', true)])
            ->orderBy('nombre_amigable');

        if ($request->filled('tematica_id')) {
            $query->where('tematica_id', $request->input('tematica_id'));
        }

        if ($request->filled('dimension_id')) {
            $query->whereHas('tematica', function ($builder) use ($request) {
                $builder->where('dimension_id', $request->input('dimension_id'));
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get(),
        ]);
    }

    public function indicador($id)
    {
        $indicador = Indicador::where('visible_en_ficha', true)
            ->whereHas('tematica', fn($tematica) => $tematica->where('visible_en_ficha', true)
                ->whereHas('dimension', fn($dimension) => $dimension->where('visible_en_ficha', true)))
            ->with(['variables' => fn($variables) => $variables->where('visible_en_ficha', true)])
            ->find($id);

        if (! $indicador) {
            return response()->json([
                'success' => false,
                'message' => 'Indicador no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $indicador,
        ]);
    }

    public function data(Request $request)
    {
        return $this->consultarDatos($request);
    }

    public function debugController()
    {
        // Log::info('API DEBUG: Entrando a debugController');
        try {
            $count = Indicador::query()->count();

            // Probar instanciación de FichaController
            $fichaController = new FichaController();
            $testValid = class_exists(FichaController::class);

            // Log::info('API DEBUG: Prueba de logs exitosa desde debugController');

            return response()->json([
                'success' => true,
                'message' => 'Controller, DB and Logs should be working',
                'indicadores_count' => $count,
                'ficha_controller_exists' => $testValid,
                'php_version' => PHP_VERSION,
                'server_time' => now()->toDateTimeString(),
                'log_path' => storage_path('logs/laravel.log'),
                'log_writable' => is_writable(storage_path('logs/laravel.log')),
            ]);
        } catch (Throwable $e) {
            // Log::error('API DEBUG: Error en debugController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Controller reached but logic failed',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
