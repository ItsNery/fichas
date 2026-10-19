<?php

namespace Tests\Feature;

use App\Models\Formula;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFormulaControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->create();
        $this->usuario->assignRole('super_admin');
    }

    public function test_listado_y_formulario_de_fórmulas_se_renderizan(): void
    {
        Formula::create([
            'nombre' => 'Porcentaje estatal de drenaje',
            'nombre_tecnico' => 'porcentaje_estatal_drenaje',
            'ambito' => Formula::AMBITO_ESTATAL,
            'expresion' => 'SUM(A) / SUM(B) * 100',
            'slots' => [['clave' => 'A', 'nombre' => 'Drenaje']],
        ]);

        $this->actingAs($this->usuario)->get(route('admin.formulas.index'))->assertOk()->assertSee('Porcentaje estatal de drenaje');
        $this->actingAs($this->usuario)->get(route('admin.formulas.create'))->assertOk();
    }

    public function test_crea_una_fórmula_y_la_persiste(): void
    {
        $response = $this->actingAs($this->usuario)->post(route('admin.formulas.store'), [
            'nombre' => 'Porcentaje estatal de viviendas con drenaje',
            'ambito' => Formula::AMBITO_ESTATAL,
            'expresion' => 'SUM(A) / SUM(B) * 100',
            'slots' => [
                ['clave' => 'A', 'nombre' => 'Viviendas con drenaje'],
                ['clave' => 'B', 'nombre' => 'Viviendas habitadas'],
            ],
            'activa' => 1,
        ]);

        $formula = Formula::first();

        $this->assertNotNull($formula);
        $this->assertEquals('SUM(A) / SUM(B) * 100', $formula->expresion);
        $this->assertEquals(['A', 'B'], collect($formula->slots())->pluck('clave')->all());
        $response->assertRedirect(route('admin.formulas.show', $formula));
    }

    public function test_rechaza_expresiones_invalidas(): void
    {
        $this->actingAs($this->usuario)
            ->post(route('admin.formulas.store'), [
                'nombre' => 'Fórmula inválida',
                'ambito' => Formula::AMBITO_ESTATAL,
                'expresion' => 'DROP TABLE variables',
            ])
            ->assertSessionHasErrors('expresion');

        $this->assertNull(Formula::first());
    }

    public function test_pantalla_de_uso_muestra_las_variables_requeridas(): void
    {
        $formula = Formula::create([
            'nombre' => 'Porcentaje estatal',
            'nombre_tecnico' => 'porcentaje_estatal',
            'ambito' => Formula::AMBITO_ESTATAL,
            'expresion' => 'SUM(A) / SUM(B) * 100',
            'slots' => [
                ['clave' => 'A', 'nombre' => 'Drenaje'],
                ['clave' => 'B', 'nombre' => 'Habitadas'],
            ],
        ]);

        $this->actingAs($this->usuario)
            ->get(route('admin.formulas.show', $formula))
            ->assertOk()
            ->assertSee('SUM(A) / SUM(B) * 100');
    }

    public function test_importa_las_fórmulas_existentes_sin_tocar_las_variables(): void
    {
        $indicador = \App\Models\Indicador::create([
            'nombre_amigable' => 'Viviendas',
            'tematica_id' => \App\Models\Tematica::create([
                'nombre' => 'Tematica',
                'nombre_tecnico' => 'tematica',
                'dimension_id' => \App\Models\Dimension::create([
                    'nombre' => 'Dimension',
                    'nombre_tecnico' => 'dimension',
                ])->id,
            ])->id,
        ]);

        $numerador = \App\Models\Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Con drenaje',
            'nombre_tecnico' => 'con_drenaje',
        ]);
        $denominador = \App\Models\Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Habitadas',
            'nombre_tecnico' => 'habitadas',
        ]);
        $construida = \App\Models\Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Porcentaje',
            'nombre_tecnico' => 'porcentaje',
            'es_construida' => true,
            'formula_tipo' => 'division',
            'formula_config' => [
                'numerador_variable_id' => $numerador->id,
                'denominador_variable_id' => $denominador->id,
                'multiplicador' => 100,
            ],
        ]);

        $this->actingAs($this->usuario)
            ->post(route('admin.formulas.importar'), ['ambito' => 'estatal'])
            ->assertRedirect(route('admin.formulas.index'));

        $this->assertCount(1, Formula::all());
        $this->assertEquals('SUM(A) / SUM(B) * 100', Formula::first()->expresion);

        // La variable de origen permanece intacta.
        $construida->refresh();
        $this->assertEquals('division', $construida->formula_tipo);
        $this->assertEquals($numerador->id, $construida->formula_config['numerador_variable_id']);
    }

    public function test_consultor_no_puede_crear_fórmulas(): void
    {
        $consultor = User::factory()->create();
        $consultor->assignRole('consultor');

        $this->actingAs($consultor)->get(route('admin.formulas.index'))->assertOk();
        $this->actingAs($consultor)->post(route('admin.formulas.store'), [])->assertForbidden();
    }

    public function test_preview_detecta_variables_y_reporta_error_de_asignacion(): void
    {
        $response = $this->actingAs($this->usuario)->postJson(route('admin.formulas.preview'), [
            'expresion' => 'SUM(A) / SUM(B) * 100',
            'ambito' => Formula::AMBITO_ESTATAL,
            'asignaciones' => [],
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $this->assertEquals(['A', 'B'], $response->json('slots'));
    }
}
