<?php

namespace Tests\Feature\Services;

use App\Models\DatoGeograficoHistorico;
use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Formula;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\User;
use App\Models\Variable;
use App\Services\FormulaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormulaServiceTest extends TestCase
{
    use RefreshDatabase;

    private Variable $drenaje;

    private Variable $habitadas;

    private Municipio $municipio;

    public function test_calcula_un_porcentaje_estatal_con_sumas_de_municipios(): void
    {
        $this->crearCatalogo();
        $this->crearDatosMunicipales();

        $formula = Formula::create([
            'nombre' => 'Porcentaje estatal de viviendas con drenaje',
            'nombre_tecnico' => 'porcentaje_estatal_drenaje',
            'ambito' => Formula::AMBITO_ESTATAL,
            'expresion' => 'SUM(A) / SUM(B) * 100',
            'slots' => [
                ['clave' => 'A', 'nombre' => 'Viviendas habitadas con drenaje'],
                ['clave' => 'B', 'nombre' => 'Viviendas particulares habitadas'],
            ],
        ]);

        $filas = app(FormulaService::class)->previsualizar($formula, [
            'A' => $this->drenaje->id,
            'B' => $this->habitadas->id,
        ]);

        $this->assertCount(1, $filas);
        $this->assertEquals(2020, $filas[0]['anio']);
        $this->assertEqualsWithDelta(50.0, $filas[0]['valor'], 0.0001);
    }

    public function test_genera_datos_estatales_en_dato_geografico_historico(): void
    {
        $this->actingAs(User::factory()->create());
        $this->crearCatalogo();
        $this->crearDatosMunicipales();

        $formula = Formula::create([
            'nombre' => 'Porcentaje estatal de viviendas con drenaje',
            'nombre_tecnico' => 'porcentaje_estatal_drenaje',
            'ambito' => Formula::AMBITO_ESTATAL,
            'expresion' => 'SUM(A) / SUM(B) * 100',
            'slots' => [
                ['clave' => 'A', 'nombre' => 'Drenaje'],
                ['clave' => 'B', 'nombre' => 'Habitadas'],
            ],
        ]);

        $indicadorPorcentaje = Indicador::create([
            'nombre_amigable' => 'Porcentajes',
            'tematica_id' => $this->drenaje->indicador->tematica_id,
            'tipo_dato' => 'porcentaje',
        ]);

        $variableResultado = Variable::create([
            'indicador_id' => $indicadorPorcentaje->id,
            'nombre_amigable' => 'Porcentaje estatal de drenaje',
            'nombre_tecnico' => 'porcentaje_estatal_drenaje',
            'es_construida' => true,
            'formula_tipo' => 'division',
            'formula_config' => ['formula_id' => $formula->id],
        ]);

        $total = app(FormulaService::class)->generar($formula, [
            'A' => $this->drenaje->id,
            'B' => $this->habitadas->id,
        ], $variableResultado);

        $this->assertEquals(1, $total);

        $dato = DatoGeograficoHistorico::where('variable_id', $variableResultado->id)->first();

        $this->assertNotNull($dato);
        $this->assertEquals(2020, (int) $dato->anio);
        $this->assertEqualsWithDelta(50.0, (float) $dato->valor, 0.0001);
    }

    public function test_exige_asignar_todas_las_variables(): void
    {
        $this->crearCatalogo();

        $formula = Formula::create([
            'nombre' => 'Porcentaje estatal',
            'nombre_tecnico' => 'porcentaje_estatal',
            'ambito' => Formula::AMBITO_ESTATAL,
            'expresion' => 'SUM(A) / SUM(B) * 100',
            'slots' => [
                ['clave' => 'A', 'nombre' => ''],
                ['clave' => 'B', 'nombre' => ''],
            ],
        ]);

        $this->expectException(\App\Exceptions\FormulaException::class);

        app(FormulaService::class)->previsualizar($formula, ['A' => $this->drenaje->id]);
    }

    private function crearCatalogo(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Macro';
        $macro->slug = 'macro';
        $macro->save();

        $micro = new Microrregion();
        $micro->nombre = 'Micro';
        $micro->slug = 'micro';
        $micro->macrorregion_id = $macro->id;
        $micro->save();

        $municipio = Municipio::create([
            'nombre' => 'Municipio A',
            'slug' => 'municipio-a',
            'microrregion_id' => $micro->id,
        ]);

        $dimension = Dimension::create(['nombre' => 'Dimension', 'nombre_tecnico' => 'dimension']);
        $tematica = Tematica::create([
            'nombre' => 'Tematica',
            'nombre_tecnico' => 'tematica',
            'dimension_id' => $dimension->id,
        ]);
        $indicador = Indicador::create([
            'nombre_amigable' => 'Viviendas',
            'tematica_id' => $tematica->id,
        ]);

        $this->drenaje = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Viviendas con drenaje',
            'nombre_tecnico' => 'viviendas_drenaje',
        ]);

        $this->habitadas = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Viviendas habitadas',
            'nombre_tecnico' => 'viviendas_habitadas',
        ]);

        $this->municipio = $municipio;
    }

    private function crearDatosMunicipales(): void
    {
        DatoHistorico::create([
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->drenaje->id,
            'anio' => 2020,
            'valor' => 30,
        ]);

        DatoHistorico::create([
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->habitadas->id,
            'anio' => 2020,
            'valor' => 60,
        ]);
    }
}
