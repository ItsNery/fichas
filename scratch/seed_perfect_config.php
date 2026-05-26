<?php

use App\Models\Indicador;
use App\Models\ConfiguracionFicha;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Población
$indPob = Indicador::where('nombre_amigable', 'like', '%Población%')->first();
if ($indPob) {
    ConfiguracionFicha::updateOrCreate(
        ['indicador_id' => $indPob->id],
        [
            'seccion' => 'Demografía',
            'orden' => 1,
            'tipo_visualizacion' => 'area',
            'titulo_reporte' => 'Dinámica Poblacional',
            'plantilla_narrativa' => 'En el año {anio}, {municipio} cuenta con una población de {valor} habitantes. Con este resultado, el municipio {ranking}, {promedio_estatal}.',
            'activo' => true,
            'clase_grid' => 'col-12'
        ]
    );
    echo "Población configurada\n";
}

// 2. Marginación
$indMarg = Indicador::where('nombre_amigable', 'like', '%Marginación%')->first();
if ($indMarg) {
    ConfiguracionFicha::updateOrCreate(
        ['indicador_id' => $indMarg->id],
        [
            'seccion' => 'Social',
            'orden' => 2,
            'tipo_visualizacion' => 'barras',
            'titulo_reporte' => 'Rezago y Marginación',
            'plantilla_narrativa' => 'El grado de marginación detectado es de {valor}. En la comparativa, {municipio} {ranking} (donde 1 es el más marginado), mientras que el {promedio_estatal}.',
            'activo' => true,
            'clase_grid' => 'col-12'
        ]
    );
    echo "Marginación configurada\n";
}
