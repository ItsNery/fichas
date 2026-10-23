<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatoHistoricoController;
use App\Http\Controllers\Admin\DiccionarioController;
use App\Http\Controllers\Admin\FormulaController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\InstrumentoController;
use App\Http\Controllers\Admin\LoteDatosController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\MunicipioController as AdminMunicipioController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TrafficAnalyticsController;
use App\Http\Controllers\FichaController;
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
Route::view('/demo/mapa-inset', 'demo.mapa-inset')->name('demo.mapa-inset');

Route::get('/challenge', [App\Http\Controllers\TurnstileController::class, 'challenge'])
    ->middleware('throttle:20,1')
    ->name('turnstile.challenge');
Route::post('/challenge', [App\Http\Controllers\TurnstileController::class, 'verify'])
    ->middleware('throttle:20,1')
    ->name('turnstile.verify');

// --- Módulo 1: Banco de Indicadores ---
Route::prefix('banco-indicadores')->name('banco-indicadores.')->group(function () {
    Route::get('/', [FichaController::class, 'index'])->name('index');
    Route::post('/exportar', [FichaController::class, 'exportData'])->name('exportar');
    Route::post('/api/data', [FichaController::class, 'getData'])->name('api.data');
    Route::get('/api/mapa-datos/{indicador}/{anio}', [FichaController::class, 'getMapData'])->name('api.mapa.data');
    Route::get('/api/indicador-anios/{indicador}', [FichaController::class, 'getIndicatorYears'])->name('api.indicador.anios');
});

// --- Módulo 2: Municipios ---
Route::prefix('municipios')->name('municipios.')->group(function () {
    Route::get('/', [FichaController::class, 'directorioVisual'])->name('index');
    Route::get('/comparar/{slug1}/con/{slug2}', [FichaController::class, 'compararMunicipal'])
        ->middleware(['turnstile', 'throttle:ficha-comparativa'])
        ->name('comparar');
    Route::get('/comparar/{slug1}/con/{slug2}/dimension/{dimension}', [FichaController::class, 'compararDimension'])
        ->middleware('throttle:ficha-comparativa-seccion')
        ->name('comparar.dimension');
    Route::get('/comparar/{slug1}/con/{slug2}/pdf', [FichaController::class, 'exportarComparativaPDF'])
        ->middleware(['turnstile', 'throttle:ficha-pdf'])
        ->name('comparar.pdf');
    Route::get('/api/similitud-indicador/{municipio}/{config}', [FichaController::class, 'getSimilitudIndicador'])->name('api.indicador.similitud');
    Route::get('/api/grafico-datos/{municipio:slug}/{config}/{anio}', [FichaController::class, 'getGraficoDatosApi'])->name('api.grafico.data');
    Route::get('/{municipio:slug}/dimension/{dimension}', [FichaController::class, 'perfilDimension'])
        ->middleware('throttle:ficha-perfil-seccion')
        ->name('dimension');
    Route::get('/{municipio:slug}', [FichaController::class, 'perfilMunicipal'])->name('show');
    Route::get('/{municipio:slug}/perfil.pdf', [FichaController::class, 'exportarPerfilPDF'])
        ->middleware(['turnstile', 'throttle:ficha-pdf'])
        ->name('perfil.pdf');
    Route::get('/{municipio:slug}/panorama', [FichaController::class, 'panoramaMunicipal'])->name('panorama');
    Route::get('/{municipio:slug}/panorama/mapa.png', [FichaController::class, 'panoramaMap'])->name('panorama.map');
    Route::get('/{municipio:slug}/panorama.pdf', [FichaController::class, 'exportarPanoramaPDF'])
        ->middleware(['turnstile', 'throttle:ficha-pdf'])
        ->name('panorama.pdf');
});

Route::get('/datos-abiertos', [HomeController::class, 'datosAbiertos'])->name('datos-abiertos.index');
Route::get('/datos-abiertos/exportar/{tipo}', [App\Http\Controllers\Admin\CatalogoController::class, 'exportarCatalogoPublico'])->name('datos-abiertos.export');
Route::get('/datos-abiertos/exportar-indicadores-coyuntura', [HomeController::class, 'exportarCoyuntura'])
    ->name('datos-abiertos.export-coyuntura');

// --- Módulo 3: Perfiles Regionales ---
Route::name('regiones.')->group(function () {
    Route::get('/perfiles-regionales', [RegionController::class, 'index'])->name('index');
    Route::get('ficha/estatal/perfil/dimension/{dimension}', [RegionController::class, 'perfilEstatalDimension'])
        ->middleware('throttle:ficha-perfil-seccion')
        ->name('estatal.dimension');
    Route::get('ficha/estatal/perfil', [RegionController::class, 'perfilEstatal'])->name('estatal.perfil');
    Route::get('ficha/estatal/pdf', [RegionController::class, 'exportarEstatalPDF'])->name('estatal.pdf');
    Route::get('ficha/estatal/excel', [RegionController::class, 'exportarEstatalExcel'])->name('estatal.excel');
    Route::get('ficha/macrorregion/{macrorregion:slug}/perfil', [RegionController::class, 'perfilMacrorregion'])->name('macro.perfil');
    Route::get('ficha/microrregion/{microrregion:slug}/perfil', [RegionController::class, 'perfilMicrorregion'])->name('micro.perfil');
    Route::get('ficha/macrorregion/{macrorregion:slug}/perfil/dimension/{dimension}', [RegionController::class, 'perfilMacrorregionDimension'])
        ->middleware('throttle:ficha-perfil-seccion')
        ->name('macro.dimension');
    Route::get('ficha/microrregion/{microrregion:slug}/perfil/dimension/{dimension}', [RegionController::class, 'perfilMicrorregionDimension'])
        ->middleware('throttle:ficha-perfil-seccion')
        ->name('micro.dimension');
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
    Route::put('/datos/territorial/{datoGeograficoHistorico}', [DatoHistoricoController::class, 'updateTerritorial'])->name('datos.territorial.update');
    Route::delete('/datos/territorial/{datoGeograficoHistorico}', [DatoHistoricoController::class, 'destroyTerritorial'])->name('datos.territorial.destroy');
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
        Route::get('/territorial', [CatalogoController::class, 'territorial'])->name('territorial');
        Route::get('/territorial/exportar/{tipo}', [CatalogoController::class, 'exportTerritorial'])->name('territorial.export');

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
        Route::patch('/tematicas/{tematica}/indicadores/orden', [CatalogoController::class, 'reorderIndicadores'])->name('tematicas.indicadores.reorder');
        Route::patch('/indicadores/{indicador}/variables/orden', [CatalogoController::class, 'reorderVariables'])->name('indicadores.variables.reorder');

        Route::get('/indicadores/crear', [CatalogoController::class, 'crearIndicador'])->name('indicadores.crear');
        Route::post('/indicadores/crear', [CatalogoController::class, 'guardarIndicador'])->name('indicadores.guardar');
        Route::get('/indicadores/{indicador}/editar', [CatalogoController::class, 'editarIndicador'])->name('indicadores.editar');
        Route::put('/indicadores/{indicador}/editar', [CatalogoController::class, 'actualizarIndicador'])->name('indicadores.actualizar');

        Route::post('/variables', [CatalogoController::class, 'storeVariable'])->name('variables.store');
        Route::put('/variables/{variable}', [CatalogoController::class, 'updateVariable'])->name('variables.update');
        Route::post('/indicadores/{indicador}/asignar-variable', [CatalogoController::class, 'asignarVariable'])->name('indicadores.asignar-variable');
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
        Route::post('/datos-geograficos', [ImportController::class, 'importDatosGeograficos'])->name('datos_geograficos');
        Route::post('/instrumentos', [ImportController::class, 'importInstrumentos'])->name('instrumentos');
        Route::post('/instrumentos-asignacion', [ImportController::class, 'importInstrumentosAsignacion'])->name('instrumentos_asignacion');
    });
    // Grupo de rutas para el catálogo de fórmulas
    Route::prefix('formulas')->name('formulas.')->group(function () {
        Route::get('/', [FormulaController::class, 'index'])->name('index');
        Route::get('/crear', [FormulaController::class, 'create'])->name('create');
        Route::post('/', [FormulaController::class, 'store'])->name('store');
        Route::post('/preview', [FormulaController::class, 'preview'])->name('preview');
        Route::post('/importar', [FormulaController::class, 'importar'])->name('importar');
        Route::get('/{formula}', [FormulaController::class, 'show'])->name('show');
        Route::get('/{formula}/editar', [FormulaController::class, 'edit'])->name('edit');
        Route::put('/{formula}', [FormulaController::class, 'update'])->name('update');
        Route::delete('/{formula}', [FormulaController::class, 'destroy'])->name('destroy');
        Route::post('/{formula}/generar', [FormulaController::class, 'generar'])->name('generar');
    });

    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::get('/trafico', [TrafficAnalyticsController::class, 'index'])->name('trafico.index');
    Route::get('/lotes-datos', [LoteDatosController::class, 'index'])->name('lotes-datos.index');
    Route::get('/lotes-datos/{lote}', [LoteDatosController::class, 'show'])->name('lotes-datos.show');
    Route::post('/lotes-datos/{lote}/aprobar', [LoteDatosController::class, 'aprobar'])->name('lotes-datos.aprobar');
    Route::post('/lotes-datos/{lote}/rechazar', [LoteDatosController::class, 'rechazar'])->name('lotes-datos.rechazar');
    Route::get('/diccionario', [DiccionarioController::class, 'index'])->name('diccionario.index');
    Route::get('/diccionario/{indicador}/editar', [DiccionarioController::class, 'edit'])->name('diccionario.edit');
    Route::put('/diccionario/{indicador}', [DiccionarioController::class, 'update'])->name('diccionario.update');

    Route::get('/salud-datos', [DashboardController::class, 'dataHealth'])->name('salud-datos');
    Route::post('/salud-datos/indicadores/{indicador}/periodicidad', [DashboardController::class, 'actualizarPeriodicidad'])->name('salud-datos.periodicidad');
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
