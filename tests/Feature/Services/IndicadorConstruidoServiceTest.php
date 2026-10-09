<?php

namespace Tests\Feature\Services;

use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\Variable;
use App\Services\IndicadorConstruidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorConstruidoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_growth_rate_uses_the_immediately_previous_available_year(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Macro';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipio = Municipio::create([
            'nombre' => 'Municipio A',
            'slug' => 'municipio-a',
            'microrregion_id' => $micro->id,
        ]);
        $dimension = Dimension::create([
            'nombre' => 'Dimension',
            'nombre_tecnico' => 'dimension',
        ]);
        $tematica = Tematica::create([
            'nombre' => 'Tematica',
            'nombre_tecnico' => 'tematica',
            'dimension_id' => $dimension->id,
        ]);
        $indicador = Indicador::create([
            'nombre_amigable' => 'Poblacion',
            'tematica_id' => $tematica->id,
        ]);
        $poblacion = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Poblacion total',
            'nombre_tecnico' => 'poblacion_total',
        ]);
        $tasa = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Tasa de crecimiento',
            'nombre_tecnico' => 'tasa_crecimiento',
            'es_construida' => true,
            'formula_tipo' => 'tasa_crecimiento',
            'formula_config' => [
                'variable_id' => $poblacion->id,
                'multiplicador' => 100,
            ],
        ]);
        $tasaInegi = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Tasa de crecimiento INEGI 2025',
            'nombre_tecnico' => 'tasa_crecimiento_inegi_2025',
            'es_construida' => true,
            'formula_tipo' => 'tasa_crecimiento_inegi_2025',
            'formula_config' => [
                'variable_id' => $poblacion->id,
                'multiplicador' => 100,
            ],
        ]);

        foreach ([2020 => 100, 2023 => 121, 2025 => 133.1] as $anio => $valor) {
            DatoHistorico::create([
                'municipio_id' => $municipio->id,
                'variable_id' => $poblacion->id,
                'anio' => $anio,
                'valor' => $valor,
            ]);
        }

        $rows = collect(app(IndicadorConstruidoService::class)->calcularPrevisualizacion($tasa))
            ->keyBy('anio');

        $this->assertCount(2, $rows);
        $this->assertEquals(2020, $rows[2023]['anio_anterior']);
        $this->assertEquals(21, $rows[2023]['valor']);
        $this->assertEquals(2023, $rows[2025]['anio_anterior']);
        $this->assertEquals(10, $rows[2025]['valor']);

        $rowsInegi = collect(app(IndicadorConstruidoService::class)->calcularPrevisualizacion($tasaInegi))
            ->keyBy('anio');

        $this->assertCount(2, $rowsInegi);
        $this->assertEquals(3, $rowsInegi[2023]['periodo_anios']);
        $this->assertEqualsWithDelta(6.5602, $rowsInegi[2023]['valor'], 0.0001);
        $this->assertEquals(2, $rowsInegi[2025]['periodo_anios']);
        $this->assertEqualsWithDelta(4.8809, $rowsInegi[2025]['valor'], 0.0001);
    }
}
