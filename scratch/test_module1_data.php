<?php
use App\Models\Variable;
use App\Models\DatoHistorico;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Verifying data availability for Module 1...\n\n";

// Target variables
$variableNames = [
    'FAISMUN DEVENGADO',
    'FORTAMUN DEVENGADO',
    'FAISMUN APROBADO',
    'FORTAMUN APROBADO',
    'Población total',
    'Porcentaje de población en situación de pobreza',
    'Porcentaje de población con carencia por acceso a los servicios básicos en la vivienda',
    'Rezago social'
];

foreach ($variableNames as $name) {
    $variable = Variable::where('nombre_amigable', 'like', "%{$name}%")->first();
    if ($variable) {
        $yearsCount = DatoHistorico::where('variable_id', $variable->id)
            ->select('anio', \DB::raw('count(*) as count'))
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->get();
            
        echo "Variable ID {$variable->id}: '{$variable->nombre_amigable}' (Unidad: {$variable->unidad_medida})\n";
        foreach ($yearsCount as $yc) {
            echo "  - Año {$yc->anio}: {$yc->count} registros\n";
        }
        echo "\n";
    } else {
        echo "Variable '{$name}' NOT found.\n\n";
    }
}
?>
