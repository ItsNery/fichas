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

    public function test_variables_can_be_reordered_within_their_indicator(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $dimension = Dimension::create(['nombre' => 'Dimension', 'nombre_tecnico' => 'dimension']);
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Tematica',
            'nombre_tecnico' => 'tematica',
        ]);
        $indicador = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Indicador',
            'nombre_tecnico' => 'indicador',
            'tipo_dato' => 'absoluto',
        ]);
        $variables = collect(['Primera', 'Segunda', 'Tercera'])->map(function (string $nombre, int $orden) use ($indicador) {
            return Variable::create([
                'indicador_id' => $indicador->id,
                'nombre_amigable' => $nombre,
                'nombre_tecnico' => str($nombre)->lower(),
                'orden' => $orden + 1,
            ]);
        });

        $this->actingAs($user)
            ->patchJson(route('admin.catalogos.indicadores.variables.reorder', $indicador), [
                'variable_ids' => [$variables[2]->id, $variables[0]->id, $variables[1]->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $variables[2]->fresh()->orden);
        $this->assertSame(2, $variables[0]->fresh()->orden);
        $this->assertSame(3, $variables[1]->fresh()->orden);
    }

    public function test_indicators_can_be_reordered_within_their_theme(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $dimension = Dimension::create(['nombre' => 'Dimension', 'nombre_tecnico' => 'dimension']);
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Tematica',
            'nombre_tecnico' => 'tematica',
        ]);
        $indicadores = collect(['Primero', 'Segundo', 'Tercero'])->map(function (string $nombre, int $orden) use ($tematica) {
            return Indicador::create([
                'tematica_id' => $tematica->id,
                'nombre_amigable' => $nombre,
                'nombre_tecnico' => strtolower($nombre),
                'tipo_dato' => 'absoluto',
                'orden' => $orden + 1,
            ]);
        });

        $this->actingAs($user)
            ->patchJson(route('admin.catalogos.tematicas.indicadores.reorder', $tematica), [
                'indicador_ids' => [$indicadores[1]->id, $indicadores[2]->id, $indicadores[0]->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $indicadores[1]->fresh()->orden);
        $this->assertSame(2, $indicadores[2]->fresh()->orden);
        $this->assertSame(3, $indicadores[0]->fresh()->orden);
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
            'tipo_dato' => 'absoluto',
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

        $this->assertSame('tasa', $indicador->fresh()->tipo_dato);
        $this->assertTrue((bool) $variable->es_construida);
        $this->assertSame('tasa_crecimiento_inegi_2025', $variable->formula_tipo);
        $this->assertSame($poblacion->id, $variable->formula_config['variable_id']);
        $this->assertSame(100, $variable->formula_config['multiplicador']);
    }
}
