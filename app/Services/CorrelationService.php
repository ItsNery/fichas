<?php

namespace App\Services;

class CorrelationService
{
    public function pearson(array $points): ?float
    {
        if (count($points) < 2) {
            return null;
        }

        $meanX = collect($points)->avg(fn (array $point) => $point[0]);
        $meanY = collect($points)->avg(fn (array $point) => $point[1]);
        $numerator = 0.0;
        $sumX = 0.0;
        $sumY = 0.0;

        foreach ($points as $point) {
            $differenceX = $point[0] - $meanX;
            $differenceY = $point[1] - $meanY;
            $numerator += $differenceX * $differenceY;
            $sumX += $differenceX ** 2;
            $sumY += $differenceY ** 2;
        }

        $denominator = sqrt($sumX * $sumY);

        return $denominator > 0 ? round($numerator / $denominator, 3) : null;
    }

    public function spearman(array $points): ?float
    {
        if (count($points) < 2) {
            return null;
        }

        $rankedX = $this->rank(array_column($points, 0));
        $rankedY = $this->rank(array_column($points, 1));
        $rankedPoints = [];

        foreach ($rankedX as $index => $rank) {
            $rankedPoints[] = [$rank, $rankedY[$index]];
        }

        return $this->pearson($rankedPoints);
    }

    public function describe(?float $coefficient, bool $monotonic = false): string
    {
        if ($coefficient === null) {
            return 'No hay variación suficiente para calcular la correlación.';
        }

        $magnitude = abs($coefficient);
        $strength = $magnitude >= 0.7 ? 'fuerte' : ($magnitude >= 0.4 ? 'moderada' : 'débil');
        $direction = $coefficient >= 0 ? 'positiva' : 'inversa';
        $association = $monotonic ? 'monótona' : 'lineal';

        return "Asociación {$association} {$strength} y {$direction}.";
    }

    private function rank(array $values): array
    {
        $indexed = [];
        foreach ($values as $index => $value) {
            $indexed[] = ['index' => $index, 'value' => (float) $value];
        }

        usort($indexed, fn (array $a, array $b) => $a['value'] <=> $b['value']);
        $ranks = [];
        $position = 0;
        $count = count($indexed);

        while ($position < $count) {
            $tieEnd = $position;
            while ($tieEnd + 1 < $count && $indexed[$tieEnd + 1]['value'] === $indexed[$position]['value']) {
                $tieEnd++;
            }

            $averageRank = (($position + 1) + ($tieEnd + 1)) / 2;
            for ($i = $position; $i <= $tieEnd; $i++) {
                $ranks[$indexed[$i]['index']] = $averageRank;
            }
            $position = $tieEnd + 1;
        }

        ksort($ranks);

        return $ranks;
    }
}
