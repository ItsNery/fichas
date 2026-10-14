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
            ->assertSee(route('regiones.micro.perfil', $micro), false);
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
