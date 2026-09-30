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

    public function test_regional_profile_omits_scatter_until_aggregation_is_defined(): void
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
        $response->assertDontSee('"type":"scatter"', false);
        $response->assertDontSee('Medianas regionales', false);
        $response->assertDontSee('"type":"bar-horizontal"', false);
    }

    public function test_regional_profile_omits_average_indicators(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Región promedio';
        $macro->slug = 'region-promedio';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro promedio';
        $micro->slug = 'micro-promedio';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipioA = new Municipio();
        $municipioA->nombre = 'Municipio A';
        $municipioA->slug = 'promedio-a';
        $municipioA->microrregion_id = $micro->id;
        $municipioA->save();
        $municipioB = new Municipio();
        $municipioB->nombre = 'Municipio B';
        $municipioB->slug = 'promedio-b';
        $municipioB->microrregion_id = $micro->id;
        $municipioB->save();

        $dimension = Dimension::create(['nombre' => 'Social promedio', 'nombre_tecnico' => 'social_promedio']);
        $tematica = Tematica::create(['nombre' => 'Vivienda', 'nombre_tecnico' => 'vivienda', 'dimension_id' => $dimension->id]);
        $indicador = Indicador::create(['nombre_amigable' => 'Índice de hacinamiento', 'tematica_id' => $tematica->id]);
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Índice de hacinamiento',
            'nombre_tecnico' => 'indice_hacinamiento',
            'unidad_medida' => 'Promedio',
        ]);
        ConfiguracionFicha::create([
            'indicador_id' => $indicador->id,
            'seccion' => 'social_promedio',
            'orden' => 1,
            'tipo_visualizacion' => 'barras',
            'clase_grid' => 'col-12',
            'activo' => true,
        ]);

        DatoHistorico::create(['municipio_id' => $municipioA->id, 'variable_id' => $variable->id, 'anio' => 2020, 'valor' => 0.08]);
        DatoHistorico::create(['municipio_id' => $municipioB->id, 'variable_id' => $variable->id, 'anio' => 2020, 'valor' => 1.5]);

        $response = $this->get(route('regiones.macro.perfil', $macro->slug));

        $response->assertOk();
        $response->assertDontSee('"valor_actual":"0.79"', false);
        $response->assertDontSee('Índice de hacinamiento');

        $stateResponse = $this->get(route('regiones.estatal.perfil'));
        $stateResponse->assertOk();
        $stateResponse->assertDontSee('"valor_actual":"0.79"', false);
        $stateResponse->assertDontSee('Índice de hacinamiento');
    }

    public function test_per_capita_values_are_averaged_in_regional_profiles(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Región per cápita';
        $macro->slug = 'region-per-capita';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro per cápita';
        $micro->slug = 'micro-per-capita';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipioA = Municipio::create(['nombre' => 'Municipio A', 'slug' => 'per-capita-a', 'microrregion_id' => $micro->id]);
        $municipioB = Municipio::create(['nombre' => 'Municipio B', 'slug' => 'per-capita-b', 'microrregion_id' => $micro->id]);

        $dimension = Dimension::create(['nombre' => 'Gobierno per cápita', 'nombre_tecnico' => 'gobierno_per_capita']);
        $tematica = Tematica::create(['nombre' => 'Recursos', 'nombre_tecnico' => 'recursos', 'dimension_id' => $dimension->id]);
        $indicador = Indicador::create([
            'nombre_amigable' => 'Recursos per cápita',
            'tematica_id' => $tematica->id,
            'tipo_dato' => 'Absoluto',
        ]);
        $variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Recursos por habitante',
            'nombre_tecnico' => 'recursos_por_habitante',
            'unidad_medida' => 'Pesos por habitante',
            'visible_en_ficha' => true,
        ]);
        $configuracion = ConfiguracionFicha::create([
            'indicador_id' => $indicador->id,
            'seccion' => 'gobierno',
            'orden' => 1,
            'tipo_visualizacion' => 'kpi',
            'activo' => true,
        ]);
        $configuracion->variables()->attach($variable->id);

        DatoHistorico::create(['municipio_id' => $municipioA->id, 'variable_id' => $variable->id, 'anio' => 2025, 'valor' => 100]);
        DatoHistorico::create(['municipio_id' => $municipioB->id, 'variable_id' => $variable->id, 'anio' => 2025, 'valor' => 300]);

        $response = $this->get(route('regiones.macro.perfil', $macro->slug));

        $response->assertOk();
        $response->assertSee('"valor_actual":"$200.00"', false);
        $response->assertDontSee('"valor_actual":"$400.00"', false);
    }

    public function test_state_profile_route_is_available(): void
    {
        $response = $this->get(route('regiones.estatal.perfil'));

        $response->assertOk();
        $response->assertSee('Estado de Puebla');
        $response->assertSee('7');
        $response->assertSee('31');
        $response->assertSee('Microrregiones oficiales');
        $response->assertSee('Alcance territorial de esta ficha');
        $response->assertSee('https://planeader.puebla.gob.mx/regionalizacion', false);
        $response->assertSee('Fondo-hero.webp', false);
        $response->assertSee(route('regiones.estatal.pdf'), false);
        $response->assertSee(route('regiones.estatal.excel'), false);
    }

    public function test_non_desagregable_microrregion_redirect_explains_available_municipal_profile(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Macro redirección';
        $macro->slug = 'macro-redireccion';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro Puebla';
        $micro->slug = 'micro-puebla';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipio = Municipio::create([
            'nombre' => 'Puebla',
            'slug' => 'puebla',
            'microrregion_id' => $micro->id,
        ]);

        $response = $this->get(route('regiones.micro.perfil', $micro->slug));

        $response->assertRedirect(route('ficha-municipal.perfil', $municipio->slug));
        $response->assertSessionHas('info', "Esta microrregión no tiene información municipal desagregable en el sistema. Te hemos redirigido a la ficha de Puebla, donde se encuentra la información disponible.");
    }

    public function test_omnisearch_includes_state_profile_for_puebla(): void
    {
        $response = $this->getJson(route('api.omnisearch', ['q' => 'Puebla']));

        $response->assertOk()
            ->assertJsonFragment([
                'id' => 'estado_puebla',
                'text' => 'Puebla',
                'type' => 'Estado',
                'url' => route('regiones.estatal.perfil'),
            ]);
    }

    public function test_state_profile_omits_sex_ratio_but_keeps_population_total(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Región demográfica';
        $macro->slug = 'region-demografica';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro demográfica';
        $micro->slug = 'micro-demografica';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipioA = Municipio::create(['nombre' => 'Municipio A', 'slug' => 'ratio-a', 'microrregion_id' => $micro->id]);
        $municipioB = Municipio::create(['nombre' => 'Municipio B', 'slug' => 'ratio-b', 'microrregion_id' => $micro->id]);

        $dimension = Dimension::create(['nombre' => 'Demográfica ratio', 'nombre_tecnico' => 'demografica_ratio']);
        $tematica = Tematica::create(['nombre' => 'Sexo', 'nombre_tecnico' => 'sexo', 'dimension_id' => $dimension->id]);
        $poblacion = Indicador::create(['nombre_amigable' => 'Población total según sexo', 'tematica_id' => $tematica->id]);
        $mujeres = Variable::create(['indicador_id' => $poblacion->id, 'nombre_amigable' => 'Población mujeres', 'nombre_tecnico' => 'poblacion_mujeres', 'unidad_medida' => 'Habitantes']);
        $hombres = Variable::create(['indicador_id' => $poblacion->id, 'nombre_amigable' => 'Población hombres', 'nombre_tecnico' => 'poblacion_hombres', 'unidad_medida' => 'Habitantes']);
        ConfiguracionFicha::create([
            'indicador_id' => $poblacion->id,
            'seccion' => 'demografica_ratio',
            'orden' => 1,
            'tipo_visualizacion' => 'treemap',
            'clase_grid' => 'col-12',
            'activo' => true,
        ]);
        $relacion = Indicador::create(['nombre_amigable' => 'Relación hombres-mujeres', 'tematica_id' => $tematica->id]);
        $variableRelacion = Variable::create([
            'indicador_id' => $relacion->id,
            'nombre_amigable' => 'Relación hombres-mujeres',
            'nombre_tecnico' => 'relacion_hombres_mujeres',
            'unidad_medida' => 'Hombres por cada cien mujeres',
        ]);
        ConfiguracionFicha::create([
            'indicador_id' => $relacion->id,
            'seccion' => 'demografica_ratio',
            'orden' => 1,
            'tipo_visualizacion' => 'lineas',
            'clase_grid' => 'col-12',
            'activo' => true,
        ]);

        foreach ([[$municipioA, 60, 40, 66.6667], [$municipioB, 340, 260, 76.4706]] as [$municipio, $mujeresValor, $hombresValor, $ratio]) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $mujeres->id, 'anio' => 2020, 'valor' => $mujeresValor]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $hombres->id, 'anio' => 2020, 'valor' => $hombresValor]);
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $variableRelacion->id, 'anio' => 2020, 'valor' => $ratio]);
        }

        $response = $this->get(route('regiones.estatal.perfil'));

        $response->assertOk();
        $response->assertDontSee('"valor_actual":"75.00"', false);
        $response->assertDontSee('Relación hombres-mujeres');
        $response->assertSee('"type":"treemap"', false);
        $response->assertSee('"name":"Hombres"', false);
        $response->assertSee('"name":"Mujeres"', false);
    }
}
