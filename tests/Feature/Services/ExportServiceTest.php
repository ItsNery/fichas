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
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExportService::class);
    }

    public function test_export_chart_data_returns_download_response()
    {
        $chartData = ['titulo' => 'Test', 'headers' => ['A', 'B'], 'series' => [['name' => 'S1', 'data' => [['A', 1], ['B', 2]]]]];

        $response = $this->service->exportChartData($chartData);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
    }

    public function test_export_resumen_pdf_returns_download()
    {
        $dim = Dimension::create(['nombre' => 'Social', 'color' => '#fff', 'nombre_tecnico' => 'social']);
        $tem = Tematica::create(['nombre' => 'Educación', 'dimension_id' => $dim->id, 'nombre_tecnico' => 'edu']);
        $ind = Indicador::create(['nombre_amigable' => 'Analfabetismo', 'tematica_id' => $tem->id]);
        $var = Variable::create(['indicador_id' => $ind->id, 'nombre_amigable' => 'Tasa', 'nombre_tecnico' => 'tasa', 'es_kpi' => true]);

        $macro = new Macrorregion(); $macro->nombre = 'M'; $macro->save();
        $micro = new Microrregion(); $micro->nombre = 'm'; $micro->macrorregion_id = $macro->id; $micro->save();
        $mun = Municipio::create(['nombre' => 'Acajete', 'slug' => 'acajete', 'microrregion_id' => $micro->id]);

        DatoHistorico::create(['municipio_id' => $mun->id, 'variable_id' => $var->id, 'anio' => 2020, 'valor' => 5.2]);

        $response = $this->service->exportResumenPDF($mun);
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    public function test_export_complejos_aborts_when_not_complejo()
    {
        $dim = Dimension::create(['nombre' => 'D', 'nombre_tecnico' => 'd']);
        $tem = Tematica::create(['nombre' => 'T', 'dimension_id' => $dim->id, 'nombre_tecnico' => 't']);
        $ind = Indicador::create(['nombre_amigable' => 'Simple', 'tematica_id' => $tem->id]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->exportDatosComplejos($ind, 2020);
    }
}
