<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteEvaluationController;
use App\Http\Controllers\PublicApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/site-evaluation', [SiteEvaluationController::class, 'store']);

Route::prefix('v1')->group(function () {
    Route::get('/municipios', [PublicApiController::class, 'municipios'])->name('api.public.municipios');
    Route::get('/microrregiones', [PublicApiController::class, 'microrregiones'])->name('api.public.microrregiones');
    Route::get('/macrorregiones', [PublicApiController::class, 'macrorregiones'])->name('api.public.macrorregiones');
    Route::get('/indicadores', [PublicApiController::class, 'indicadores'])->name('api.public.indicadores');
    Route::get('/indicadores/{id}', [PublicApiController::class, 'indicador'])->name('api.public.indicador');
    Route::get('/metadata', [PublicApiController::class, 'metadata'])->name('api.public.metadata');
    Route::match(['get', 'post'], '/data', [PublicApiController::class, 'data'])->name('api.public.data');
    Route::post('/consulta', [PublicApiController::class, 'consultarDatos'])->name('api.public.consulta');
});

Route::get('/openapi.json', [\App\Http\Controllers\ApiDocumentationController::class, 'openapi'])->name('api.openapi');
