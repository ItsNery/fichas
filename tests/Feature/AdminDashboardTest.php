<?php

namespace Tests\Feature;

use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\User;
use App\Models\Variable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    public function test_el_tablero_muestra_el_panorama_del_portal(): void
    {
        $this->crearCatalogo();

        $usuario = User::factory()->create();
        $usuario->assignRole('gobernanza');

        $response = $this->actingAs($usuario)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Panorama del portal');
        $response->assertSee('Datos históricos');
        $response->assertSee('Indicadores');
        $response->assertSee('chartSerieAnual', false);
        $response->assertSee('chartCobertura', false);
    }

    public function test_el_tablero_requiere_permiso_ejecutivo(): void
    {
        $analista = User::factory()->create();
        $analista->assignRole('analista');

        $this->actingAs($analista)->get(route('admin.dashboard'))->assertForbidden();
    }

    private function crearCatalogo(): void
    {
        $dimension = Dimension::create(['nombre' => 'Dimension', 'nombre_tecnico' => 'dimension']);
        $tematica = Tematica::create([
            'nombre' => 'Tematica',
            'nombre_tecnico' => 'tematica',
            'dimension_id' => $dimension->id,
        ]);
        $indicador = Indicador::create([
            'nombre_amigable' => 'Poblacion',
            'tematica_id' => $tematica->id,
            'tipo_dato' => 'absoluto',
        ]);
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Poblacion total',
            'nombre_tecnico' => 'poblacion_total',
        ]);
        $macro = new \App\Models\Macrorregion();
        $macro->nombre = 'Macro';
        $macro->slug = 'macro';
        $macro->save();

        $micro = new \App\Models\Microrregion();
        $micro->nombre = 'Micro';
        $micro->slug = 'micro';
        $micro->macrorregion_id = $macro->id;
        $micro->save();

        $municipio = Municipio::create([
            'nombre' => 'Municipio A',
            'slug' => 'municipio-a',
            'microrregion_id' => $micro->id,
        ]);

        DatoHistorico::create([
            'municipio_id' => $municipio->id,
            'variable_id' => $variable->id,
            'anio' => 2024,
            'valor' => 100,
        ]);
    }
}
