<?php
use App\Models\ConfiguracionFicha;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$configs = ConfiguracionFicha::with('indicador')
    ->where('activo', true)
    ->orderBy('seccion')
    ->orderBy('orden')
    ->get();

echo "Active Ficha Configurations:\n";
echo "============================\n";
foreach ($configs as $c) {
    echo "ID {$c->id} | Sección: '{$c->seccion}' | Orden: {$c->orden} | Tipo Vis: '{$c->tipo_visualizacion}'\n";
    echo "  - Indicador: {$c->indicador->nombre_amigable} (ID {$c->indicador_id})\n";
    echo "  - Grid: '{$c->clase_grid}'\n";
    echo "  - Variables: " . $c->variables->pluck('nombre_amigable')->implode(', ') . "\n\n";
}
?>
