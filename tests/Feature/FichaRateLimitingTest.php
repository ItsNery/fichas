<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FichaRateLimitingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('ficha-comparativa:127.0.0.1');
        RateLimiter::clear('ficha-pdf:127.0.0.1');
    }

    public function test_production_routes_use_the_cost_specific_limiters(): void
    {
        $routes = Route::getRoutes();

        $this->assertContains('throttle:ficha-comparativa', $routes->getByName('municipios.comparar')->gatherMiddleware());
        $this->assertContains('throttle:ficha-perfil-seccion', $routes->getByName('municipios.dimension')->gatherMiddleware());

        foreach ([
            'municipios.perfil.pdf',
            'municipios.panorama.pdf',
        ] as $routeName) {
            $this->assertContains('throttle:ficha-pdf', $routes->getByName($routeName)->gatherMiddleware());
        }
    }

    public function test_comparison_requests_are_rejected_after_ten_requests_per_minute(): void
    {
        Route::get('/__test/ficha-comparativa', fn () => response()->noContent())
            ->middleware('throttle:ficha-comparativa');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->get('/__test/ficha-comparativa')->assertNoContent();
        }

        $this->get('/__test/ficha-comparativa')->assertTooManyRequests();
    }

    public function test_pdf_requests_are_rejected_after_three_requests_per_minute(): void
    {
        Route::get('/__test/ficha-pdf', fn () => response()->noContent())
            ->middleware('throttle:ficha-pdf');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->get('/__test/ficha-pdf')->assertNoContent();
        }

        $this->get('/__test/ficha-pdf')->assertTooManyRequests();
    }
}
