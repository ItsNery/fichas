<?php

namespace Tests\Feature;

use App\Models\CatMotivoSinDato;
use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\Variable;
use App\Services\ExportV3Service;
use App\Services\MunicipalityMapImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PanoramaPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_panorama_pdf_embeds_the_map_without_requesting_the_application(): void
    {
        $macrorregion = new Macrorregion;
        $macrorregion->nombre = 'Centro';
        $macrorregion->save();
        $microrregion = new Microrregion;
        $microrregion->macrorregion_id = $macrorregion->id;
        $microrregion->nombre = 'Acatzingo';
        $microrregion->save();
        $municipio = Municipio::create([
            'nombre' => 'Acatzingo',
            'slug' => 'acatzingo',
            'cvegeo' => '21004',
            'microrregion_id' => $microrregion->id,
        ]);
        $dimension = Dimension::create([
            'nombre' => 'Social',
            'nombre_tecnico' => 'social',
            'visible_en_ficha' => true,
        ]);
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Población',
            'nombre_tecnico' => 'poblacion',
            'visible_en_ficha' => true,
        ]);
        $indicador = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Población total',
            'nombre_tecnico' => 'poblacion_total',
            'visible_en_ficha' => true,
        ]);
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Población total',
            'nombre_tecnico' => 'poblacion_total',
            'visible_en_ficha' => true,
        ]);
        DatoHistorico::create([
            'municipio_id' => $municipio->id,
            'variable_id' => $variable->id,
            'anio' => 2025,
            'valor' => 1234,
        ]);
        $motivo = CatMotivoSinDato::create([
            'codigo' => 'NA',
            'nombre' => 'No aplica',
        ]);
        $indicadorSinDato = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Tasa sin dato',
            'nombre_tecnico' => 'tasa_sin_dato',
            'tipo_grafico_default' => 'Lineal',
            'visible_en_ficha' => true,
        ]);
        $variableSinDato = Variable::create([
            'indicador_id' => $indicadorSinDato->id,
            'nombre_amigable' => 'Tasa sin dato',
            'nombre_tecnico' => 'tasa_sin_dato',
            'visible_en_ficha' => true,
        ]);
        $datoSinValor = DatoHistorico::create([
            'municipio_id' => $municipio->id,
            'variable_id' => $variableSinDato->id,
            'anio' => 2025,
            'valor' => null,
            'motivo_sin_dato_id' => $motivo->id,
        ]);

        $this->assertSame('No aplica', $datoSinValor->valor_display);

        $map = Mockery::mock(MunicipalityMapImageService::class);
        $map->shouldReceive('render')->once()->with('21004')->andReturn('png-contents');
        $this->app->instance(MunicipalityMapImageService::class, $map);

        $mapDataUri = 'data:image/png;base64,'.base64_encode('png-contents');
        $mapUrl = route('ficha-municipal.panorama.map', $municipio);
        $exporter = Mockery::mock(ExportV3Service::class);
        $exporter->shouldReceive('exportPanoramaPDF')
            ->once()
            ->withArgs(function (string $html, string $fileName) use ($mapDataUri, $mapUrl): bool {
                return $fileName === 'panorama-acatzingo.pdf'
                    && str_contains($html, $mapDataUri)
                    && str_contains($html, '"display":"No aplica"')
                    && ! str_contains($html, $mapUrl);
            })
            ->andReturn(response('pdf'));
        $this->app->instance(ExportV3Service::class, $exporter);

        $this->get(route('ficha-municipal.panorama.pdf', $municipio))->assertOk();
    }
}
