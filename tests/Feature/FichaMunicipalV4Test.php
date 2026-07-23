<?php

namespace Tests\Feature;

use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\Variable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FichaMunicipalV4Test extends TestCase
{
    use RefreshDatabase;

    public function test_v4_shell_only_lists_public_sections(): void
    {
        [$municipio, $dimension] = $this->createMunicipio();
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Temática',
            'nombre_tecnico' => 'tematica',
        ]);
        $public = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Indicador público',
            'visible_en_ficha' => true,
        ]);
        $hidden = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Indicador oculto',
            'visible_en_ficha' => false,
        ]);
        ConfiguracionFicha::create([
            'indicador_id' => $public->id,
            'seccion' => 'social',
            'tipo_visualizacion' => 'kpi',
            'activo' => true,
        ]);
        ConfiguracionFicha::create([
            'indicador_id' => $hidden->id,
            'seccion' => 'social',
            'tipo_visualizacion' => 'kpi',
            'activo' => true,
        ]);

        $response = $this->get(route('ficha-municipal.v4', $municipio->slug));

        $response->assertOk()
            ->assertSee('Ficha municipal · Nueva versión')
            ->assertSee($dimension->nombre)
            ->assertSee('1 indicadores')
            ->assertDontSee('Indicador oculto');
    }

    public function test_v4_section_returns_a_compact_indicator_contract(): void
    {
        [$municipio, $dimension] = $this->createMunicipio();
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Temática',
            'nombre_tecnico' => 'tematica',
        ]);
        $indicador = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Población',
            'fuente' => 'Fuente de prueba',
            'visible_en_ficha' => true,
        ]);
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_tecnico' => 'poblacion',
            'nombre_amigable' => 'Población total',
            'unidad_medida' => 'Habitantes',
            'visible_en_ficha' => true,
        ]);
        ConfiguracionFicha::create([
            'indicador_id' => $indicador->id,
            'seccion' => 'social',
            'tipo_visualizacion' => 'kpi',
            'activo' => true,
        ]);
        DatoHistorico::create([
            'municipio_id' => $municipio->id,
            'variable_id' => $variable->id,
            'anio' => 2024,
            'valor' => 12345,
        ]);

        $response = $this->getJson(route('ficha-municipal.v4.section', [
            'municipio' => $municipio->slug,
            'dimension' => Str::slug($dimension->nombre),
        ]));

        $response->assertOk()
            ->assertJsonPath('section', Str::slug($dimension->nombre))
            ->assertJsonPath('items.0.title', 'Población')
            ->assertJsonPath('items.0.year', 2024)
            ->assertJsonPath('items.0.unit', 'Habitantes')
            ->assertJsonPath('items.0.source', 'Fuente de prueba')
            ->assertJsonPath('items.0.quality.status', 'provisional')
            ->assertJsonPath('items.0.quality.year', 2024)
            ->assertJsonPath('items.0.quality.coverage', 1)
            ->assertJsonPath('items.0.quality.expected', 1);
    }

    public function test_v4_section_returns_population_pyramid_data(): void
    {
        [$municipio, $dimension] = $this->createMunicipio();
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Demografía',
            'nombre_tecnico' => 'demografia',
        ]);
        $indicador = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Población por grupos de edad según sexo',
            'visible_en_ficha' => true,
        ]);
        $hombres = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_tecnico' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_hombres_de_85_a_89_anos',
            'nombre_amigable' => 'Hombres de 85 a 89 años',
            'unidad_medida' => 'Habitantes',
            'visible_en_ficha' => true,
        ]);
        $mujeres = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_tecnico' => 'poblacion_por_grupos_de_edad_segun_sexo_poblacion_de_mujeres_de_85_a_89_anos',
            'nombre_amigable' => 'Mujeres de 85 a 89 años',
            'unidad_medida' => 'Habitantes',
            'visible_en_ficha' => true,
        ]);
        $config = ConfiguracionFicha::create([
            'indicador_id' => $indicador->id,
            'seccion' => 'demografia',
            'tipo_visualizacion' => 'piramide',
            'activo' => true,
        ]);
        $config->variables()->attach([$hombres->id, $mujeres->id]);
        DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $hombres->id, 'anio' => 2024, 'valor' => 10]);
        DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $mujeres->id, 'anio' => 2024, 'valor' => 15]);

        $response = $this->getJson(route('ficha-municipal.v4.section', [
            'municipio' => $municipio->slug,
            'dimension' => Str::slug($dimension->nombre),
        ]));

        $response->assertOk()
            ->assertJsonPath('items.0.data.tipo_grafico', 'piramide')
            ->assertJsonPath('items.0.data.series.0.data.3', -10)
            ->assertJsonPath('items.0.data.series.1.data.3', 15);
    }

    private function createMunicipio(): array
    {
        $macro = Macrorregion::forceCreate(['nombre' => 'Macrorregión']);
        $micro = Microrregion::forceCreate([
            'macrorregion_id' => $macro->id,
            'nombre' => 'Microrregión',
        ]);
        $municipio = Municipio::create([
            'microrregion_id' => $micro->id,
            'nombre' => 'Municipio de prueba',
            'slug' => 'municipio-de-prueba',
        ]);
        $dimension = Dimension::create([
            'nombre' => 'Dimensión social',
            'nombre_tecnico' => 'dimension_social',
            'visible_en_ficha' => true,
        ]);

        return [$municipio, $dimension];
    }
}
