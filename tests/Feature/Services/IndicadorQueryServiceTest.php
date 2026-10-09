<?php

namespace Tests\Feature\Services;

use App\Models\DatoHistorico;
use App\Models\DatoGeograficoHistorico;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\Variable;
use App\Models\UnidadGeografica;
use App\Services\IndicadorQueryService;
use Database\Seeders\UnidadesGeograficasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private IndicadorQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IndicadorQueryService::class);
    }

    private function makeIndicador(string $nombre = 'Test', array $extra = []): Indicador
    {
        $dim = Dimension::create(['nombre' => 'D', 'nombre_tecnico' => 'd']);
        $tem = Tematica::create(['nombre' => 'T', 'nombre_tecnico' => 't', 'dimension_id' => $dim->id]);
        return Indicador::create(array_merge(['nombre_amigable' => $nombre, 'tematica_id' => $tem->id], $extra));
    }

    private function makeMunicipio(string $nombre = 'M1'): Municipio
    {
        $macro = new Macrorregion(); $macro->nombre = 'M'; $macro->save();
        $micro = new Microrregion(); $micro->nombre = 'm'; $micro->macrorregion_id = $macro->id; $micro->save();
        return Municipio::create(['nombre' => $nombre, 'slug' => strtolower($nombre), 'microrregion_id' => $micro->id]);
    }

    public function test_get_indicator_years_returns_sorted_years()
    {
        $ind = $this->makeIndicador();
        $var = Variable::create(['indicador_id' => $ind->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1']);
        $mun = $this->makeMunicipio();
        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 1]);
        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2021, 'valor' => 2]);
        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2019, 'valor' => 3]);

        $this->assertEquals([2021, 2020, 2019], $this->service->getIndicatorYears($ind)->toArray());
    }

    public function test_get_anios_por_dimension()
    {
        $dim = Dimension::create(['nombre' => 'Social', 'color' => '#fff', 'nombre_tecnico' => 'social']);
        $tem = Tematica::create(['nombre' => 'Edu', 'dimension_id' => $dim->id, 'nombre_tecnico' => 'edu']);
        $ind = Indicador::create(['nombre_amigable' => 'Test', 'tematica_id' => $tem->id]);
        $var = Variable::create(['indicador_id' => $ind->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1']);
        $mun = $this->makeMunicipio();
        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2022, 'valor' => 1]);
        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 2]);

        $this->assertEquals([2022, 2020], $this->service->getAniosPorDimension($dim)->toArray());
    }

    public function test_prepare_geographic_selection_municipio()
    {
        $mun = $this->makeMunicipio('Acajete');
        $result = $this->service->prepareGeographicSelection('municipio', ['municipio_ids' => [$mun->id]]);
        $this->assertEquals([$mun->id], $result['ids']);
        $this->assertEquals($mun->nombre, $result['titulo']);
    }

    public function test_prepare_geographic_selection_estatal()
    {
        $result = $this->service->prepareGeographicSelection('municipio', ['municipio_ids' => ['estatal']]);
        $this->assertEquals(['estatal'], $result['ids']);
        $this->assertEquals('Total Estatal', $result['titulo']);
    }

    public function test_prepare_geographic_selection_state_level_returns_all_municipalities(): void
    {
        $municipioA = $this->makeMunicipio('A');
        $municipioB = $this->makeMunicipio('B');

        $result = $this->service->prepareGeographicSelection('estatal', []);

        $this->assertEqualsCanonicalizing([$municipioA->id, $municipioB->id], $result['ids']);
        $this->assertSame('Estado de Puebla', $result['titulo']);
        $this->assertEqualsCanonicalizing(['A', 'B'], $result['nombres_municipios']);
    }

    public function test_get_chart_data_simple_bar()
    {
        $ind = $this->makeIndicador('Test', ['tipo_grafico_default' => 'barras']);
        $var = Variable::create(['indicador_id' => $ind->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1', 'unidad_medida' => '%']);
        $mun = $this->makeMunicipio('Acajete');
        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 50]);
        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2019, 'valor' => 40]);

        $result = $this->service->getChartData([
            'indicador_id' => $ind->id,
            'nivel_de_agregacion' => 'municipio',
            'municipio_ids' => [$mun->id],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('series', $result);
    }

    public function test_get_anios_por_indicador_complejo_returns_empty_for_simple()
    {
        $this->assertTrue($this->service->getAniosPorIndicadorComplejo($this->makeIndicador('Simple'))->isEmpty());
    }

    public function test_population_pyramid_aggregates_both_sexes_for_multiple_municipalities()
    {
        $indicador = $this->makeIndicador('Población por grupos de edad según sexo');
        $hombres = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Población de hombres de 85 a 89 años',
            'nombre_tecnico' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_85_a_89_anos',
            'unidad_medida' => 'Habitantes',
        ]);
        $mujeres = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Población de mujeres de 85 a 89 años',
            'nombre_tecnico' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_85_a_89_anos',
            'unidad_medida' => 'Habitantes',
        ]);
        $municipioA = $this->makeMunicipio('A');
        $municipioB = $this->makeMunicipio('B');

        foreach ([[$municipioA, 10, 15], [$municipioB, 20, 25]] as [$municipio, $valorHombres, $valorMujeres]) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $hombres->id, 'anio' => 2020, 'valor' => $valorHombres]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $mujeres->id, 'anio' => 2020, 'valor' => $valorMujeres]);
        }

        $result = $this->service->handlePiramideChart($indicador, [
            'ids' => [$municipioA->id, $municipioB->id],
            'titulo' => 'Región de prueba',
        ], [$hombres->id, $mujeres->id]);

        $index = array_search('85 a 89 años', $result['eje_x']['categorias'], true);
        $this->assertSame('piramide', $result['tipo_grafico']);
        $this->assertSame(-30.0, $result['series'][0]['data'][$index]);
        $this->assertSame(40.0, $result['series'][1]['data'][$index]);
        $this->assertSame(2020, $result['anio']);
    }

    public function test_population_pyramid_uses_the_requested_year(): void
    {
        $indicador = $this->makeIndicador('Población por grupos de edad según sexo');
        $hombres = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Población de hombres de 85 a 89 años',
            'nombre_tecnico' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_85_a_89_anos',
            'unidad_medida' => 'Habitantes',
        ]);
        $mujeres = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Población de mujeres de 85 a 89 años',
            'nombre_tecnico' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_85_a_89_anos',
            'unidad_medida' => 'Habitantes',
        ]);
        $municipio = $this->makeMunicipio();

        DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $hombres->id, 'anio' => 2020, 'valor' => 10]);
        DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $mujeres->id, 'anio' => 2020, 'valor' => 15]);
        DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $hombres->id, 'anio' => 2025, 'valor' => 30]);
        DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $mujeres->id, 'anio' => 2025, 'valor' => 45]);

        $result = $this->service->getChartData([
            'indicador_id' => $indicador->id,
            'nivel_de_agregacion' => 'municipio',
            'municipio_ids' => [$municipio->id],
            'anios' => [2025],
        ]);

        $index = array_search('85 a 89 años', $result['eje_x']['categorias'], true);

        $this->assertSame(2025, $result['anio']);
        $this->assertSame([2025], $result['selected_years']);
        $this->assertSame(-30.0, $result['series'][0]['data'][$index]);
        $this->assertSame(45.0, $result['series'][1]['data'][$index]);
    }

    public function test_population_pyramid_includes_100_years_and_over(): void
    {
        $indicador = $this->makeIndicador('Población por grupos de edad según sexo');
        $variables = collect([
            ['nombre_amigable' => 'Población de hombres de 85 a 89 años', 'nombre_tecnico' => 'poblacion_hombres_de_85_a_89_anos', 'valor' => 10],
            ['nombre_amigable' => 'Población de mujeres de 85 a 89 años', 'nombre_tecnico' => 'poblacion_mujeres_de_85_a_89_anos', 'valor' => 15],
            ['nombre_amigable' => 'Población de hombres de 100 años y más', 'nombre_tecnico' => 'poblacion_hombres_de_100_anos_y_mas', 'valor' => 20],
            ['nombre_amigable' => 'Población de mujeres de 100 años y más', 'nombre_tecnico' => 'poblacion_mujeres_de_100_anos_y_mas', 'valor' => 25],
        ])->map(function (array $data) use ($indicador) {
            return Variable::create([
                'indicador_id' => $indicador->id,
                'nombre_amigable' => $data['nombre_amigable'],
                'nombre_tecnico' => $data['nombre_tecnico'],
                'unidad_medida' => 'Habitantes',
            ]);
        });
        $municipio = $this->makeMunicipio();

        foreach ($variables as $index => $variable) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $variable->id, 'anio' => 2025, 'valor' => [10, 15, 20, 25][$index]]);
        }

        $result = $this->service->handlePiramideChart($indicador, [
            'ids' => [$municipio->id],
            'titulo' => $municipio->nombre,
        ], $variables->pluck('id')->all(), [2025]);

        $index = array_search('100 años y más', $result['eje_x']['categorias'], true);

        $this->assertNotFalse($index);
        $this->assertSame(-20.0, $result['series'][0]['data'][$index]);
        $this->assertSame(25.0, $result['series'][1]['data'][$index]);
    }

    public function test_aggregated_percentage_view_averages_municipal_values(): void
    {
        $dummy = $this->makeIndicador('Población por grupos de edad según sexo');
        $ind = Indicador::create([
            'nombre_amigable' => 'Porcentaje regional',
            'tematica_id' => $dummy->tematica_id,
            'tipo_grafico_default' => 'lineas',
        ]);
        $var = Variable::create([
            'indicador_id' => $ind->id,
            'nombre_amigable' => 'Porcentaje',
            'nombre_tecnico' => 'porcentaje',
            'unidad_medida' => '%',
        ]);
        $municipioA = $this->makeMunicipio('A');
        $municipioB = $this->makeMunicipio('B');
        $municipioB->microrregion_id = $municipioA->microrregion_id;
        $municipioB->save();
        foreach ([[$municipioA, 10], [$municipioB, 30]] as [$municipio, $valor]) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => $valor]);
        }

        $result = $this->service->getChartData([
            'indicador_id' => $ind->id,
            'nivel_de_agregacion' => 'microrregion',
            'region_id' => $municipioA->microrregion_id,
            'anios' => [2020],
        ]);

        $this->assertSame(20.0, $result['series'][0]['data'][0][1]);
    }

    public function test_state_level_aggregates_absolute_values(): void
    {
        $ind = $this->makeIndicador('Total estatal', ['tipo_grafico_default' => 'barras']);
        $var = Variable::create([
            'indicador_id' => $ind->id,
            'nombre_amigable' => 'Habitantes',
            'nombre_tecnico' => 'habitantes',
            'unidad_medida' => 'Habitantes',
        ]);
        $municipioA = $this->makeMunicipio('A');
        $municipioB = $this->makeMunicipio('B');

        DatoHistorico::create(['municipio_id' => $municipioA->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 10]);
        DatoHistorico::create(['municipio_id' => $municipioB->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 30]);

        $result = $this->service->getChartData([
            'indicador_id' => $ind->id,
            'nivel_de_agregacion' => 'estatal',
            'anios' => [2020],
        ]);

        $this->assertSame(40.0, $result['series'][0]['data'][0][1]);
        $this->assertStringContainsString('Estado de Puebla', $result['titulo']);
    }

    public function test_official_state_value_has_priority_over_municipal_aggregation(): void
    {
        $this->seed(UnidadesGeograficasSeeder::class);
        $ind = $this->makeIndicador('Porcentaje oficial', [
            'tipo_dato' => 'porcentaje',
            'tipo_grafico_default' => 'barras',
        ]);
        $var = Variable::create([
            'indicador_id' => $ind->id,
            'nombre_amigable' => 'Porcentaje',
            'nombre_tecnico' => 'porcentaje_oficial',
            'unidad_medida' => '%',
        ]);
        $municipioA = $this->makeMunicipio('A');
        $municipioB = $this->makeMunicipio('B');
        DatoHistorico::create(['municipio_id' => $municipioA->id, 'variable_id' => $var->id, 'anio' => 2025, 'valor' => 10]);
        DatoHistorico::create(['municipio_id' => $municipioB->id, 'variable_id' => $var->id, 'anio' => 2025, 'valor' => 30]);
        DatoGeograficoHistorico::create([
            'unidad_geografica_id' => UnidadGeografica::where('slug', 'puebla')->value('id'),
            'variable_id' => $var->id,
            'anio' => 2025,
            'valor' => 24.5,
        ]);

        $result = $this->service->getChartData([
            'indicador_id' => $ind->id,
            'nivel_de_agregacion' => 'estatal',
            'anios' => [2025],
        ]);

        $this->assertSame('oficial_estatal', $result['origen_dato']);
        $this->assertSame(24.5, $result['series'][0]['data'][0][1]);
        $this->assertEquals([2025], $result['available_years']->all());
        $this->assertTrue($this->service->getStateAvailableIndicatorIds()->contains($ind->id));
    }

    public function test_state_year_is_available_only_when_all_visible_variables_have_official_values(): void
    {
        $this->seed(UnidadesGeograficasSeeder::class);
        $ind = $this->makeIndicador('Índice compuesto', ['tipo_dato' => 'indice']);
        $variableA = Variable::create([
            'indicador_id' => $ind->id,
            'nombre_amigable' => 'Componente A',
            'nombre_tecnico' => 'componente_a',
        ]);
        Variable::create([
            'indicador_id' => $ind->id,
            'nombre_amigable' => 'Componente B',
            'nombre_tecnico' => 'componente_b',
        ]);
        DatoGeograficoHistorico::create([
            'unidad_geografica_id' => UnidadGeografica::where('slug', 'puebla')->value('id'),
            'variable_id' => $variableA->id,
            'anio' => 2025,
            'valor' => 1,
        ]);

        $this->assertFalse($this->service->getStateAvailableIndicatorIds()->contains($ind->id));

        $this->postJson(route('api.data'), [
            'indicador_id' => $ind->id,
            'nivel_de_agregacion' => 'estatal',
        ])->assertStatus(422);
    }

    public function test_state_level_rejects_non_absolute_indicators(): void
    {
        $ind = $this->makeIndicador('Porcentaje estatal', ['tipo_dato' => 'porcentaje']);

        $response = $this->postJson(route('api.data'), [
            'indicador_id' => $ind->id,
            'nivel_de_agregacion' => 'estatal',
        ]);

        $response->assertStatus(422);
    }

    public function test_indicator_bank_exposes_state_as_territorial_level(): void
    {
        $response = $this->get(route('banco-indicadores.index'));

        $response->assertOk()
            ->assertSee('data-nivel="estatal"', false)
            ->assertSee('Estado de Puebla')
            ->assertDontSee('id="estatal-btn"', false);
    }
}
