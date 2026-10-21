<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['trustedproxy.proxies' => '10.1.4.9,10.1.4.15']);
    }

    public function test_it_uses_the_forwarded_client_ip_from_a_trusted_proxy(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.1.4.9',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.25, 10.1.4.9',
        ]);

        $response = (new TrustProxies())->handle($request, fn (Request $request) => response($request->ip()));

        $this->assertSame('198.51.100.25', $response->getContent());
    }

    public function test_it_ignores_forwarded_headers_from_an_untrusted_source(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.1.4.99',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.25',
        ]);

        $response = (new TrustProxies())->handle($request, fn (Request $request) => response($request->ip()));

        $this->assertSame('10.1.4.99', $response->getContent());
    }
}
