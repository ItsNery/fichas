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
use App\Services\RankingService;
use App\Services\FichaDataStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RankingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RankingService::class);
    }

    private function makeIndicador(): Indicador
    {
        $dim = Dimension::create(['nombre' => 'D', 'nombre_tecnico' => 'd']);
        $tem = Tematica::create(['nombre' => 'T', 'nombre_tecnico' => 't', 'dimension_id' => $dim->id]);
        return Indicador::create(['nombre_amigable' => 'Test', 'tematica_id' => $tem->id]);
    }

    private function makeMunicipio(string $nombre, ?int $microId = null): Municipio
    {
        if (!$microId) {
            $macro = new Macrorregion(); $macro->nombre = 'M'; $macro->save();
            $micro = new Microrregion(); $micro->nombre = 'm'; $micro->macrorregion_id = $macro->id; $micro->save();
            $microId = $micro->id;
        }
        return Municipio::create(['nombre' => $nombre, 'slug' => strtolower($nombre), 'microrregion_id' => $microId]);
    }

    public function test_get_municipality_ranking()
    {
        $m1 = $this->makeMunicipio('A'); $m2 = $this->makeMunicipio('B'); $m3 = $this->makeMunicipio('C');
        $var = Variable::create(['indicador_id' => $this->makeIndicador()->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1']);
        DatoHistorico::create(['municipio_id' => $m1->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 50]);
        DatoHistorico::create(['municipio_id' => $m2->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 100]);
        DatoHistorico::create(['municipio_id' => $m3->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 30]);

        $r = $this->service->getMunicipalityRanking([$var->id], $m1->id, 2020);
        $this->assertEquals(2, $r['posicion']);
        $this->assertEquals(3, $r['total_municipios']);
    }

    public function test_get_municipality_ranking_returns_nd_when_no_data()
    {
        $m = $this->makeMunicipio('A');
        $r = $this->service->getMunicipalityRanking([999], $m->id, 2020);
        $this->assertEquals('N/D', $r['posicion']);
        $this->assertEquals(0, $r['total_municipios']);
    }

    public function test_get_state_average()
    {
        $m1 = $this->makeMunicipio('A'); $m2 = $this->makeMunicipio('B');
        $var = Variable::create(['indicador_id' => $this->makeIndicador()->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1']);
        DatoHistorico::create(['municipio_id' => $m1->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 40]);
        DatoHistorico::create(['municipio_id' => $m2->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 60]);
        $this->assertEquals(50, $this->service->getStateAverage([$var->id], 2020, 'avg'));
    }

    public function test_get_state_sum()
    {
        $m1 = $this->makeMunicipio('A'); $m2 = $this->makeMunicipio('B');
        $var = Variable::create(['indicador_id' => $this->makeIndicador()->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1']);
        DatoHistorico::create(['municipio_id' => $m1->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 40]);
        DatoHistorico::create(['municipio_id' => $m2->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 60]);
        $this->assertEquals(100, $this->service->getStateAverage([$var->id], 2020, 'sum'));
    }

    public function test_get_macrorregional_average()
    {
        $macro = new Macrorregion(); $macro->nombre = 'R1'; $macro->save();
        $micro = new Microrregion(); $micro->nombre = 'M1'; $micro->macrorregion_id = $macro->id; $micro->save();
        $m1 = Municipio::create(['nombre' => 'A', 'slug' => 'a', 'microrregion_id' => $micro->id]);
        $m2 = Municipio::create(['nombre' => 'B', 'slug' => 'b', 'microrregion_id' => $micro->id]);
        $var = Variable::create(['indicador_id' => $this->makeIndicador()->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1']);
        DatoHistorico::create(['municipio_id' => $m1->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 30]);
        DatoHistorico::create(['municipio_id' => $m2->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 50]);
        $this->assertEquals(40, $this->service->getMacrorregionalAverage([$var->id], $m1, 2020, 'avg'));
    }

    public function test_get_macrorregional_average_returns_zero_for_no_region()
    {
        $this->assertEquals(0, $this->service->getMacrorregionalAverage([1], $this->makeMunicipio('A'), 2020, 'avg'));
    }

    public function test_in_memory_rankings_and_averages_match_database_results()
    {
        $macro = new Macrorregion(); $macro->nombre = 'R1'; $macro->save();
        $micro = new Microrregion(); $micro->nombre = 'M1'; $micro->macrorregion_id = $macro->id; $micro->save();
        $m1 = Municipio::create(['nombre' => 'A', 'slug' => 'a', 'microrregion_id' => $micro->id]);
        $m2 = Municipio::create(['nombre' => 'B', 'slug' => 'b', 'microrregion_id' => $micro->id]);
        $var = Variable::create(['indicador_id' => $this->makeIndicador()->id, 'nombre_amigable' => 'V1', 'nombre_tecnico' => 'v1']);
        DatoHistorico::create(['municipio_id' => $m1->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 40]);
        DatoHistorico::create(['municipio_id' => $m2->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 60]);
        $store = new FichaDataStore($m1, [$var->id]);

        $variableIds = collect([$var->id]);
        $ranking = $this->service->getMunicipalityRankingInMemory($store, $variableIds, $m1->id, '2020');

        $this->assertEquals(2, $ranking['posicion']);
        $this->assertEquals(2, $ranking['total_municipios']);
        $this->assertEquals(50, $this->service->getStateAverageInMemory($store, $variableIds, '2020', 'avg'));
        $this->assertEquals(100, $this->service->getStateAverageInMemory($store, $variableIds, '2020', 'sum'));
        $this->assertEquals(50, $this->service->getMacrorregionalAverageInMemory($store, $variableIds, [$m1->id, $m2->id], '2020', 'avg'));
    }
}
