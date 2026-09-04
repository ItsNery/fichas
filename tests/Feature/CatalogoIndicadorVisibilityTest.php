<?php

namespace Tests\Feature;

use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\Variable;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoIndicadorVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unchecked_variable_visibility_is_persisted_as_false(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $dimension = Dimension::create([
            'nombre' => 'Dimensión de prueba',
            'nombre_tecnico' => 'dimension_prueba',
        ]);
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Temática de prueba',
            'nombre_tecnico' => 'tematica_prueba',
        ]);
        $indicador = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Indicador de prueba',
            'nombre_tecnico' => 'indicador_prueba',
            'tipo_dato' => 'absoluto',
        ]);
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Variable de prueba',
            'nombre_tecnico' => 'variable_prueba',
            'unidad_medida' => 'Habitantes',
            'visible_en_ficha' => true,
        ]);

        $this->actingAs($user)->put(
            route('admin.catalogos.indicadores.actualizar', $indicador),
            [
                'nombre_amigable' => $indicador->nombre_amigable,
                'nombre_tecnico' => $indicador->nombre_tecnico,
                'tematica_id' => $tematica->id,
                'tipo_dato' => 'absoluto',
                'variables' => [[
                    'id' => $variable->id,
                    'nombre_amigable' => $variable->nombre_amigable,
                    'nombre_tecnico' => $variable->nombre_tecnico,
                    'unidad_medida' => $variable->unidad_medida,
                ]],
            ],
        )->assertRedirect();

        $this->assertFalse($variable->fresh()->visible_en_ficha);
    }
}
