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
use Tests\TestCase;

class RegionProfilePyramidTest extends TestCase
{
    use RefreshDatabase;

    public function test_regional_profile_preserves_population_pyramid_format(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Región de prueba';
        $macro->slug = 'region-prueba';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro de prueba';
        $micro->slug = 'micro-prueba';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipioA = Municipio::create(['nombre' => 'Municipio A', 'slug' => 'municipio-a', 'microrregion_id' => $micro->id]);
        $municipioB = Municipio::create(['nombre' => 'Municipio B', 'slug' => 'municipio-b', 'microrregion_id' => $micro->id]);

        $dimension = Dimension::create(['nombre' => 'Demográfica y Social', 'nombre_tecnico' => 'social']);
        $tematica = Tematica::create(['nombre' => 'Población', 'nombre_tecnico' => 'poblacion', 'dimension_id' => $dimension->id]);
        $indicador = Indicador::create(['nombre_amigable' => 'Población por grupos de edad según sexo', 'tematica_id' => $tematica->id]);
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
        $configuracion = ConfiguracionFicha::create([
            'indicador_id' => $indicador->id,
            'seccion' => 'social',
            'orden' => 1,
            'tipo_visualizacion' => 'piramide',
            'clase_grid' => 'col-12',
            'activo' => true,
        ]);
        $configuracion->variables()->attach([$hombres->id, $mujeres->id]);

        foreach ([$municipioA, $municipioB] as $index => $municipio) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $hombres->id, 'anio' => 2020, 'valor' => 10 + $index]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $mujeres->id, 'anio' => 2020, 'valor' => 12 + $index]);
        }

        $response = $this->get(route('regiones.macro.perfil', $macro->slug));

        $response->assertOk();
        $response->assertSee('"tipo_grafico":"piramide"', false);
        $response->assertDontSee('"type":"bar-horizontal"', false);
    }

    public function test_regional_profile_preserves_scatter_and_state_context(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Región scatter';
        $macro->slug = 'region-scatter';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro scatter';
        $micro->slug = 'micro-scatter';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipioA = Municipio::create(['nombre' => 'Municipio A', 'slug' => 'scatter-a', 'microrregion_id' => $micro->id]);
        $municipioB = Municipio::create(['nombre' => 'Municipio B', 'slug' => 'scatter-b', 'microrregion_id' => $micro->id]);

        $otraMacro = new Macrorregion();
        $otraMacro->nombre = 'Otra región';
        $otraMacro->slug = 'otra-region';
        $otraMacro->save();
        $otraMicro = new Microrregion();
        $otraMicro->nombre = 'Otra micro';
        $otraMicro->slug = 'otra-micro';
        $otraMicro->macrorregion_id = $otraMacro->id;
        $otraMicro->save();
        $municipioExterno = Municipio::create(['nombre' => 'Municipio externo', 'slug' => 'scatter-externo', 'microrregion_id' => $otraMicro->id]);

        $dimension = Dimension::create(['nombre' => 'Económico', 'nombre_tecnico' => 'economico']);
        $tematica = Tematica::create(['nombre' => 'Cruces', 'nombre_tecnico' => 'cruces', 'dimension_id' => $dimension->id]);
        $indicadorX = Indicador::create(['nombre_amigable' => 'Indicador X', 'tematica_id' => $tematica->id]);
        $indicadorY = Indicador::create(['nombre_amigable' => 'Indicador Y', 'tematica_id' => $tematica->id]);
        $variableX = Variable::create(['indicador_id' => $indicadorX->id, 'nombre_amigable' => 'Variable X', 'nombre_tecnico' => 'scatter_x', 'unidad_medida' => 'Porcentaje']);
        $variableY = Variable::create(['indicador_id' => $indicadorY->id, 'nombre_amigable' => 'Variable Y', 'nombre_tecnico' => 'scatter_y', 'unidad_medida' => 'Porcentaje']);
        $configuracion = ConfiguracionFicha::create([
            'indicador_id' => $indicadorX->id,
            'seccion' => 'economico',
            'orden' => 1,
            'tipo_visualizacion' => 'scatter',
            'clase_grid' => 'col-12',
            'activo' => true,
        ]);
        $configuracion->variables()->attach([$variableX->id, $variableY->id]);

        foreach ([[$municipioA, 10, 20], [$municipioB, 15, 25], [$municipioExterno, 30, 40]] as [$municipio, $x, $y]) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $variableX->id, 'anio' => 2020, 'valor' => $x]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $variableY->id, 'anio' => 2020, 'valor' => $y]);
        }

        $response = $this->get(route('regiones.macro.perfil', $macro->slug));

        $response->assertOk();
        $response->assertSee('"type":"scatter"', false);
        $response->assertSee('Medianas regionales', false);
        $response->assertSee('Municipio externo', false);
        $response->assertDontSee('"type":"bar-horizontal"', false);
    }
}
