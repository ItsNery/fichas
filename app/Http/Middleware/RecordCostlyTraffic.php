<?php

namespace App\Http\Middleware;

use App\Jobs\StoreTrafficRequestEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordCostlyTraffic
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('costly_traffic_started_at', hrtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $routeName = $request->route()?->getName();

        if (! in_array($routeName, config('traffic.routes'), true)) {
            return;
        }

        $startedAt = $request->attributes->get('costly_traffic_started_at');
        $durationMs = $startedAt ? (int) ((hrtime(true) - $startedAt) / 1_000_000) : 0;

        StoreTrafficRequestEvent::dispatch([
            'occurred_at' => now(),
            'route_name' => $routeName,
            'method' => $request->method(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'client_ip' => $request->ip(),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'user_id' => optional($request->user())->getAuthIdentifier(),
        ]);
    }
}
