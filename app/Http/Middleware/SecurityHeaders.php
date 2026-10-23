<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->setIfMissing($response, 'X-Content-Type-Options', 'nosniff');
        $this->setIfMissing($response, 'X-Frame-Options', 'SAMEORIGIN');
        $this->setIfMissing($response, 'Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->setIfMissing($response, 'Permissions-Policy', 'camera=(), geolocation=(), microphone=(), payment=(), usb=()');

        $policy = config(
            'security_headers.content_security_policy',
            "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'",
        );
        if (is_string($policy) && $policy !== '') {
            $this->setIfMissing($response, 'Content-Security-Policy', $policy);
        }

        if ($request->isSecure()) {
            $this->setIfMissing(
                $response,
                'Strict-Transport-Security',
                'max-age='.config('security_headers.hsts_max_age', 31536000),
            );
        }

        return $response;
    }

    private function setIfMissing(Response $response, string $header, string $value): void
    {
        if (!$response->headers->has($header)) {
            $response->headers->set($header, $value);
        }
    }
}
