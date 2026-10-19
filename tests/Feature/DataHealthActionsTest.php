<?php

namespace Tests\Feature;

use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\User;
use App\Models\Variable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataHealthActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_las_acciones_del_panel_apuntan_a_destinos_reales(): void
    {
        $this->crearCatalogo();

        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $response = $this->actingAs($usuario)->get(route('admin.salud-datos'));

        $response->assertOk();
        $response->assertDontSee('href="#"', false);
    }

    public function test_asignar_una_variable_a_un_indicador(): void
    {
        $this->crearCatalogo();

        $indicador = Indicador::first();
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Variable suelta',
            'nombre_tecnico' => 'variable_suelta',
        ]);

        $indicadorDestino = Indicador::create([
            'nombre_amigable' => 'Demografía',
            'tematica_id' => Tematica::first()->id,
            'tipo_dato' => 'absoluto',
        ]);

        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)
            ->post(route('admin.catalogos.indicadores.asignar-variable', $indicadorDestino), [
                'variable_id' => $variable->id,
            ])
            ->assertRedirect(route('admin.salud-datos'))
            ->assertSessionHas('success');

        $this->assertEquals($indicadorDestino->id, $variable->refresh()->indicador_id);
    }

    public function test_asignar_exige_una_variable_valida(): void
    {
        $this->crearCatalogo();

        $indicador = Indicador::first();
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Variable suelta',
            'nombre_tecnico' => 'variable_suelta',
        ]);

        $indicadorOriginal = $variable->indicador_id;

        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)
            ->post(route('admin.catalogos.indicadores.asignar-variable', $indicador), [])
            ->assertSessionHasErrors('variable_id');

        $this->assertEquals($indicadorOriginal, $variable->refresh()->indicador_id);
    }

    public function test_solo_quien_puede_editar_catalogos_asigna_variables(): void
    {
        $this->crearCatalogo();

        $indicador = Indicador::first();
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Variable suelta',
            'nombre_tecnico' => 'variable_suelta',
        ]);

        $consultor = User::factory()->create();
        $consultor->assignRole('consultor');

        $this->actingAs($consultor)
            ->post(route('admin.catalogos.indicadores.asignar-variable', $indicador), [
                'variable_id' => $variable->id,
            ])
            ->assertForbidden();
    }

    public function test_declara_la_periodicidad_de_un_indicador(): void
    {
        $this->crearCatalogo();

        $indicador = Indicador::first();
        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)
            ->post(route('admin.salud-datos.periodicidad', $indicador), [
                'periodicidad' => 'Quinquenal',
            ])
            ->assertRedirect(route('admin.salud-datos'))
            ->assertSessionHas('success');

        $this->assertEquals('Quinquenal', $indicador->refresh()->periodicidad);
    }

    public function test_la_periodicidad_solo_acepta_valores_permitidos(): void
    {
        $this->crearCatalogo();

        $indicador = Indicador::first();
        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)
            ->post(route('admin.salud-datos.periodicidad', $indicador), [
                'periodicidad' => 'Decenal',
            ])
            ->assertSessionHasErrors('periodicidad');
    }

    public function test_solo_quien_puede_editar_catalogos_declara_periodicidad(): void
    {
        $this->crearCatalogo();

        $indicador = Indicador::first();
        $consultor = User::factory()->create();
        $consultor->assignRole('consultor');

        $this->actingAs($consultor)
            ->post(route('admin.salud-datos.periodicidad', $indicador), [
                'periodicidad' => 'Anual',
            ])
            ->assertForbidden();
    }

    private function crearCatalogo(): void
    {
        $dimension = Dimension::create(['nombre' => 'Dimension', 'nombre_tecnico' => 'dimension']);
        $tematica = Tematica::create([
            'nombre' => 'Tematica',
            'nombre_tecnico' => 'tematica',
            'dimension_id' => $dimension->id,
        ]);

        Indicador::create([
            'nombre_amigable' => 'Viviendas',
            'tematica_id' => $tematica->id,
            'tipo_dato' => 'absoluto',
        ]);
    }
}
