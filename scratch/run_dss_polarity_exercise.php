<?php

use App\Models\Indicador;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$csvPath = __DIR__ . '/../docs/dss-ejercicio-polaridad.csv';
$summaryPath = __DIR__ . '/../docs/dss-ejercicio-polaridad.md';
$csv = fopen($csvPath, 'wb');
fputcsv($csv, [
    'indicador_id', 'indicador', 'variable_id', 'variable', 'polaridad', 'municipio_id', 'municipio',
    'anio_anterior', 'valor_anterior', 'anio_reciente', 'valor_reciente', 'cambio_porcentual',
    'cambio_ajustado', 'lectura_dss',
]);

$summary = [];
$totalRows = 0;

foreach (Indicador::with('variables')->orderBy('id')->get() as $indicador) {
    // Multi-variable indicators need a semantic aggregation rule before ranking them.
    if ($indicador->variables->count() !== 1) {
        continue;
    }

    $variable = $indicador->variables->first();
    $direction = match ($indicador->polaridad) {
        'asendente' => 1,
        'descendente' => -1,
        default => 0,
    };

    $rows = DB::table('dato_historicos')
        ->join('municipios', 'municipios.id', '=', 'dato_historicos.municipio_id')
        ->where('dato_historicos.variable_id', $variable->id)
        ->whereNotNull('dato_historicos.valor')
        ->orderBy('dato_historicos.municipio_id')
        ->orderBy('dato_historicos.anio')
        ->get([
            'dato_historicos.municipio_id', 'municipios.nombre', 'dato_historicos.anio', 'dato_historicos.valor',
        ])
        ->groupBy('municipio_id');

    $indicatorSummary = ['mejora' => 0, 'deterioro' => 0, 'estable' => 0, 'sin_clasificar' => 0, 'ranked' => []];

    foreach ($rows as $municipioRows) {
        if ($municipioRows->count() < 2) {
            $indicatorSummary['sin_clasificar']++;
            continue;
        }

        $previous = $municipioRows->get($municipioRows->count() - 2);
        $recent = $municipioRows->last();
        $oldValue = (float) $previous->valor;
        $newValue = (float) $recent->valor;
        $change = $oldValue == 0.0 ? null : (($newValue - $oldValue) / abs($oldValue)) * 100;
        $adjusted = $change === null || $direction === 0 ? null : $change * $direction;

        if ($adjusted === null) {
            $reading = 'sin_clasificar';
            $indicatorSummary['sin_clasificar']++;
        } elseif ($adjusted > 0.5) {
            $reading = 'mejora';
            $indicatorSummary['mejora']++;
        } elseif ($adjusted < -0.5) {
            $reading = 'deterioro';
            $indicatorSummary['deterioro']++;
        } else {
            $reading = 'estable';
            $indicatorSummary['estable']++;
        }

        if ($adjusted !== null) {
            $indicatorSummary['ranked'][] = [
                'municipio' => $recent->nombre,
                'adjusted' => $adjusted,
                'reading' => $reading,
            ];
        }

        fputcsv($csv, [
            $indicador->id, $indicador->nombre_amigable, $variable->id, $variable->nombre_amigable,
            $indicador->polaridad, $recent->municipio_id, $recent->nombre, $previous->anio, $oldValue,
            $recent->anio, $newValue, $change, $adjusted, $reading,
        ]);
        $totalRows++;
    }

    usort($indicatorSummary['ranked'], fn($a, $b) => $b['adjusted'] <=> $a['adjusted']);
    $summary[$indicador->id] = [
        'name' => $indicador->nombre_amigable,
        'polarity' => $indicador->polaridad,
        'variable' => $variable->nombre_amigable,
        'counts' => $indicatorSummary,
    ];
}

fclose($csv);

$improvements = 0;
$deteriorations = 0;
$stable = 0;
$skipped = 0;
$markdown = "# Ejercicio DSS con polaridad\n\n";
$markdown .= "Comparación del último par de años disponible por municipio para indicadores con una sola variable. "
    . "El cambio ajustado invierte el signo cuando la polaridad es `descendente`. No implica causalidad.\n\n";
$markdown .= "- Registros comparables: {$totalRows}\n";

foreach ($summary as $item) {
    $counts = $item['counts'];
    $improvements += $counts['mejora'];
    $deteriorations += $counts['deterioro'];
    $stable += $counts['estable'];
    $skipped += $counts['sin_clasificar'];
    $markdown .= "## {$item['name']}\n\n";
    $markdown .= "- Variable: {$item['variable']}\n";
    $markdown .= "- Polaridad: `{$item['polarity']}`\n";
    $markdown .= "- Resultado: {$counts['mejora']} mejoras, {$counts['deterioro']} deterioros, {$counts['estable']} estables.\n";

    if ($item['polarity'] !== 'neutro') {
        $markdown .= "- Mayores mejoras: ";
        $markdown .= collect(array_slice($counts['ranked'], 0, 3))->map(fn($row) => $row['municipio'] . ' (' . number_format($row['adjusted'], 1) . '%)')->join(', ') ?: 'N/D';
        $markdown .= ".\n- Mayores deterioros: ";
        $worst = array_slice(array_reverse($counts['ranked']), 0, 3);
        $markdown .= collect($worst)->map(fn($row) => $row['municipio'] . ' (' . number_format($row['adjusted'], 1) . '%)')->join(', ') ?: 'N/D';
        $markdown .= ".\n";
    } else {
        $markdown .= "- Lectura: neutra; se reporta cambio, pero no mejora o deterioro normativo.\n";
    }
    $markdown .= "\n";
}

$markdown = str_replace(
    "Comparación",
    "Resumen global: {$improvements} mejoras, {$deteriorations} deterioros, {$stable} estables y {$skipped} sin clasificar.\n\nComparación",
    $markdown,
);
file_put_contents($summaryPath, $markdown);

echo "Registros DSS generados: {$totalRows}\n";
