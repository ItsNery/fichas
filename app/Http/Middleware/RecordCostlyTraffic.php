<?php

namespace App\Http\Middleware;

use App\Jobs\StoreTrafficRequestEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        try {
            StoreTrafficRequestEvent::dispatch([
                'occurred_at' => now(),
                'route_name' => $routeName,
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'client_ip' => $request->ip(),
                'visitor_hash' => $this->fingerprint($request->cookie((string) config('traffic.visitor_cookie'))),
                'session_hash' => $this->fingerprint($request->hasSession() ? $request->session()->getId() : null),
                'user_agent_hash' => $this->fingerprint($request->userAgent()),
                'referer_hash' => $this->fingerprint($request->headers->get('referer')),
                'user_id' => optional($request->user())->getAuthIdentifier(),
            ]);
        } catch (\Throwable $exception) {
            // Este middleware corre tras generar la respuesta: la telemetría
            // nunca puede alterar ni añadir contenido a una página ya enviada.
            Log::warning('traffic.event_recording_failed', [
                'route' => $routeName,
                'status' => $response->getStatusCode(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function fingerprint(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
