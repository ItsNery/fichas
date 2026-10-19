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
use App\Services\DataHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->municipio = $this->crearMunicipio();

        // Un indicador con serie anual hasta 2025 fija el corte más reciente del portal.
        $referencia = $this->crearIndicador('Referencia');
        $variableReferencia = $this->crearVariable($referencia, 'referencia');

        foreach ([2021, 2022, 2023, 2024, 2025] as $anio) {
            $this->crearDato($variableReferencia, $anio, 100);
        }
    }

    public function test_una_serie_quinquenal_no_se_marca_como_atrasada(): void
    {
        $indicador = $this->crearIndicador('Poblacion');
        $variable = $this->crearVariable($indicador, 'poblacion');

        foreach ([2010, 2015, 2020] as $anio) {
            $this->crearDato($variable, $anio, 100);
        }

        $fila = $this->filaDe($indicador);

        $this->assertEquals(Indicador::PERIODICIDADES['Quinquenal']['etiqueta'], $fila['cadencia']);
        $this->assertEquals(2020, $fila['ultimo']);
        $this->assertEquals(2025, $fila['esperado']);
        $this->assertEquals('pendiente', $fila['estado']);
    }

    public function test_una_serie_anual_con_dos_anos_de_retraso_si_es_atrasada(): void
    {
        $indicador = $this->crearIndicador('Poblacion');
        $variable = $this->crearVariable($indicador, 'poblacion');

        foreach ([2021, 2022, 2023] as $anio) {
            $this->crearDato($variable, $anio, 100);
        }

        $fila = $this->filaDe($indicador);

        $this->assertEquals(Indicador::PERIODICIDADES['Anual']['etiqueta'], $fila['cadencia']);
        $this->assertEquals(2023, $fila['ultimo']);
        $this->assertEquals('atrasado', $fila['estado']);
        $this->assertEquals(2, $fila['retraso']);
    }

    public function test_una_serie_al_dia_no_se_reporta(): void
    {
        $indicador = $this->crearIndicador('Poblacion');
        $variable = $this->crearVariable($indicador, 'poblacion');

        foreach ([2023, 2024, 2025] as $anio) {
            $this->crearDato($variable, $anio, 100);
        }

        $fila = $this->filaDe($indicador);

        $this->assertEquals('al_dia', $fila['estado']);
        $this->assertEquals(0, $fila['retraso']);
    }

    public function test_la_periodicidad_declarada_tiene_prioridad(): void
    {
        $indicador = $this->crearIndicador('Poblacion');
        $variable = $this->crearVariable($indicador, 'poblacion');

        $this->crearDato($variable, 2020, 100);

        $fila = $this->filaDe($indicador);
        $this->assertEquals('Irregular (sin ciclo fijo)', $fila['cadencia']);

        $indicador->update(['periodicidad' => 'Quinquenal']);

        $fila = $this->filaDe($indicador);

        $this->assertEquals(Indicador::PERIODICIDADES['Quinquenal']['etiqueta'], $fila['cadencia']);
        $this->assertEquals(2025, $fila['esperado']);
        $this->assertEquals('pendiente', $fila['estado']);
    }

    public function test_detecta_indicadores_sin_datos(): void
    {
        $indicador = $this->crearIndicador('Poblacion');
        $this->crearVariable($indicador, 'poblacion');

        $fila = $this->filaDe($indicador);

        $this->assertEquals('sin_datos', $fila['estado']);
    }

    public function test_detecta_celdas_sin_valor_ni_motivo(): void
    {
        $indicador = $this->crearIndicador('Poblacion');
        $variable = $this->crearVariable($indicador, 'poblacion');

        DatoHistorico::create([
            'municipio_id' => $this->municipio->id,
            'variable_id' => $variable->id,
            'anio' => 2024,
            'valor' => null,
            'motivo_sin_dato_id' => null,
        ]);

        $celdas = app(DataHealthService::class)->resumen()['celdasSinMotivo'];

        $this->assertCount(1, $celdas);
        $this->assertEquals('Variable poblacion', $celdas[0]['variable']);
        $this->assertEquals(1, $celdas[0]['celdas']);
    }

    private function filaDe(Indicador $indicador): array
    {
        $filas = app(DataHealthService::class)->resumen()['coberturaTemporal']['filas'];

        foreach ($filas as $fila) {
            if ($fila['indicador_id'] === $indicador->id) {
                return $fila;
            }
        }

        $this->fail('El indicador no apareció en la cobertura temporal.');
    }

    private function crearMunicipio(): Municipio
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

        return Municipio::create([
            'nombre' => 'Municipio A',
            'slug' => 'municipio-a',
            'microrregion_id' => $micro->id,
        ]);
    }

    private function crearIndicador(string $nombre): Indicador
    {
        $dimension = Dimension::first() ?? Dimension::create([
            'nombre' => 'Dimension',
            'nombre_tecnico' => 'dimension',
        ]);

        $tematica = Tematica::create([
            'nombre' => 'Tematica ' . uniqid(),
            'nombre_tecnico' => 'tematica_' . uniqid(),
            'dimension_id' => $dimension->id,
        ]);

        return Indicador::create([
            'nombre_amigable' => $nombre,
            'tematica_id' => $tematica->id,
            'tipo_dato' => 'absoluto',
        ]);
    }

    private function crearVariable(Indicador $indicador, string $tecnico): Variable
    {
        return Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Variable ' . $tecnico,
            'nombre_tecnico' => $tecnico,
        ]);
    }

    private function crearDato(Variable $variable, int $anio, float $valor): DatoHistorico
    {
        return DatoHistorico::create([
            'municipio_id' => $this->municipio->id,
            'variable_id' => $variable->id,
            'anio' => $anio,
            'valor' => $valor,
        ]);
    }
}
