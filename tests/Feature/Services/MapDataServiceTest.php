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
use App\Services\MapDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private MapDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MapDataService::class);
    }

    private function makeIndicador(): Indicador
    {
        $dim = Dimension::create(['nombre' => 'D', 'nombre_tecnico' => 'd']);
        $tem = Tematica::create(['nombre' => 'T', 'nombre_tecnico' => 't', 'dimension_id' => $dim->id]);
        return Indicador::create(['nombre_amigable' => 'Test', 'tematica_id' => $tem->id]);
    }

    private function makeMunicipio(string $nombre, string $cvegeo, string $slug): Municipio
    {
        $macro = new Macrorregion(); $macro->nombre = 'M'; $macro->save();
        $micro = new Microrregion(); $micro->nombre = 'm'; $micro->macrorregion_id = $macro->id; $micro->save();
        return Municipio::create(['nombre' => $nombre, 'cvegeo' => $cvegeo, 'slug' => $slug, 'microrregion_id' => $micro->id]);
    }

    public function test_get_map_data_returns_cvegeo_keyed_array()
    {
        $m1 = $this->makeMunicipio('Acajete', '001', 'acajete');
        $m2 = $this->makeMunicipio('Acateno', '002', 'acateno');
        $ind = $this->makeIndicador();
        $var = Variable::create(['indicador_id' => $ind->id, 'nombre_amigable' => 'Total', 'nombre_tecnico' => 'total']);

        DatoHistorico::create(['municipio_id' => $m1->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 100]);
        DatoHistorico::create(['municipio_id' => $m2->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 200]);

        $result = $this->service->getMapData($ind, 2020);

        $this->assertCount(2, $result);
        $this->assertEquals(100, $result['001']);
        $this->assertEquals(200, $result['002']);
    }

    public function test_get_map_data_prioritizes_total_variable()
    {
        $m = $this->makeMunicipio('Acajete', '001', 'acajete');
        $ind = $this->makeIndicador();
        $t = Variable::create(['indicador_id' => $ind->id, 'nombre_amigable' => 'Total', 'nombre_tecnico' => 'total']);
        $o = Variable::create(['indicador_id' => $ind->id, 'nombre_amigable' => 'Otro', 'nombre_tecnico' => 'otro']);

        DatoHistorico::create(['municipio_id' => $m->id, 'variable_id' => $t->id, 'anio' => 2020, 'valor' => 500]);
        DatoHistorico::create(['municipio_id' => $m->id, 'variable_id' => $o->id, 'anio' => 2020, 'valor' => 999]);

        $this->assertEquals(500, $this->service->getMapData($ind, 2020)['001']);
    }

    public function test_get_map_data_returns_empty_array_for_no_data()
    {
        $this->assertEmpty($this->service->getMapData($this->makeIndicador(), 2020));
    }
}
