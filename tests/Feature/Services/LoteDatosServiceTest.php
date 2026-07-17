<?php

namespace Tests\Feature\Services;

use App\Models\DatoHistorico;
use App\Models\DatoIndicadorComplejo;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\LoteDatoHistorico;
use App\Models\LoteDatos;
use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use App\Models\Tematica;
use App\Models\User;
use App\Models\Variable;
use App\Services\LoteDatosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LoteDatosServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $capturista;
    private User $revisor;
    private Municipio $municipio;
    private Variable $variable;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'datos.ver']);
        Permission::create(['name' => 'datos.aprobar']);
        Permission::create(['name' => 'datos.editar']);

        $this->capturista = User::factory()->create();
        $this->capturista->givePermissionTo(['datos.ver', 'datos.editar']);
        $this->revisor = User::factory()->create();
        $this->revisor->givePermissionTo(['datos.ver', 'datos.aprobar']);

        $macro = new Macrorregion();
        $macro->nombre = 'Macro';
        $macro->save();
        $micro = new Microrregion();
        $micro->nombre = 'Micro';
        $micro->macrorregion_id = $macro->id;
        $micro->save();

        $this->municipio = Municipio::create([
            'nombre' => 'Municipio A',
            'slug' => 'municipio-a',
            'microrregion_id' => $micro->id,
        ]);
        $dimension = Dimension::create(['nombre' => 'Dimensión', 'nombre_tecnico' => 'dimension']);
        $tematica = Tematica::create([
            'nombre' => 'Temática',
            'nombre_tecnico' => 'tematica',
            'dimension_id' => $dimension->id,
        ]);
        $indicador = Indicador::create(['nombre_amigable' => 'Indicador', 'tematica_id' => $tematica->id]);
        $this->variable = Variable::create([
            'indicador_id' => $indicador->id,
            'nombre_amigable' => 'Variable',
            'nombre_tecnico' => 'variable',
        ]);
    }

    public function test_pending_batch_does_not_publish_data(): void
    {
        $lote = $this->makeLote(LoteDatos::EN_REVISION);
        $this->makeFila($lote, 42.5, 'insertar');

        $this->assertDatabaseMissing('dato_historicos', [
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->variable->id,
            'anio' => 2025,
        ]);
    }

    public function test_valid_excel_creates_draft_and_staging_rows_without_publishing(): void
    {
        Storage::fake('local');
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['municipio_id', 'variable_id', 'anio', 'valor'],
            [$this->municipio->id, $this->variable->id, 2025, 55.25],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'lote') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile(
            $path,
            'datos.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = app(LoteDatosService::class)->crearBorrador($file, $this->capturista);

        $this->assertArrayHasKey('lote', $result);
        $this->assertEquals(LoteDatos::BORRADOR, $result['lote']->estado);
        $this->assertDatabaseHas('lote_dato_historicos', [
            'lote_datos_id' => $result['lote']->id,
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->variable->id,
            'anio' => 2025,
            'valor' => 55.25,
        ]);
        $this->assertDatabaseCount('dato_historicos', 0);
        Storage::disk('local')->assertExists($result['lote']->archivo_path);
    }

    public function test_approval_inserts_and_links_canonical_data(): void
    {
        $lote = $this->makeLote(LoteDatos::EN_REVISION);
        $this->makeFila($lote, 42.5, 'insertar');

        app(LoteDatosService::class)->aprobar($lote, $this->revisor);

        $this->assertDatabaseHas('dato_historicos', [
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->variable->id,
            'anio' => 2025,
            'valor' => 42.5,
            'lote_datos_id' => $lote->id,
        ]);
        $this->assertDatabaseHas('lotes_datos', [
            'id' => $lote->id,
            'estado' => LoteDatos::APROBADO,
            'usuario_revision_id' => $this->revisor->id,
        ]);
    }

    public function test_approval_updates_existing_data(): void
    {
        DatoHistorico::create([
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->variable->id,
            'anio' => 2025,
            'valor' => 10,
        ]);
        $lote = $this->makeLote(LoteDatos::EN_REVISION);
        $this->makeFila($lote, 75, 'actualizar');

        app(LoteDatosService::class)->aprobar($lote, $this->revisor);

        $this->assertSame(1, DatoHistorico::count());
        $this->assertEquals(75, DatoHistorico::first()->valor);
        $this->assertEquals($lote->id, DatoHistorico::first()->lote_datos_id);
    }

    public function test_rejection_does_not_publish_data(): void
    {
        $lote = $this->makeLote(LoteDatos::EN_REVISION);
        $this->makeFila($lote, 42.5, 'insertar');

        app(LoteDatosService::class)->rechazar($lote, $this->revisor, 'La fuente requiere corrección.');

        $this->assertDatabaseCount('dato_historicos', 0);
        $this->assertDatabaseHas('lotes_datos', [
            'id' => $lote->id,
            'estado' => LoteDatos::RECHAZADO,
            'observaciones' => 'La fuente requiere corrección.',
        ]);
    }

    public function test_user_without_approval_permission_cannot_approve(): void
    {
        $lote = $this->makeLote(LoteDatos::EN_REVISION);
        $this->makeFila($lote, 42.5, 'insertar');

        $this->actingAs($this->capturista)
            ->post(route('admin.lotes-datos.aprobar', $lote))
            ->assertForbidden();

        $this->assertDatabaseCount('dato_historicos', 0);
    }

    public function test_manual_edit_creates_review_batch_without_changing_public_value(): void
    {
        $dato = DatoHistorico::create([
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->variable->id,
            'anio' => 2025,
            'valor' => 10,
        ]);

        $this->actingAs($this->capturista)
            ->putJson(route('admin.datos.update', $dato), ['valor' => 25])
            ->assertStatus(202)
            ->assertJsonPath('success', 'La propuesta fue enviada a revisión. El valor publicado no cambió.');

        $this->assertEquals(10, $dato->fresh()->valor);
        $this->assertDatabaseHas('lotes_datos', [
            'tipo' => 'dato_historico_manual',
            'estado' => LoteDatos::EN_REVISION,
            'usuario_carga_id' => $this->capturista->id,
        ]);
        $this->assertDatabaseHas('lote_dato_historicos', ['valor' => 25, 'valor_original' => 10]);
    }

    public function test_approval_detects_changes_made_after_manual_proposal(): void
    {
        $dato = DatoHistorico::create([
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->variable->id,
            'anio' => 2025,
            'valor' => 10,
        ]);
        $lote = app(LoteDatosService::class)->crearEdicionManual($dato, 25, $this->capturista);
        $dato->update(['valor' => 15]);

        try {
            app(LoteDatosService::class)->aprobar($lote, $this->revisor);
            $this->fail('La aprobación debió detectar un conflicto.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cambió después', $e->errors()['lote'][0]);
        }

        $this->assertEquals(15, $dato->fresh()->valor);
        $this->assertEquals(LoteDatos::EN_REVISION, $lote->fresh()->estado);
    }

    public function test_complex_batch_is_staged_and_published_only_after_approval(): void
    {
        Storage::fake('local');
        $indicador = $this->variable->indicador;
        $indicador->update(['es_complejo' => true]);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['municipio_id', 'anio', 'Maíz', 'Frijol'],
            [$this->municipio->id, 2025, 120.5, 45],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'complejo') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile(
            $path,
            'complejos.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = app(LoteDatosService::class)->crearBorradorComplejo($file, $indicador->fresh(), $this->capturista);
        $lote = $result['lote'];

        $this->assertDatabaseCount('dato_indicador_complejos', 0);
        $this->assertDatabaseHas('lote_dato_indicador_complejos', ['lote_datos_id' => $lote->id]);

        app(LoteDatosService::class)->enviarRevision($lote, $this->capturista);
        app(LoteDatosService::class)->aprobar($lote, $this->revisor);

        $dato = DatoIndicadorComplejo::first();
        $this->assertSame(['Maíz' => 120.5, 'Frijol' => 45], $dato->datos);
        $this->assertEquals($lote->id, $dato->lote_datos_id);
    }

    private function makeLote(string $estado): LoteDatos
    {
        return LoteDatos::create([
            'estado' => $estado,
            'archivo_original' => 'datos.xlsx',
            'archivo_path' => 'lotes_datos/datos.xlsx',
            'usuario_carga_id' => $this->capturista->id,
            'total_filas' => 1,
            'filas_insertar' => 1,
        ]);
    }

    private function makeFila(LoteDatos $lote, float $valor, string $accion): LoteDatoHistorico
    {
        $original = DatoHistorico::where('municipio_id', $this->municipio->id)
            ->where('variable_id', $this->variable->id)
            ->where('anio', 2025)
            ->first();

        return LoteDatoHistorico::create([
            'lote_datos_id' => $lote->id,
            'fila_origen' => 2,
            'municipio_id' => $this->municipio->id,
            'variable_id' => $this->variable->id,
            'anio' => 2025,
            'valor' => $valor,
            'accion' => $accion,
            'valor_original' => $original?->valor,
            'motivo_sin_dato_original_id' => $original?->motivo_sin_dato_id,
            'dato_historico_updated_at' => $original?->updated_at,
        ]);
    }
}
