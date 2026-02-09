<?php
namespace App\Http\Controllers;

use App\Http\Controllers\FichaController;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    /**
     * Processes a request for indicator data, validates the parameters, calls the
     * internal data retrieval logic (from FichaController), and returns the results as JSON.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarDatos(Request $request)
    {
        // 1. Instanciamos FichaController para acceder a sus métodos
        $fichaController = new FichaController();

        // 2. Definimos y validamos los parámetros que el público puede enviar.
        // Son las mismas reglas que ya usas en tu dashboard.
        $validated = $request->validate([
            'indicador_id'        => 'required|integer|exists:indicadors,id',
            'nivel_de_agregacion' => 'required|string|in:municipio,microrregion,macrorregion',
            'municipio_ids'       => 'nullable|array',
            'municipio_ids.*'     => 'string',
            'region_id'           => 'nullable|integer',
            'anios'               => 'nullable|array',
            'anios.*'             => 'integer',
        ]);

        try {
            // 3. ¡La Magia! Llamamos al método getChartData que ya tienes
            // para que haga todo el trabajo pesado.
            $chartData = $fichaController->getChartData($validated);

            // 4. Devolvemos los datos en un formato JSON estándar.
            return response()->json([
                'success' => true,
                'data'    => $chartData,
            ]);

        } catch (\Exception $e) {
            // Si algo sale mal, devolvemos un error estructurado.
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
