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

    public function test_inegi_growth_formula_is_persisted_with_a_fixed_multiplier(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $dimension = Dimension::create([
            'nombre' => 'Dimension de prueba',
            'nombre_tecnico' => 'dimension_prueba',
        ]);
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Tematica de prueba',
            'nombre_tecnico' => 'tematica_prueba',
        ]);
        $indicadorFuente = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Poblacion total',
            'nombre_tecnico' => 'poblacion_total',
            'tipo_dato' => 'absoluto',
        ]);
        $poblacion = Variable::create([
            'indicador_id' => $indicadorFuente->id,
            'nombre_amigable' => 'Poblacion total',
            'nombre_tecnico' => 'poblacion_total',
        ]);
        $indicador = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Tasa de crecimiento INEGI 2025',
            'nombre_tecnico' => 'tasa_crecimiento_inegi_2025',
            'tipo_dato' => 'tasa',
        ]);
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Tasa de crecimiento INEGI 2025',
            'nombre_tecnico' => 'tasa_crecimiento_inegi_2025',
            'unidad_medida' => 'Porcentaje',
        ]);

        $this->actingAs($user)->put(
            route('admin.catalogos.indicadores.actualizar', $indicador),
            [
                'nombre_amigable' => $indicador->nombre_amigable,
                'nombre_tecnico' => $indicador->nombre_tecnico,
                'tematica_id' => $tematica->id,
                'tipo_dato' => 'tasa',
                'variables' => [[
                    'id' => $variable->id,
                    'nombre_amigable' => $variable->nombre_amigable,
                    'nombre_tecnico' => $variable->nombre_tecnico,
                    'unidad_medida' => $variable->unidad_medida,
                    'es_construida' => 1,
                    'formula_tipo' => 'tasa_crecimiento_inegi_2025',
                    'formula_variable_id' => $poblacion->id,
                    'formula_multiplicador' => 25,
                ]],
            ],
        )->assertRedirect();

        $variable->refresh();

        $this->assertTrue((bool) $variable->es_construida);
        $this->assertSame('tasa_crecimiento_inegi_2025', $variable->formula_tipo);
        $this->assertSame($poblacion->id, $variable->formula_config['variable_id']);
        $this->assertSame(100, $variable->formula_config['multiplicador']);
    }
}
