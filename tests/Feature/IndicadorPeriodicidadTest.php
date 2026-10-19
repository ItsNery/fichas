<?php

namespace Tests\Feature;

use App\Imports\IndicadorsImport;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Tematica;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorPeriodicidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_el_formulario_de_indicador_expone_la_periodicidad(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)
            ->get(route('admin.catalogos.indicadores.crear'))
            ->assertOk()
            ->assertSee('name="periodicidad"', false)
            ->assertSee('Anual (cada 1 año)')
            ->assertSee('Quinquenal (cada 5 años)');
    }

    public function test_guarda_la_periodicidad_al_crear_un_indicador(): void
    {
        $tematica = $this->crearTematica();

        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)
            ->post(route('admin.catalogos.indicadores.guardar'), [
                'nombre_amigable' => 'Poblacion',
                'tematica_id' => $tematica->id,
                'tipo_dato' => 'absoluto',
                'periodicidad' => 'Quinquenal',
            ])
            ->assertRedirect();

        $this->assertEquals('Quinquenal', Indicador::first()->periodicidad);
    }

    public function test_rechaza_una_periodicidad_no_valida(): void
    {
        $tematica = $this->crearTematica();

        $usuario = User::factory()->create();
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)
            ->post(route('admin.catalogos.indicadores.guardar'), [
                'nombre_amigable' => 'Poblacion',
                'tematica_id' => $tematica->id,
                'tipo_dato' => 'absoluto',
                'periodicidad' => 'Decenal',
            ])
            ->assertSessionHasErrors('periodicidad');
    }

    public function test_la_importacion_de_indicadores_toma_la_periodicidad(): void
    {
        $this->crearTematica();

        $importador = new IndicadorsImport();

        $modelo = $importador->model([
            'tematica_tecnico' => 'poblacion',
            'nombre_amigable' => 'Poblacion',
            'nombre_tecnico' => 'poblacion_total',
            'tipo_dato' => 'absoluto',
            'periodicidad' => 'bienal',
        ]);

        $this->assertNotNull($modelo);
        $this->assertEquals('Bienal', $modelo->periodicidad);
    }

    public function test_la_importacion_actualiza_la_periodicidad_de_indicadores_existentes(): void
    {
        $this->crearTematica();

        $importador = new IndicadorsImport();

        $fila = [
            'tematica_tecnico' => 'poblacion',
            'nombre_amigable' => 'Poblacion',
            'nombre_tecnico' => 'poblacion_total',
            'tipo_dato' => 'absoluto',
            'periodicidad' => 'Anual',
        ];

        $importador->model($fila);
        $fila['periodicidad'] = 'Quinquenal';
        $importador->model($fila);

        $this->assertEquals('Quinquenal', Indicador::first()->periodicidad);
    }

    public function test_la_importacion_descarta_una_periodicidad_no_valida(): void
    {
        $this->crearTematica();

        $modelo = (new IndicadorsImport())->model([
            'tematica_tecnico' => 'poblacion',
            'nombre_amigable' => 'Poblacion',
            'nombre_tecnico' => 'poblacion_total',
            'tipo_dato' => 'absoluto',
            'periodicidad' => 'Decenal',
        ]);

        $this->assertNotNull($modelo);
        $this->assertNull($modelo->periodicidad);
    }

    private function crearTematica(): Tematica
    {
        $dimension = Dimension::create(['nombre' => 'Dimension', 'nombre_tecnico' => 'dimension']);

        return Tematica::create([
            'nombre' => 'Poblacion',
            'nombre_tecnico' => 'poblacion',
            'dimension_id' => $dimension->id,
        ]);
    }
}
