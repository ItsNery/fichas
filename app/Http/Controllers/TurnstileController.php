<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequireTurnstileClearance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
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
            'token' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $respuesta = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => (string) config('services.turnstile.secret'),
            'response' => $datos['token'],
            'remoteip' => $request->ip(),
        ])->json();

        if (empty($respuesta['success'])) {
            throw ValidationException::withMessages([
                'token' => 'No se pudo verificar el captcha, intenta de nuevo.',
            ]);
        }

        return redirect()
            ->to($this->safeRedirectValue($datos['redirect'] ?? null))
            ->withCookie(RequireTurnstileClearance::cookie());
    }

    private function safeRedirect(Request $request): string
    {
        return $this->safeRedirectValue($request->query('redirect'));
    }

    private function safeRedirectValue(?string $url): string
    {
        // Sólo rutas internas: evita redirigir a un dominio externo.
        if (is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return '/';
    }
}
