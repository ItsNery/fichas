<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GeographicAggregationService
{
    public function method($config, $variables): string
    {
        $configured = $config?->ajustes_visuales['aggregation'] ?? null;
        if (in_array($configured, ['sum', 'average', 'mode'], true)) {
            return $configured;
        }

        $unit = mb_strtolower((string) ($variables->first()?->unidad_medida ?? ''), 'UTF-8');
        if (str_contains($unit, '%') || str_contains($unit, 'porcentaje')
            || str_contains($unit, 'promedio') || str_contains($unit, 'índice')
            || str_contains($unit, 'indice') || str_contains($unit, 'grado')) {
            return 'average';
        }

        return 'sum';
    }

    public function label(string $method): string
    {
        return match ($method) {
            'sum' => 'Total acumulado',
            'average' => 'Promedio municipal',
            'ratio' => 'Razón hombres/mujeres',
            'mode' => 'Valor más frecuente',
            default => 'Valor',
        };
    }

    public function commonLatestYear(Collection $rows, array $municipioIds, array $variableIds): ?int
    {
        $requiredMunicipios = collect($municipioIds)->map(fn ($id) => (int) $id)->unique()->values();
        $requiredVariables = collect($variableIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($requiredMunicipios->isEmpty() || $requiredVariables->isEmpty()) {
            return null;
        }

        return $rows
            ->filter(fn ($row) => $row->valor !== null)
            ->groupBy('anio')
            ->filter(function (Collection $yearRows) use ($requiredMunicipios, $requiredVariables) {
                $completeMunicipios = $yearRows
                    ->groupBy('municipio_id')
                    ->filter(fn (Collection $municipioRows) => $requiredVariables->every(
                        fn ($variableId) => $municipioRows->contains('variable_id', $variableId)
                    ))
                    ->keys()
                    ->map(fn ($id) => (int) $id);

                return $requiredMunicipios->diff($completeMunicipios)->isEmpty();
            })
            ->keys()
            ->map(fn ($year) => (int) $year)
            ->sortDesc()
            ->first();
    }

    public function latestYear(Collection $rows, array $variableIds): ?int
    {
        return $rows
            ->whereIn('variable_id', $variableIds)
            ->filter(fn ($row) => $row->valor !== null)
            ->max('anio');
    }

    public function aggregate(Collection $rows, string $method): float|int|null
    {
        $values = $rows
            ->filter(fn ($row) => $row->valor !== null && is_numeric($row->valor))
            ->pluck('valor')
            ->map(fn ($value) => (float) $value);

        if ($values->isEmpty()) {
            return null;
        }

        return match ($method) {
            'average' => $values->avg(),
            default => $values->sum(),
        };
    }

    public function aggregateByMunicipality(Collection $rows, string $method): Collection
    {
        return $rows->groupBy('municipio_id')->map(fn (Collection $municipioRows) => $this->aggregate($municipioRows, $method));
    }

    public function aggregateAcrossMunicipalities(Collection $rows, array $municipioIds, string $method, ?string $acrossMethod = null): float|int|null
    {
        $values = $this->aggregateByMunicipality($rows, $method)
            ->only(collect($municipioIds)->map(fn ($id) => (string) $id)->all())
            ->filter(fn ($value) => $value !== null);

        if ($values->isEmpty()) {
            return null;
        }

        $acrossMethod ??= $method;
        return $acrossMethod === 'sum' ? $values->sum() : $values->avg();
    }

    public function ratio(Collection $rows, int $numeratorId, int $denominatorId): ?float
    {
        $numerator = $rows->where('variable_id', $numeratorId)->sum('valor');
        $denominator = $rows->where('variable_id', $denominatorId)->sum('valor');

        return $denominator > 0 ? ($numerator / $denominator) * 100 : null;
    }

    public function coverage(Collection $rows, array $municipioIds): array
    {
        $municipios = collect($municipioIds)->map(fn ($id) => (int) $id)->unique();
        $withData = $rows
            ->filter(fn ($row) => $row->valor !== null)
            ->pluck('municipio_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        $total = $municipios->count();
        return [
            'municipios_total' => $total,
            'municipios_con_dato' => $municipios->intersect($withData)->count(),
            'coverage' => $total > 0 ? round($municipios->intersect($withData)->count() / $total, 4) : 0,
        ];
    }
}
