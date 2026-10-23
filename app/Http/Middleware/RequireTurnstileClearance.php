<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTurnstileClearance
{
    private const COOKIE = 'turnstile_clearance';
    private const MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $expira = $request->cookie(self::COOKIE);

        if (is_string($expira) && ctype_digit($expira) && (int) $expira > time()) {
            return $next($request);
        }

        return redirect()->route('turnstile.challenge', [
            'redirect' => $request->fullUrl(),
        ]);
    }

    public static function cookie()
    {
        return cookie(self::COOKIE, (string) (time() + self::MINUTES * 60), self::MINUTES);
    }
}
