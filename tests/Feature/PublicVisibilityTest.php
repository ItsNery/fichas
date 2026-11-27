<?php

namespace Tests\Feature;

use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\Variable;
use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Exports\IndicadoresExport;
use App\Exports\VariablesExport;
use App\Exports\DatosHistoricosExport;
use App\Services\PublicVisibilityVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return ['--path' => ['tests/fixtures/migrations', 'database/migrations']];
    }

    public function test_public_api_and_exports_exclude_non_public_or_unpublished_indicators(): void
    {
        $dimension = Dimension::create(['nombre' => 'Dimensión', 'nombre_tecnico' => 'dimension', 'visible_en_ficha' => true]);
        $tematica = Tematica::create(['dimension_id' => $dimension->id, 'nombre' => 'Temática', 'nombre_tecnico' => 'tematica', 'visible_en_ficha' => true]);
        $indicators = collect([
            ['publica', 'publicado'], ['uso_interno', 'publicado'], ['confidencial', 'publicado'],
            ['publica', 'borrador'], ['publica', 'en_revision'], ['publica', 'deprecado'],
        ])->map(function ($state, $index) use ($tematica) {
            return Indicador::create([
                'tematica_id' => $tematica->id, 'nombre_amigable' => "Indicador {$index}",
                'visible_en_ficha' => true, 'clasificacion' => $state[0], 'estado_publicacion' => $state[1],
            ]);
        });
        $public = $indicators->first();
        $this->getJson("/api/v1/indicadores/{$public->id}")->assertOk();
        foreach ($indicators->skip(1) as $indicator) {
            $this->getJson("/api/v1/indicadores/{$indicator->id}")->assertNotFound();
            $this->getJson("/banco-indicadores/api/mapa-datos/{$indicator->id}/2020")->assertNotFound();
            $this->getJson("/banco-indicadores/api/indicador-anios/{$indicator->id}")->assertNotFound();
            $payload = ['indicador_id' => $indicator->id, 'nivel_de_agregacion' => 'estatal'];
            $this->postJson('/api/v1/consulta', $payload)->assertNotFound();
            $this->postJson('/api/v1/data', $payload)->assertNotFound();
            $this->getJson('/api/v1/data?'.http_build_query($payload))->assertNotFound();
        }
        $this->assertSame([$public->id], collect($this->getJson('/api/v1/indicadores')->assertOk()->json('data'))->pluck('id')->all());
        $this->assertSame([$public->id], collect($this->getJson('/api/v1/metadata')->assertOk()->json('data.indicadores'))->pluck('id')->all());
        $this->assertSame([$public->id], (new IndicadoresExport(true))->query()->pluck('id')->all());
        $this->assertCount(6, (new IndicadoresExport())->query()->get());

        foreach ($indicators as $index => $indicator) {
            Variable::create(['indicador_id' => $indicator->id, 'nombre_tecnico' => "variable_{$index}", 'nombre_amigable' => "Variable {$index}", 'visible_en_ficha' => true]);
        }
        $this->assertSame([$public->id], (new VariablesExport(true))->query()->pluck('indicador_id')->all());
        $this->assertCount(6, (new VariablesExport())->query()->get());
        $macro = new Macrorregion();
        $macro->nombre = 'Región';
        $macro->slug = 'region';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro';
        $micro->slug = 'micro';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        $municipio = Municipio::create(['nombre' => 'Municipio', 'slug' => 'municipio', 'microrregion_id' => $micro->id]);
        foreach (Variable::all() as $variable) {
            DatoHistorico::create(['municipio_id' => $municipio->id, 'variable_id' => $variable->id, 'anio' => 2020, 'valor' => 100]);
        }
        $this->assertSame([$public->id], (new DatosHistoricosExport($dimension, 'todos'))->query()->get()->map(fn ($dato) => $dato->variable->indicador_id)->all());
        $this->assertCount(6, (new DatosHistoricosExport())->query()->get());
        $this->postJson('/api/v1/consulta', ['indicador_id' => 999999, 'nivel_de_agregacion' => 'estatal'])->assertNotFound();
        $this->get('/banco-indicadores')->assertOk()->assertSee('Indicador 0')->assertDontSee('Indicador 1');
        $this->getJson('/api/omnisearch?q=Indicador')->assertOk()->assertDontSee('Indicador 1');

        $version = app(PublicVisibilityVersion::class)->fingerprint();
        $indicators[1]->update(['clasificacion' => 'publica']);
        $this->assertNotSame($version, app(PublicVisibilityVersion::class)->fingerprint());
        $indicators[1]->update(['clasificacion' => 'uso_interno']);

        $config = ConfiguracionFicha::create([
            'indicador_id' => $public->id, 'seccion' => 'general',
            'tipo_visualizacion' => 'kpi', 'activo' => true,
        ]);
        $this->assertTrue(ConfiguracionFicha::visibleEnPerfilPublico()->whereKey($config->id)->exists());
        $config->variables()->attach(Variable::where('indicador_id', $indicators[1]->id)->firstOrFail());
        $this->assertFalse(ConfiguracionFicha::visibleEnPerfilPublico()->whereKey($config->id)->exists());
        $this->getJson("/municipios/api/grafico-datos/{$municipio->slug}/{$config->id}/2020")->assertNotFound();
    }

    public function test_metadata_only_exposes_published_hierarchy_and_variables(): void
    {
        $dimension = Dimension::create([
            'nombre' => 'Dimensión pública',
            'nombre_tecnico' => 'dimension_publica',
            'visible_en_ficha' => true,
        ]);
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Temática pública',
            'nombre_tecnico' => 'tematica_publica',
            'visible_en_ficha' => true,
        ]);
        $publicIndicator = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Indicador público',
            'visible_en_ficha' => true,
        ]);
        $hiddenIndicator = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Indicador oculto',
            'visible_en_ficha' => false,
        ]);
        $publicVariable = Variable::create([
            'indicador_id' => $publicIndicator->id,
            'nombre_tecnico' => 'variable_publica',
            'nombre_amigable' => 'Variable pública',
            'visible_en_ficha' => true,
        ]);
        $hiddenParentVariable = Variable::create([
            'indicador_id' => $hiddenIndicator->id,
            'nombre_tecnico' => 'variable_de_indicador_oculto',
            'nombre_amigable' => 'Variable de indicador oculto',
            'visible_en_ficha' => true,
        ]);

        $response = $this->getJson('/api/v1/metadata')->assertOk();
        $metadata = $response->json('data');

        $this->assertContains($publicIndicator->id, collect($metadata['indicadores'])->pluck('id')->all());
        $this->assertNotContains($hiddenIndicator->id, collect($metadata['indicadores'])->pluck('id')->all());
        $this->assertContains($publicVariable->id, collect($metadata['variables'])->pluck('id')->all());
        $this->assertNotContains($hiddenParentVariable->id, collect($metadata['variables'])->pluck('id')->all());
    }

    public function test_hidden_hierarchy_is_not_available_from_public_indicator_endpoint(): void
    {
        $dimension = Dimension::create([
            'nombre' => 'Dimensión oculta',
            'nombre_tecnico' => 'dimension_oculta',
            'visible_en_ficha' => false,
        ]);
        $tematica = Tematica::create([
            'dimension_id' => $dimension->id,
            'nombre' => 'Temática oculta',
            'nombre_tecnico' => 'tematica_oculta',
            'visible_en_ficha' => true,
        ]);
        $indicator = Indicador::create([
            'tematica_id' => $tematica->id,
            'nombre_amigable' => 'Indicador bajo dimensión oculta',
            'visible_en_ficha' => true,
        ]);

        $this->getJson("/api/v1/indicadores/{$indicator->id}")->assertNotFound();
    }

    public function test_public_debug_endpoints_are_not_available(): void
    {
        $this->getJson('/api/v1/debug')->assertNotFound();
        $this->getJson('/api/v1/debug-controller')->assertNotFound();
        $this->get('/admin/datos/exportar')->assertRedirect('/login');
        $this->get('/admin/roles')->assertRedirect('/login');
        $this->get('/admin/diccionario')->assertRedirect('/login');
    }
}
