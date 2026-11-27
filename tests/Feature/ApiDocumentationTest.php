<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_documented_public_operations_match_registered_routes(): void
    {
        $spec = $this->getJson('/api/openapi.json')->assertOk()->json();
        $operations = [];
        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $operations[] = strtoupper($method).' '.ltrim($path, '/');
            }
        }

        $routes = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $routes[] = $method.' '.$route->uri();
            }
        }
        sort($routes);
        sort($operations);
        $this->assertSame($routes, $operations);
    }

    public function test_query_endpoints_report_invalid_input_as_documented(): void
    {
        foreach ([['post', '/api/v1/consulta'], ['post', '/api/v1/data'], ['get', '/api/v1/data']] as [$method, $url]) {
            $this->{$method.'Json'}($url)->assertUnprocessable()->assertJsonPath('success', false)
                ->assertJsonValidationErrors(['indicador_id', 'nivel_de_agregacion']);
        }
    }

    public function test_docs_and_open_data_link_to_the_specification(): void
    {
        $this->get('/datos-abiertos/api/docs')->assertOk()->assertSee('swagger-ui')->assertSee('/api/openapi.json');
        $this->get('/api/docs')->assertStatus(301)->assertRedirect('/datos-abiertos/api/docs');
        $this->get('/datos-abiertos')->assertOk()->assertSee('/datos-abiertos/api/docs');
    }
}
