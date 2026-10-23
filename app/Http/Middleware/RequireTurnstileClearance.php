<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
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
            'redirect' => $request->getRequestUri(),
        ]);
    }

    public static function cookie(Request $request): Cookie
    {
        return cookie(
            self::COOKIE,
            (string) (time() + self::MINUTES * 60),
            self::MINUTES,
            '/',
            null,
            (bool) (config('session.secure') || $request->isSecure()),
            true,
            false,
            'lax',
        );
    }
}
