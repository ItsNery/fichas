<?php

namespace Tests\Feature\Services;

use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\Variable;
use App\Services\FichaComposerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FichaComposerServiceTest extends TestCase
{
    use RefreshDatabase;

    private FichaComposerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FichaComposerService::class);
    }

    public function test_formatear_echarts_bar()
    {
        $result = $this->service->formatearDatosParaECharts(
            [['nombre' => 'V1', 'valor' => 10, 'unidad' => '%'], ['nombre' => 'V2', 'valor' => 20, 'unidad' => '%']],
            'barras'
        );
        $this->assertEquals('bar', $result['type']);
        $this->assertCount(1, $result['series']);
        $this->assertEquals(['V1', 'V2'], $result['eje_x']['categorias']);
    }

    public function test_formatear_echarts_line_with_trend()
    {
        $result = $this->service->formatearDatosParaECharts(
            [['nombre' => 'V1', 'valor' => 10, 'unidad' => '%']],
            'lineas', null, null,
            [['anio' => 2018, 'valor' => 5], ['anio' => 2019, 'valor' => 8], ['anio' => 2020, 'valor' => 10]]
        );
        $this->assertEquals('line', $result['type']);
        $this->assertCount(1, $result['series']);
    }

    public function test_formatear_echarts_line_with_benchmarks()
    {
        $result = $this->service->formatearDatosParaECharts(
            [['nombre' => 'V1', 'valor' => 10, 'unidad' => '%']],
            'line', null, null,
            [['anio' => 2018, 'valor' => 5], ['anio' => 2019, 'valor' => 8]],
            [2018 => 4.5, 2019 => 7.2],
            [2018 => 4.0, 2019 => 6.5]
        );
        $this->assertCount(3, $result['series']);
    }

    public function test_formatear_echarts_pie()
    {
        $result = $this->service->formatearDatosParaECharts(
            [['nombre' => 'A', 'valor' => 30, 'unidad' => '%'], ['nombre' => 'B', 'valor' => 70, 'unidad' => '%']],
            'pie'
        );
        $this->assertEquals('pie', $result['type']);
        $this->assertCount(2, $result['series'][0]['data']);
    }

    public function test_formatear_echarts_treemap()
    {
        $result = $this->service->formatearDatosParaECharts(
            [['nombre' => 'A', 'valor' => 50, 'unidad' => ''], ['nombre' => 'B', 'valor' => 50, 'unidad' => '']],
            'treemap'
        );
        $this->assertEquals('treemap', $result['type']);
    }

    private function makePair(): array
    {
        $macro = new Macrorregion(); $macro->nombre = 'M'; $macro->save();
        $micro = new Microrregion(); $micro->nombre = 'm'; $micro->macrorregion_id = $macro->id; $micro->save();
        return [
            Municipio::create(['nombre' => 'A', 'slug' => 'a', 'microrregion_id' => $micro->id]),
            Municipio::create(['nombre' => 'B', 'slug' => 'b', 'microrregion_id' => $micro->id]),
        ];
    }

    public function test_combinar_datos_para_echarts_bar()
    {
        [$m1, $m2] = $this->makePair();
        $config = new ConfiguracionFicha(); $config->tipo_visualizacion = 'bar';
        $result = $this->service->combinarDatosParaECharts(
            $config,
            ['variables' => [['nombre' => 'Pob', 'valor' => 100, 'unidad' => 'hab']]],
            ['variables' => [['nombre' => 'Pob', 'valor' => 200, 'unidad' => 'hab']]],
            $m1, $m2
        );
        $this->assertEquals('bar', $result['type']);
        $this->assertCount(2, $result['series']);
    }

    public function test_combinar_datos_para_echarts_line()
    {
        [$m1, $m2] = $this->makePair();
        $config = new ConfiguracionFicha(); $config->tipo_visualizacion = 'line';
        $result = $this->service->combinarDatosParaECharts(
            $config,
            ['tendencia' => [['anio' => 2018, 'valor' => 5], ['anio' => 2019, 'valor' => 8]]],
            ['tendencia' => [['anio' => 2018, 'valor' => 6], ['anio' => 2019, 'valor' => 9]]],
            $m1, $m2
        );
        $this->assertEquals('line', $result['type']);
        $this->assertCount(2, $result['series']);
    }

    public function test_combinar_returns_null_when_missing_data()
    {
        [$m1, $m2] = $this->makePair();
        $this->assertNull($this->service->combinarDatosParaECharts(new ConfiguracionFicha(), null, ['variables' => []], $m1, $m2));
    }

    public function test_scatter_uses_ordered_axes_generic_sources_and_per_capita_values()
    {
        [$m1, $m2] = $this->makePair();
        $dimension = Dimension::create(['nombre' => 'Prueba', 'nombre_tecnico' => 'prueba']);
        $tematica = Tematica::create(['dimension_id' => $dimension->id, 'nombre' => 'Cruces']);
        $indicadorX = Indicador::create(['tematica_id' => $tematica->id, 'nombre_amigable' => 'Recursos', 'fuente' => 'SHCP']);
        $indicadorY = Indicador::create(['tematica_id' => $tematica->id, 'nombre_amigable' => 'Carencia', 'fuente' => 'CONEVAL']);
        $indicadorPob = Indicador::create(['tematica_id' => $tematica->id, 'nombre_amigable' => 'Población', 'fuente' => 'INEGI']);
        $varX = Variable::create(['indicador_id' => $indicadorX->id, 'nombre_tecnico' => 'monto_test', 'nombre_amigable' => 'Monto ejercido', 'unidad_medida' => 'Miles de pesos']);
        $varY = Variable::create(['indicador_id' => $indicadorY->id, 'nombre_tecnico' => 'carencia_test', 'nombre_amigable' => 'Población con carencia', 'unidad_medida' => 'Porcentaje']);
        $poblacion = Variable::create(['indicador_id' => $indicadorPob->id, 'nombre_tecnico' => 'poblacion_test', 'nombre_amigable' => 'Población total', 'unidad_medida' => 'Habitantes']);
        $config = ConfiguracionFicha::create([
            'indicador_id' => $indicadorX->id,
            'seccion' => 'prueba',
            'orden' => 1,
            'tipo_visualizacion' => 'scatter',
            'clase_grid' => 'col-12',
        ]);
        $config->variables()->attach([$varX->id, $varY->id]);

        foreach ([[$m1, 100, 25, 1000], [$m2, 300, 40, 2000]] as [$municipio, $x, $y, $pob]) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $varX->id, 'anio' => 2025, 'valor' => $x]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $varY->id, 'anio' => 2020, 'valor' => $y]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $poblacion->id, 'anio' => 2020, 'valor' => $pob]);
        }

        $result = $this->service->obtenerDatosParaConfig(
            $config->fresh(['indicador', 'variables']),
            $m1
        );

        $this->assertSame('Monto ejercido per cápita ($/hab)', $result['echarts']['eje_x']['titulo']);
        $this->assertSame('$ por habitante', $result['variables'][0]['unidad']);
        $this->assertSame(100.0, $result['variables'][0]['valor']);
        $this->assertSame('SHCP / CONEVAL', $result['fuente']);
        $this->assertStringContainsString('2025 para el eje X y 2020 para el eje Y', $result['descripcion']);
        $this->assertSame(1.0, $result['correlacion']);
        $this->assertStringContainsString('asociación lineal observada', $result['descripcion']);
    }

    public function test_regional_scatter_separates_context_region_and_median(): void
    {
        [$municipioA, $municipioB] = $this->makePair();
        $municipioC = Municipio::create([
            'nombre' => 'C',
            'slug' => 'c',
            'microrregion_id' => $municipioA->microrregion_id,
        ]);
        $dimension = Dimension::create(['nombre' => 'Regional', 'nombre_tecnico' => 'regional']);
        $tematica = Tematica::create(['dimension_id' => $dimension->id, 'nombre' => 'Cruces regionales']);
        $indicadorX = Indicador::create(['tematica_id' => $tematica->id, 'nombre_amigable' => 'Indicador X', 'fuente' => 'Fuente X']);
        $indicadorY = Indicador::create(['tematica_id' => $tematica->id, 'nombre_amigable' => 'Indicador Y', 'fuente' => 'Fuente Y']);
        $variableX = Variable::create(['indicador_id' => $indicadorX->id, 'nombre_tecnico' => 'regional_x', 'nombre_amigable' => 'Variable X', 'unidad_medida' => 'Porcentaje']);
        $variableY = Variable::create(['indicador_id' => $indicadorY->id, 'nombre_tecnico' => 'regional_y', 'nombre_amigable' => 'Variable Y', 'unidad_medida' => 'Porcentaje']);
        $configuracion = ConfiguracionFicha::create([
            'indicador_id' => $indicadorX->id,
            'seccion' => 'regional',
            'orden' => 1,
            'tipo_visualizacion' => 'scatter',
            'clase_grid' => 'col-12',
        ]);
        $configuracion->variables()->attach([$variableX->id, $variableY->id]);

        foreach ([[$municipioA, 1, 10], [$municipioB, 2, 20], [$municipioC, 3, 30]] as [$municipio, $x, $y]) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $variableX->id, 'anio' => 2020, 'valor' => $x]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $variableY->id, 'anio' => 2020, 'valor' => $y]);
        }
        $region = new Macrorregion();
        $region->nombre = 'Región seleccionada';

        $result = $this->service->obtenerScatterRegional(
            $configuracion->fresh(['indicador.variables', 'variables']),
            $region,
            collect([$municipioA, $municipioB])
        );

        $this->assertSame(2, $result['valor_actual']);
        $this->assertCount(1, $result['echarts']['series'][0]['data']);
        $this->assertCount(2, $result['echarts']['series'][1]['data']);
        $this->assertSame('a', $result['echarts']['series'][1]['data'][0][3]);
        $this->assertCount(2, $result['echarts']['series']);
        $this->assertSame(1.5, $result['variables'][0]['valor']);
        $this->assertSame(15.0, $result['variables'][1]['valor']);
        $this->assertNull($result['correlacion']);
        $this->assertStringContainsString('menos de cinco municipios', $result['correlacion_lectura']);
    }

    public function test_get_wikipedia_summary_returns_cached_data()
    {
        Http::fake(['es.wikipedia.org/*' => Http::response([
            'title' => 'Municipio de Acajete (Puebla)',
            'extract' => 'Acajete es un municipio...',
            'thumbnail' => ['source' => 'https://example.com/img.jpg'],
        ])]);
        $result = $this->service->getWikipediaSummary('Acajete');
        $this->assertEquals('Acajete es un municipio...', $result['extract']);
    }

    public function test_get_wikipedia_summary_handles_disambiguation()
    {
        Http::fake(['es.wikipedia.org/*' => Http::sequence()
            ->push(['type' => 'disambiguation', 'title' => 'Acajete'])
            ->push(['type' => 'standard', 'title' => 'Acajete', 'extract' => 'Correcto']),
        ]);
        $result = $this->service->getWikipediaSummary('Acajete');
        $this->assertEquals('Correcto', $result['extract']);
    }
}
