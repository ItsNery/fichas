<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequireTurnstileClearance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TurnstileController extends Controller
{
    public function challenge(Request $request): Response
    {
        $redirect = $this->safeRedirect($request);

        return response()->view('turnstile.challenge', [
            'siteKey' => (string) config('services.turnstile.site_key'),
            'redirect' => $redirect,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cf-turnstile-response' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $respuesta = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => (string) config('services.turnstile.secret'),
            'response' => $datos['cf-turnstile-response'],
            'remoteip' => $request->ip(),
        ])->json();

        if (empty($respuesta['success'])) {
            Log::notice('turnstile.verification_failed', [
                'error_codes' => $this->errorCodes($respuesta),
                'ip_hash' => $this->fingerprint($request->ip()),
                'user_agent_hash' => $this->fingerprint($request->userAgent()),
            ]);

            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'No se pudo verificar el captcha, intenta de nuevo.',
            ]);
        }

        Log::info('turnstile.verification_succeeded', [
            'ip_hash' => $this->fingerprint($request->ip()),
            'user_agent_hash' => $this->fingerprint($request->userAgent()),
        ]);

        return redirect()
            ->to($this->safeRedirectValue($datos['redirect'] ?? null))
            ->withCookie(RequireTurnstileClearance::cookie($request));
    }

    private function safeRedirect(Request $request): string
    {
        return $this->safeRedirectValue($request->query('redirect'));
    }

    private function safeRedirectValue(?string $url): string
    {
        // Sólo rutas internas: evita redirigir a un dominio externo.
        if (! is_string($url) || $url === '') {
            return '/';
        }

        $decodedUrl = rawurldecode($url);
        $parts = parse_url($decodedUrl);

        if (
            str_starts_with($decodedUrl, '/')
            && ! str_starts_with($decodedUrl, '//')
            && ! str_contains($decodedUrl, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $decodedUrl)
            && is_array($parts)
            && ! isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'], $parts['port'])
        ) {
            return $url;
        }

        return '/';
    }

    /** @param array<string, mixed> $response */
    private function errorCodes(array $response): array
    {
        return array_values(array_filter(
            (array) ($response['error-codes'] ?? ['verification_failed']),
            static fn (mixed $code): bool => is_string($code) && $code !== '',
        ));
    }

    private function fingerprint(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
