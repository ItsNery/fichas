<?php
namespace App\Http\Controllers;

use App\Models\SiteEvaluation;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class SiteEvaluationController extends Controller
{
    /**
     * Stores a new site evaluation (score) received via an API/AJAX request.
     * Recopila datos del navegador, dispositivo, y comentario del usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $score = (int) $request->score;

        $request->validate([
            'score' => 'required|integer|in:1,2,3',
            // El comentario es obligatorio si el score es 1 o 2
            'comment' => $score <= 2 ? 'required|string|max:1000' : 'nullable|string|max:1000',
            'url_evaluated' => 'nullable|string|max:2000',
            'screen_resolution' => 'nullable|string|max:20',
            'language' => 'nullable|string|max:10',
            'time_zone' => 'nullable|string|max:50',
        ]);

        $agent = new Agent();

        // Detectar tipo de dispositivo
        $deviceType = 'desktop';
        if ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif ($agent->isRobot()) {
            $deviceType = 'bot';
        }

        SiteEvaluation::create([
            'score'             => $request->score,
            'comment'           => $request->comment,
            'url_evaluated'     => $request->url_evaluated,
            'user_agent'        => $request->header('User-Agent'),
            'ip_address'        => $request->ip(),
            'user_id'           => auth()->id(),
            'device_type'       => $deviceType,
            'browser'           => $agent->browser(),
            'browser_version'   => $agent->version($agent->browser()),
            'os'                => $agent->platform(),
            'os_version'        => $agent->version($agent->platform()),
            'screen_resolution' => $request->screen_resolution,
            'language'          => $request->language,
            'time_zone'         => $request->time_zone,
        ]);

        return response()->json(['success' => true, 'message' => 'Evaluación guardada correctamente.']);
    }
}
