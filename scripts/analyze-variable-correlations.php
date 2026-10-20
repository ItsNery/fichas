<?php

use App\Models\DatoHistorico;
use App\Models\Variable;
use App\Services\CorrelationService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$minimumCoverage = 100;
$minimumUniqueValues = 8;
$limit = (int) ($argv[1] ?? 100);
$familyFilter = $argv[2] ?? null;
$correlationService = app(CorrelationService::class);
$confidenceInterval = static function (float $correlation, int $sampleSize): array {
    if ($sampleSize <= 3 || abs($correlation) >= 1) {
        return [$correlation, $correlation];
    }

    $z = atanh($correlation);
    $margin = 1.96 / sqrt($sampleSize - 3);

    return [round(tanh($z - $margin), 3), round(tanh($z + $margin), 3)];
};

$normalize = static fn (?string $value): string => mb_strtolower(trim((string) $value), 'UTF-8');
$classifyUnit = static function (string $unit) use ($normalize): array {
    $normalized = $normalize($unit);
    $relativeUnits = ['porcentaje', 'promedio', 'índice', 'razón', 'tasa', 'grado', 'estadía promedio'];
    $isRelative = in_array($normalized, $relativeUnits, true)
        || str_contains($normalized, 'por cada')
        || str_contains($normalized, 'por vivienda')
        || str_contains($normalized, 'por cien');

    return [
        'family' => $isRelative ? 'normalizada' : 'absoluta:' . $normalized,
        'normalized_unit' => $normalized,
        'is_relative' => $isRelative,
    ];
};

$variables = Variable::with(['indicador.tematica.dimension'])
    ->whereHas('datosHistoricos')
    ->get()
    ->filter(fn (Variable $variable) => empty($variable->mapeo_valores))
    ->values();

$datasets = [];
foreach ($variables as $variable) {
    $year = DatoHistorico::where('variable_id', $variable->id)
        ->whereNotNull('valor')
        ->max('anio');
    if (!$year) {
        continue;
    }

    $values = DatoHistorico::where('variable_id', $variable->id)
        ->where('anio', $year)
        ->whereNotNull('valor')
        ->pluck('valor', 'municipio_id')
        ->map(fn ($value) => (float) $value);
    if ($values->count() < $minimumCoverage || $values->unique()->count() < $minimumUniqueValues) {
        continue;
    }

    $datasets[$variable->id] = [
        'variable' => $variable,
        'year' => (int) $year,
        'values' => $values,
        'unit' => $classifyUnit($variable->unidad_medida),
    ];
}

$results = [];
$variableIds = array_keys($datasets);
$count = count($variableIds);

for ($i = 0; $i < $count; $i++) {
    for ($j = $i + 1; $j < $count; $j++) {
        $left = $datasets[$variableIds[$i]];
        $right = $datasets[$variableIds[$j]];
        $leftVariable = $left['variable'];
        $rightVariable = $right['variable'];

        if ($leftVariable->indicador_id === $rightVariable->indicador_id) {
            continue;
        }

        $bothRelative = $left['unit']['is_relative'] && $right['unit']['is_relative'];
        $sameAbsoluteUnit = !$left['unit']['is_relative']
            && !$right['unit']['is_relative']
            && $left['unit']['normalized_unit'] === $right['unit']['normalized_unit'];
        if (!$bothRelative && !$sameAbsoluteUnit) {
            continue;
        }

        $points = [];
        foreach ($left['values'] as $municipalityId => $leftValue) {
            if ($right['values']->has($municipalityId)) {
                $points[] = [$leftValue, $right['values']->get($municipalityId)];
            }
        }
        if (count($points) < $minimumCoverage) {
            continue;
        }

        $pearson = $correlationService->pearson($points);
        $spearman = $correlationService->spearman($points);
        if ($pearson === null || abs($pearson) < 0.5) {
            continue;
        }

        $results[] = [
            'pearson' => $pearson,
            'pearson_ci_95' => $confidenceInterval($pearson, count($points)),
            'spearman' => $spearman,
            'n' => count($points),
            'family' => $bothRelative ? 'normalizada' : 'absoluta_misma_unidad',
            'left' => [
                'id' => $leftVariable->id,
                'variable' => $leftVariable->nombre_amigable,
                'indicator' => $leftVariable->indicador?->nombre_amigable,
                'unit' => trim($leftVariable->unidad_medida),
                'year' => $left['year'],
                'theme' => $leftVariable->indicador?->tematica?->nombre,
                'dimension' => $leftVariable->indicador?->tematica?->dimension?->nombre,
            ],
            'right' => [
                'id' => $rightVariable->id,
                'variable' => $rightVariable->nombre_amigable,
                'indicator' => $rightVariable->indicador?->nombre_amigable,
                'unit' => trim($rightVariable->unidad_medida),
                'year' => $right['year'],
                'theme' => $rightVariable->indicador?->tematica?->nombre,
                'dimension' => $rightVariable->indicador?->tematica?->dimension?->nombre,
            ],
        ];
    }
}

usort($results, fn (array $a, array $b) => abs($b['pearson']) <=> abs($a['pearson']));

if ($familyFilter) {
    $results = array_values(array_filter($results, fn (array $result) => $result['family'] === $familyFilter));
}

echo json_encode([
    'generated_at' => now()->toIso8601String(),
    'minimum_coverage' => $minimumCoverage,
    'minimum_unique_values' => $minimumUniqueValues,
    'variables_evaluated' => count($datasets),
    'eligible_pairs' => count($results),
    'results' => array_slice($results, 0, max(1, $limit)),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
