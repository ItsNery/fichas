<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    /**
     * Searches for Municipalities based on a query string and returns the results
     * in a JSON format compatible with select/autocomplete libraries (e.g., Tom Select).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $searchTerm = $request->query('q', '');
        if (strlen($searchTerm) < 2) {
            return response()->json([]);
        }

        $municipios = Municipio::where('nombre', 'LIKE', "%{$searchTerm}%")
            ->select('slug as id', 'nombre as text')
            ->limit(10)
            ->get();

        return response()->json($municipios);
    }
}
