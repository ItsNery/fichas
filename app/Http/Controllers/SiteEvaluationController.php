<?php
namespace App\Http\Controllers;

use App\Models\SiteEvaluation;
use Illuminate\Http\Request;

class SiteEvaluationController extends Controller
{
    /**
     * Stores a new site evaluation (score) received via an API/AJAX request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'score' => 'required|integer|in:1,2,3',
        ]);

        // Aquí guardas la evaluación en tu base de datos.
        // Puedes añadir más campos como la URL, el user agent, etc.
        SiteEvaluation::create([
            'score'      => $validated['score'],
            'url'        => url()->previous(), // Guarda la URL desde donde se votó.
            'user_agent' => $request->header('User-Agent'),
        ]);

        return response()->json(['message' => 'Evaluation received successfully.']);
    }
}
