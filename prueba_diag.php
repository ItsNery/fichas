<?php

use App\Models\Indicador;
use App\Models\ConfiguracionFicha;
use App\Models\Municipio;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- INICIO DE PRUEBA TECNICA ---\n";

$indicador = Indicador::where('nombre_amigable', 'like', '%Población%')->first();

if (!$indicador) {
    die("ERROR: No se encontró ningún indicador con la palabra 'Población'. Revisa tus indicadores.\n");
}

echo "Indicador encontrado: " . $indicador->nombre_amigable . " (ID: " . $indicador->id . ")\n";

$config = ConfiguracionFicha::updateOrCreate(
    ['indicador_id' => $indicador->id],
    [
        'seccion' => 'Demografia',
        'tipo_visualizacion' => 'bar',
        'clase_grid' => 'col-12',
        'orden' => 1,
        'plantilla_narrativa' => 'PRUEBA TECNICA: El municipio de [MUNICIPIO] tiene un valor de [VALOR].',
        'activo' => true
    ]
);

if ($config) {
    echo "¡ÉXITO! Registro creado/actualizado en la base de datos.\n";
    echo "ID del registro: " . $config->id . "\n";
    echo "Ahora ve a tu navegador y refresca el perfil de Puebla.\n";
} else {
    echo "ERROR: No se pudo guardar el registro.\n";
}
