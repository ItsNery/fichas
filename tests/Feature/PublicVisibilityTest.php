<?php

namespace Tests\Feature;

use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\Variable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVisibilityTest extends TestCase
{
    use RefreshDatabase;

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
    }
}
