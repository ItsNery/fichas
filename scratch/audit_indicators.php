<?php
use App\Models\Indicador;
use App\Models\Variable;
use App\Models\Dimension;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Auditing Indicators and Variables...\n";

$dimensions = Dimension::with(['tematicas.indicadores.variables'])->orderBy('nombre')->get();

$report = [];

foreach ($dimensions as $dim) {
    $dimData = [
        'dimension' => $dim->nombre,
        'tematicas' => []
    ];
    
    foreach ($dim->tematicas as $tem) {
        $temData = [
            'tematica' => $tem->nombre,
            'indicadores' => []
        ];
        
        foreach ($tem->indicadores as $ind) {
            $indData = [
                'id' => $ind->id,
                'nombre_amigable' => $ind->nombre_amigable,
                'nombre_tecnico' => $ind->nombre_tecnico,
                'es_complejo' => (bool)$ind->es_complejo,
                'polaridad' => $ind->polaridad,
                'tipo_grafico_default' => $ind->tipo_grafico_default,
                'variables' => []
            ];
            
            foreach ($ind->variables as $var) {
                $indData['variables'][] = [
                    'id' => $var->id,
                    'nombre_amigable' => $var->nombre_amigable,
                    'nombre_tecnico' => $var->nombre_tecnico,
                    'unidad_medida' => $var->unidad_medida,
                    'es_destacada' => (bool)$var->es_destacada,
                    'es_kpi' => (bool)$var->es_kpi,
                ];
            }
            
            $temData['indicadores'][] = $indData;
        }
        
        $dimData['tematicas'][] = $temData;
    }
    
    $report[] = $dimData;
}

$outputPath = __DIR__ . '/indicators_audit.json';
file_put_contents($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Successfully generated audit report at: {$outputPath}\n";
?>
