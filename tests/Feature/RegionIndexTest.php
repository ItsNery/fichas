<?php

namespace Tests\Feature;

use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_regional_index_lists_state_macro_and_micro_profiles(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Sierra Norte';
        $macro->slug = 'sierra-norte';
        $macro->save();

        $micro = new Microrregion();
        $micro->nombre = 'Zacatlán';
        $micro->slug = 'zacatlan';
        $micro->macrorregion_id = $macro->id;
        $micro->save();
        Municipio::create([
            'nombre' => 'Zacatlán',
            'slug' => 'zacatlan',
            'microrregion_id' => $micro->id,
        ]);

        $this->get(route('regiones.index'))
            ->assertOk()
            ->assertSee('Perfil estatal')
            ->assertSee('Sierra Norte')
            ->assertSee('Zacatlán')
            ->assertSee(route('regiones.macro.perfil', $macro), false)
            ->assertSee(route('municipios.show', $micro->municipios()->first()), false)
            ->assertDontSee(route('regiones.micro.perfil', $micro), false);
    }

    public function test_regional_index_and_profiles_redirect_partitioned_microrregions_to_the_municipal_profile(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Macro';
        $macro->slug = 'macro';
        $macro->save();
        $microA = new Microrregion();
        $microA->nombre = 'Municipio dividido';
        $microA->slug = 'municipio-dividido';
        $microA->macrorregion_id = $macro->id;
        $microA->save();
        $microB = new Microrregion();
        $microB->nombre = 'Municipio dividido';
        $microB->slug = 'municipio-dividido-2';
        $microB->macrorregion_id = $macro->id;
        $microB->save();
        $municipio = Municipio::create([
            'nombre' => 'Municipio dividido',
            'slug' => 'municipio-dividido',
            'microrregion_id' => $microA->id,
        ]);
        Municipio::create(['nombre' => 'Otro A', 'slug' => 'otro-a', 'microrregion_id' => $microA->id]);
        Municipio::create(['nombre' => 'Otro B', 'slug' => 'otro-b', 'microrregion_id' => $microB->id]);

        $this->get(route('regiones.index'))
            ->assertOk()
            ->assertSee(route('municipios.show', $municipio), false)
            ->assertDontSee(route('regiones.micro.perfil', $microA), false)
            ->assertDontSee(route('regiones.micro.perfil', $microB), false);

        $this->get(route('regiones.micro.perfil', $microA))
            ->assertRedirect(route('municipios.show', $municipio));
        $this->get(route('regiones.micro.perfil', $microB))
            ->assertRedirect(route('municipios.show', $municipio));
    }

    public function test_regional_index_does_not_fail_when_a_region_has_no_slug(): void
    {
        $macro = new Macrorregion();
        $macro->nombre = 'Región sin slug';
        $macro->slug = 'region-sin-slug';
        $macro->save();

        $micro = new Microrregion();
        $micro->nombre = 'Microrregión sin slug';
        $micro->macrorregion_id = $macro->id;
        $micro->save();

        $this->get(route('regiones.index'))
            ->assertOk()
            ->assertSee('Microrregión sin slug')
            ->assertSee('Perfil no disponible temporalmente.');
    }
}
