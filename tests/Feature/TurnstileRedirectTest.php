<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileRedirectTest extends TestCase
{
    public function test_verified_turnstile_redirect_preserves_the_public_base_path_once(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);

        $redirect = '/fichas/municipios/comparar/acatlan/con/ayotoxco-de-guerrero';

        $this->post('/challenge', [
            'cf-turnstile-response' => 'test-token',
            'redirect' => $redirect,
        ])->assertRedirect($redirect);
    }
}
