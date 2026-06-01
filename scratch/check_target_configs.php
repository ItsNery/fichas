<?php
use App\Models\ConfiguracionFicha;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$indicatorIds = [137, 33, 42, 49];
$configs = ConfiguracionFicha::whereIn('indicador_id', $indicatorIds)->get();

echo "Existing configurations for targeted indicators:\n";
echo "=================================================\n";
foreach ($configs as $c) {
    echo "ID {$c->id} | Indicador ID: {$c->indicador_id} | Sección: '{$c->seccion}' | Tipo Vis: '{$c->tipo_visualizacion}' | Activo: " . ($c->activo ? 'SI' : 'NO') . "\n";
}
?>
