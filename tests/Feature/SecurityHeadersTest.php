<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_it_adds_security_headers_to_https_responses(): void
    {
        $response = app(SecurityHeaders::class)->handle(
            Request::create('https://fichas.example.test', 'GET'),
            fn (): Response => new Response('ok'),
        );

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame("base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertSame('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
    }
}
