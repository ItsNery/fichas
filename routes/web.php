<?php

use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatoHistoricoController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\InstrumentoController;
use App\Http\Controllers\Admin\MunicipioController as AdminMunicipioController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MunicipioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/
// Route::get('/', function () {
//     return view('inicio');
// });
Route::get('/prueba-header', function () {
     return view('prueba-header');
});
Route::get('/', [HomeController::class, 'index']);
Route::get('/api/municipios/search', [MunicipioController::class, 'search'])->name('api.municipios.search');

Route::prefix('banco-indicadores')->name('fichas.')->group(function () {
    Route::get('/', [FichaController::class, 'index'])->name('index');
    Route::get('/resumen/{municipio}', [FichaController::class, 'resumenMunicipal'])->name('resumen');
    Route::post('/exportar', [FichaController::class, 'exportData'])->name('exportar');
    Route::get('/resumen/{municipio}/pdf', [FichaController::class, 'exportarResumenPDF'])->name('resumen.pdf');
    Route::post('/api/data', [FichaController::class, 'getData'])->name('api.data');
    Route::get('/api/mapa-datos/{indicador}/{anio}', [FichaController::class, 'getMapData'])->name('api.mapa.data');
    Route::get('/api/indicador-anios/{indicador}', [FichaController::class, 'getIndicatorYears'])->name('api.indicador.anios');
});

// Route::get('/datos-abiertos', function () {
//     return view('datos-abiertos');
// })->name('datos-abiertos.index');
Route::get('/datos-abiertos', [HomeController::class, 'datosAbiertos'])->name('datos-abiertos.index');
Route::get('/datos-abiertos/exportar/{tipo}', [App\Http\Controllers\Admin\CatalogoController::class, 'exportarCatalogoPublico'])
    ->name('datos-abiertos.export');

// Route::get('/datos-abiertos/exportar-datos-historicos/{dimension}', [DatoHistoricoController::class, 'export'])
//     ->name('datos-abiertos.export-historicos');

// 1. La ruta AJAX para obtener los años de una dimensión
Route::get('/api/dimension/{dimension}/anios-disponibles', [FichaController::class, 'getAniosPorDimension'])
    ->name('api.dimension.anios');

// 2. La nueva ruta de descarga (reemplaza la que tenías)
Route::get('/datos-abiertos/exportar-datos-historicos/{dimension}/{anio}', [DatoHistoricoController::class, 'exportPorDimensionAnio'])
    ->name('datos-abiertos.export-historicos');

// Route::get('/datos-abiertos/exportar-datos-complejos/{indicador}', [DatoHistoricoController::class, 'exportComplejos'])
//     ->name('datos-abiertos.export-complejos');

Route::get('/api/indicador-complejo/{indicador}/anios-disponibles', [FichaController::class, 'getAniosPorIndicadorComplejo'])
    ->name('api.indicador-complejo.anios');

// 2. Ruta de descarga para datos complejos (filtrada por indicador y año)
Route::get('/datos-abiertos/exportar-datos-complejos/{indicador}/{anio}', [FichaController::class, 'exportDatosComplejos'])
    ->name('datos-abiertos.export-complejos');

Route::get('/test-500', function () {
    // La función abort() de Laravel está hecha para esto.
    // Le decimos que genere un error 500 con un mensaje.
    abort(500, "¡Esto es una prueba del error 500!");
});
/*
|--------------------------------------------------------------------------
| Rutas de Autenticación y Dashboard
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php'; // Inhabilitar registro desde este archivo
Route::get('/dashboard', function () {
    // Redirigir a la ruta nombrada de tu admin
    return redirect()->route('admin.dashboard');
})->middleware(['auth']);
/*
|--------------------------------------------------------------------------
| Rutas del Panel de Administración
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {

    // CRUDs principales
    Route::get('/datos/exportar', [DatoHistoricoController::class, 'export'])->name('datos.export');
    Route::resource('datos', DatoHistoricoController::class);
    Route::resource('users', UserController::class);
    Route::resource('instrumentos', InstrumentoController::class);
    Route::resource('municipios', AdminMunicipioController::class);

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

        Route::post('/variables', [CatalogoController::class, 'storeVariable'])->name('variables.store');
        Route::put('/variables/{variable}', [CatalogoController::class, 'updateVariable'])->name('variables.update');
        Route::delete('/variables/{variable}', [CatalogoController::class, 'destroyVariable'])->name('variables.destroy');
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
