<?php

namespace Tests\Feature\Services;

use App\Models\Dimension;
use App\Models\Formula;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\User;
use App\Models\Variable;
use App\Services\FormulaImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormulaImporterTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Indicador $indicador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        $this->actingAs($this->usuario);

        $dimension = Dimension::create(['nombre' => 'Dimension', 'nombre_tecnico' => 'dimension']);
        $tematica = Tematica::create([
            'nombre' => 'Tematica',
            'nombre_tecnico' => 'tematica',
            'dimension_id' => $dimension->id,
        ]);
        $this->indicador = Indicador::create([
            'nombre_amigable' => 'Viviendas',
            'tematica_id' => $tematica->id,
            'tipo_dato' => 'absoluto',
        ]);
    }

    public function test_importa_las_fórmulas_de_las_variables_construidas(): void
    {
        $numerador = $this->variable('Viviendas con drenaje', 'viviendas_drenaje');
        $denominador = $this->variable('Viviendas habitadas', 'viviendas_habitadas');

        $this->variable('Porcentaje estatal', 'porcentaje_estatal', true, 'division', [
            'numerador_variable_id' => $numerador->id,
            'denominador_variable_id' => $denominador->id,
            'multiplicador' => 100,
        ]);

        $resultado = app(FormulaImporter::class)->importar();

        $this->assertCount(1, $resultado['creadas']);
        $this->assertEmpty($resultado['omitidas']);

        $formula = $resultado['creadas'][0];

        $this->assertEquals('SUM(A) / SUM(B) * 100', $formula->expresion);
        $this->assertEquals(Formula::AMBITO_MUNICIPAL, $formula->ambito);

        $slots = $formula->slots();

        $this->assertEquals('A', $slots[0]['clave']);
        $this->assertEquals('Viviendas con drenaje', $slots[0]['nombre']);
        $this->assertEquals($numerador->id, $slots[0]['variable_id']);
        $this->assertEquals($denominador->id, $slots[1]['variable_id']);
    }

    public function test_no_modifica_las_variables_de_origen(): void
    {
        $numerador = $this->variable('Viviendas con drenaje', 'viviendas_drenaje');
        $denominador = $this->variable('Viviendas habitadas', 'viviendas_habitadas');

        $construida = $this->variable('Porcentaje estatal', 'porcentaje_estatal', true, 'division', [
            'numerador_variable_id' => $numerador->id,
            'denominador_variable_id' => $denominador->id,
            'multiplicador' => 100,
        ]);

        app(FormulaImporter::class)->importar();

        $construida->refresh();

        $this->assertEquals(1, (int) $construida->es_construida);
        $this->assertEquals('division', $construida->formula_tipo);
        $this->assertEquals($numerador->id, $construida->formula_config['numerador_variable_id']);
    }

    public function test_importa_sumatorias(): void
    {
        $uno = $this->variable('Hombres', 'hombres');
        $dos = $this->variable('Mujeres', 'mujeres');

        $this->variable('Población total', 'poblacion_total', true, 'sumatoria', [
            'variable_ids' => [$uno->id, $dos->id],
        ]);

        $resultado = app(FormulaImporter::class)->importar();

        $this->assertCount(1, $resultado['creadas']);
        $this->assertEquals('SUM(A) + SUM(B)', $resultado['creadas'][0]->expresion);
    }

    public function test_omite_las_tasas_de_crecimiento_por_no_ser_expresables(): void
    {
        $this->variable('Población', 'poblacion');

        $this->variable('Tasa de crecimiento', 'tasa_crecimiento', true, 'tasa_crecimiento', [
            'variable_id' => 1,
            'multiplicador' => 100,
        ]);

        $resultado = app(FormulaImporter::class)->importar();

        $this->assertEmpty($resultado['creadas']);
        $this->assertCount(1, $resultado['omitidas']);
        $this->assertEquals(FormulaImporter::MOTIVO_INCOMPATIBLE, $resultado['omitidas'][0]['motivo']);
    }

    public function test_no_duplica_cuando_se_importa_dos_veces(): void
    {
        $numerador = $this->variable('Viviendas con drenaje', 'viviendas_drenaje');
        $denominador = $this->variable('Viviendas habitadas', 'viviendas_habitadas');

        $this->variable('Porcentaje estatal', 'porcentaje_estatal', true, 'division', [
            'numerador_variable_id' => $numerador->id,
            'denominador_variable_id' => $denominador->id,
            'multiplicador' => 100,
        ]);

        app(FormulaImporter::class)->importar();
        $resultado = app(FormulaImporter::class)->importar();

        $this->assertEmpty($resultado['creadas']);
        $this->assertCount(1, $resultado['omitidas']);
        $this->assertEquals(FormulaImporter::MOTIVO_DUPLICADA, $resultado['omitidas'][0]['motivo']);
        $this->assertCount(1, Formula::all());
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function variable(
        string $nombre,
        string $tecnico,
        bool $construida = false,
        ?string $tipo = null,
        array $config = []
    ): Variable {
        return Variable::create([
            'indicador_id' => $this->indicador->id,
            'nombre_amigable' => $nombre,
            'nombre_tecnico' => $tecnico,
            'es_construida' => $construida,
            'formula_tipo' => $tipo,
            'formula_config' => $config ?: null,
        ]);
    }
}
