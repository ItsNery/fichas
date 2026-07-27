<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatoHistoricoController;
use App\Http\Controllers\Admin\DiccionarioController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\InstrumentoController;
use App\Http\Controllers\Admin\LoteDatosController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\MunicipioController as AdminMunicipioController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\FichaMunicipalV4Controller;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\OmnisearchController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\Admin\ConfiguracionFichaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/
// Route::get('/prueba-header', function () {
//      return view('prueba-header');
// });
Route::get('/', [HomeController::class, 'index'])->name('inicio');
Route::get('/api/municipios/search', [MunicipioController::class, 'search'])->name('api.municipios.search');
Route::get('/api/omnisearch', [OmnisearchController::class, 'search'])->name('api.omnisearch');

// --- Módulo 1: Banco de Indicadores ---
Route::prefix('banco-indicadores')->name('banco-indicadores.')->group(function () {
    Route::get('/', [FichaController::class, 'index'])->name('index');
    Route::post('/exportar', [FichaController::class, 'exportData'])->name('exportar');
    Route::post('/api/data', [FichaController::class, 'getData'])->name('api.data');
    Route::get('/api/mapa-datos/{indicador}/{anio}', [FichaController::class, 'getMapData'])->name('api.mapa.data');
    Route::get('/api/indicador-anios/{indicador}', [FichaController::class, 'getIndicatorYears'])->name('api.indicador.anios');
});

// --- Módulo 2: Fichas Municipales ---
Route::prefix('ficha/municipio')->name('ficha-municipal.')->group(function () {
    Route::get('/', [FichaController::class, 'directorioVisual'])->name('index');
    Route::get('/{municipio:slug}/v4', [FichaMunicipalV4Controller::class, 'index'])->name('v4');
    Route::get('/{municipio:slug}/v4/api/seccion/{dimension}', [FichaMunicipalV4Controller::class, 'section'])->name('v4.section');
    Route::get('/v4/api/municipios', [FichaMunicipalV4Controller::class, 'searchComparison'])->name('v4.municipios');
    Route::get('/comparar/{slug1}/{slug2}', [FichaController::class, 'compararMunicipal'])->name('comparar');
    Route::get('/comparar/{slug1}/{slug2}/pdf', [FichaController::class, 'exportarComparativaPDF'])->name('comparar.pdf');
    Route::get('/api/similitud-indicador/{municipio}/{config}', [FichaController::class, 'getSimilitudIndicador'])->name('api.indicador.similitud');
    Route::get('/api/grafico-datos/{municipio:slug}/{config}/{anio}', [FichaController::class, 'getGraficoDatosApi'])->name('api.grafico.data');
    Route::get('/{municipio:slug}', [FichaController::class, 'resumenMunicipalV3'])->name('show');
    Route::get('/{municipio:slug}/v1', [FichaController::class, 'resumenMunicipal'])->name('v1');
    Route::get('/{municipio:slug}/test', [FichaController::class, 'resumenMunicipalTest'])->name('test');
    Route::get('/{municipio:slug}/v3', [FichaController::class, 'resumenMunicipalV3'])->name('v3');
    Route::get('/{municipio:slug}/perfil', [FichaController::class, 'perfilMunicipal'])->name('perfil');
    Route::get('/{municipio:slug}/v3/pdf', [FichaController::class, 'exportarResumenV3PDF'])->name('v3.pdf');
    Route::get('/{municipio:slug}/perfil/pdf', [FichaController::class, 'exportarPerfilPDF'])->name('perfil.pdf');
    Route::get('/{municipio:slug}/pdf', [FichaController::class, 'exportarResumenPDF'])->name('pdf');
});

Route::get('/datos-abiertos', [HomeController::class, 'datosAbiertos'])->name('datos-abiertos.index');
Route::get('/datos-abiertos/exportar/{tipo}', [App\Http\Controllers\Admin\CatalogoController::class, 'exportarCatalogoPublico'])->name('datos-abiertos.export');

// --- Módulo 3: Perfiles Regionales ---
Route::name('regiones.')->group(function () {
    Route::get('ficha/estatal/perfil', [RegionController::class, 'perfilEstatal'])->name('estatal.perfil');
    Route::get('ficha/estatal/pdf', [RegionController::class, 'exportarEstatalPDF'])->name('estatal.pdf');
    Route::get('ficha/estatal/excel', [RegionController::class, 'exportarEstatalExcel'])->name('estatal.excel');
    Route::get('ficha/macrorregion/{macrorregion:slug}/perfil', [RegionController::class, 'perfilMacrorregion'])->name('macro.perfil');
    Route::get('ficha/microrregion/{microrregion:slug}/perfil', [RegionController::class, 'perfilMicrorregion'])->name('micro.perfil');
    Route::get('ficha/macrorregion/{macrorregion:slug}/pdf', [RegionController::class, 'exportarMacrorregionPDF'])->name('macro.pdf');
    Route::get('ficha/microrregion/{microrregion:slug}/pdf', [RegionController::class, 'exportarMicrorregionPDF'])->name('micro.pdf');
    Route::get('ficha/macrorregion/{macrorregion:slug}/excel', [RegionController::class, 'exportarMacrorregionExcel'])->name('macro.excel');
    Route::get('ficha/microrregion/{microrregion:slug}/excel', [RegionController::class, 'exportarMicrorregionExcel'])->name('micro.excel');
});


// 1. La ruta AJAX para obtener los años de una dimensión
Route::get('/api/dimension/{dimension}/anios-disponibles', [FichaController::class, 'getAniosPorDimension'])
    ->name('api.dimension.anios');

// 2. La nueva ruta de descarga (reemplaza la que tenías)
Route::get('/datos-abiertos/exportar-datos-historicos/{dimension}/{anio}', [DatoHistoricoController::class, 'exportPorDimensionAnio'])
    ->name('datos-abiertos.export-historicos');

Route::get('/api/indicador-complejo/{indicador}/anios-disponibles', [FichaController::class, 'getAniosPorIndicadorComplejo'])
    ->name('api.indicador-complejo.anios');

// 2. Ruta de descarga para datos complejos (filtrada por indicador y año)
Route::get('/datos-abiertos/exportar-datos-complejos/{indicador}/{anio}', [FichaController::class, 'exportDatosComplejos'])
    ->name('datos-abiertos.export-complejos');

Route::get('/api/docs', [App\Http\Controllers\ApiDocumentationController::class, 'index'])
    ->name('api.docs');

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación y Dashboard
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php'; // Inhabilitar registro desde este archivo
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->can('dashboard.ejecutivo')) return redirect()->route('admin.dashboard');
    if ($user->can('datos.ver')) return redirect()->route('admin.datos.index');
    if ($user->can('catalogos.ver')) return redirect()->route('admin.catalogos.index');
    if ($user->can('auditoria.ver')) return redirect()->route('admin.auditoria.index');

    return redirect()->route('inicio');
})->middleware(['auth'])->name('dashboard');
/*
|--------------------------------------------------------------------------
| Rutas del Panel de Administración
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {

    // CRUDs principales
    Route::get('/datos/exportar', [DatoHistoricoController::class, 'export'])->name('datos.export');
    Route::resource('datos', DatoHistoricoController::class);
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('instrumentos', InstrumentoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('municipios', AdminMunicipioController::class)->only(['index', 'edit', 'update']);
    Route::resource('configuracion-fichas', ConfiguracionFichaController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('permissions', PermissionController::class)->except(['show'])->parameters(['permissions' => 'permission']);
    Route::get('/configuracion-fichas/api/variables-por-indicador/{indicador}', [ConfiguracionFichaController::class, 'getVariablesPorIndicador'])->name('configuracion-fichas.api-variables');
    Route::get('/configuracion-fichas/api/todas-las-variables', [ConfiguracionFichaController::class, 'getAllVariables'])->name('configuracion-fichas.api-todas-variables');
    Route::get('/configuracion-fichas/api/anios-disponibles', [ConfiguracionFichaController::class, 'getAniosDisponibles'])->name('configuracion-fichas.api-anios');
    Route::get('/configuracion-fichas/api/correlacion', [ConfiguracionFichaController::class, 'calcularCorrelacion'])->name('configuracion-fichas.api-correlacion');

    Route::get('/municipios/{municipio}/instrumentos', [AdminMunicipioController::class, 'getInstrumentosJson'])->name('municipios.getInstrumentos');
    Route::post('/municipios/{municipio}/instrumentos', [AdminMunicipioController::class, 'syncInstrumentos'])->name('municipios.syncInstrumentos');

    // Grupo de rutas para la Gestión de Catálogos
    Route::prefix('catalogos')->name('catalogos.')->group(function () {
        Route::get('/', [CatalogoController::class, 'index'])->name('index');
        Route::get('/exportar', [CatalogoController::class, 'export'])->name('export');

        // CRUD para Dimensiones
        Route::post('/dimensions', [CatalogoController::class, 'storeDimension'])->name('dimensions.store');
        Route::put('/dimensions/{dimension}', [CatalogoController::class, 'updateDimension'])->name('dimensions.update');
        Route::delete('/dimensions/{dimension}', [CatalogoController::class, 'destroyDimension'])->name('dimensions.destroy');

        Route::post('/tematicas', [CatalogoController::class, 'storeTematica'])->name('tematicas.store');
        Route::put('/tematicas/{tematica}', [CatalogoController::class, 'updateTematica'])->name('tematicas.update');
        Route::delete('/tematicas/{tematica}', [CatalogoController::class, 'destroyTematica'])->name('tematicas.destroy');

        Route::post('/indicadores', [CatalogoController::class, 'storeIndicador'])->name('indicadores.store');
        Route::put('/indicadores/{indicador}', [CatalogoController::class, 'updateIndicador'])->name('indicadores.update');
        Route::delete('/indicadores/{indicador}', [CatalogoController::class, 'destroyIndicador'])->name('indicadores.destroy');

        Route::get('/indicadores/crear', [CatalogoController::class, 'crearIndicador'])->name('indicadores.crear');
        Route::post('/indicadores/crear', [CatalogoController::class, 'guardarIndicador'])->name('indicadores.guardar');
        Route::get('/indicadores/{indicador}/editar', [CatalogoController::class, 'editarIndicador'])->name('indicadores.editar');
        Route::put('/indicadores/{indicador}/editar', [CatalogoController::class, 'actualizarIndicador'])->name('indicadores.actualizar');

        Route::post('/variables', [CatalogoController::class, 'storeVariable'])->name('variables.store');
        Route::put('/variables/{variable}', [CatalogoController::class, 'updateVariable'])->name('variables.update');
        Route::delete('/variables/{variable}', [CatalogoController::class, 'destroyVariable'])->name('variables.destroy');

        Route::get('/variables/{variable}/preview', [CatalogoController::class, 'previewConstruido'])->name('variables.preview');
        Route::post('/variables/{variable}/generar', [CatalogoController::class, 'generarConstruido'])->name('variables.generar');
        Route::post('/variables/{variable}/regenerar', [CatalogoController::class, 'regenerarConstruido'])->name('variables.regenerar');
    });

    // Grupo de rutas para las Importaciones
    Route::prefix('importar')->name('import.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::get('/plantilla/{tipo}', [ImportController::class, 'descargarPlantilla'])->name('plantilla');

        Route::post('/dimensiones', [ImportController::class, 'importDimensiones'])->name('dimensiones');
        Route::post('/tematicas', [ImportController::class, 'importTematicas'])->name('tematicas');
        Route::post('/indicadores', [ImportController::class, 'importIndicadores'])->name('indicadores');
        Route::post('/variables', [ImportController::class, 'importVariables'])->name('variables');
        // Route::post('/datos', [ImportController::class, 'importDatos'])->name('datos');
        Route::post('/datos/validar', [ImportController::class, 'validateDatos'])->name('datos.validate');
        Route::post('/datos/ejecutar', [ImportController::class, 'importDatos'])->name('datos.perform');
        Route::post('/datos-complejos', [ImportController::class, 'importDatosComplejos'])->name('datos_complejos');
        Route::post('/instrumentos', [ImportController::class, 'importInstrumentos'])->name('instrumentos');
        Route::post('/instrumentos-asignacion', [ImportController::class, 'importInstrumentosAsignacion'])->name('instrumentos_asignacion');
    });
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::get('/lotes-datos', [LoteDatosController::class, 'index'])->name('lotes-datos.index');
    Route::get('/lotes-datos/{lote}', [LoteDatosController::class, 'show'])->name('lotes-datos.show');
    Route::post('/lotes-datos/{lote}/aprobar', [LoteDatosController::class, 'aprobar'])->name('lotes-datos.aprobar');
    Route::post('/lotes-datos/{lote}/rechazar', [LoteDatosController::class, 'rechazar'])->name('lotes-datos.rechazar');
    Route::get('/diccionario', [DiccionarioController::class, 'index'])->name('diccionario.index');
    Route::get('/diccionario/{indicador}/editar', [DiccionarioController::class, 'edit'])->name('diccionario.edit');
    Route::put('/diccionario/{indicador}', [DiccionarioController::class, 'update'])->name('diccionario.update');

    Route::get('/salud-datos', [DashboardController::class, 'dataHealth'])->name('salud-datos');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::prefix('catalogos/exportar')->name('catalogos.export.')->group(function () {
        Route::get('/dimensiones', [CatalogoController::class, 'exportDimensiones'])->name('dimensiones');
        Route::get('/tematicas', [CatalogoController::class, 'exportTematicas'])->name('tematicas');
        Route::get('/indicadores', [CatalogoController::class, 'exportIndicadores'])->name('indicadores');
        Route::get('/variables', [CatalogoController::class, 'exportVariables'])->name('variables');
    });
});

/*
|--------------------------------------------------------------------------
| Rutas de API (AJAX)
|--------------------------------------------------------------------------
*/
Route::post('/api/datos-historicos', [FichaController::class, 'getData'])->name('api.data');
