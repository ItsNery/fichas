<?php
$data = json_decode(file_get_contents(__DIR__ . '/indicators_audit.json'), true);

$output = "=========================================\n";
$output .= "       RESUMEN DE INDICADORES EN BD      \n";
$output .= "=========================================\n\n";

foreach ($data as $dim) {
    $output .= "DIMENSIÓN: " . $dim['dimension'] . "\n";
    $output .= str_repeat("-", strlen($dim['dimension']) + 11) . "\n";
    
    foreach ($dim['tematicas'] as $tem) {
        $output .= "  ├─ Temática: " . $tem['tematica'] . "\n";
        
        foreach ($tem['indicadores'] as $ind) {
            $varCount = count($ind['variables']);
            $varNames = array_map(fn($v) => $v['nombre_amigable'] . " (" . $v['unidad_medida'] . ")", $ind['variables']);
            $varsStr = implode(', ', array_slice($varNames, 0, 3));
            if ($varCount > 3) {
                $varsStr .= " and " . ($varCount - 3) . " more";
            }
            
            $output .= "  │    └─ ID {$ind['id']}: {$ind['nombre_amigable']} [" . ($ind['es_complejo'] ? 'COMPLEJO' : 'ESTÁNDAR') . "] [Polaridad: {$ind['polaridad']}]\n";
            $output .= "  │         Variables ({$varCount}): {$varsStr}\n";
        }
    }
    $output .= "\n";
}

file_put_contents(__DIR__ . '/indicators_summary.txt', $output);
echo "Successfully generated summary at scratch/indicators_summary.txt\n";
?>
