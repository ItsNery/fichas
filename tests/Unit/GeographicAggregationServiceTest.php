<?php

namespace Tests\Unit;

use App\Services\GeographicAggregationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GeographicAggregationServiceTest extends TestCase
{
    public function test_it_averages_values_by_municipality_before_averaging_the_region(): void
    {
        $service = app(GeographicAggregationService::class);
        $rows = new Collection([
            (object) ['municipio_id' => 1, 'variable_id' => 10, 'anio' => 2020, 'valor' => 1],
            (object) ['municipio_id' => 1, 'variable_id' => 11, 'anio' => 2020, 'valor' => 3],
            (object) ['municipio_id' => 2, 'variable_id' => 10, 'anio' => 2020, 'valor' => 5],
            (object) ['municipio_id' => 2, 'variable_id' => 11, 'anio' => 2020, 'valor' => 7],
        ]);

        $this->assertSame(4.0, $service->aggregateAcrossMunicipalities($rows, [1, 2], 'average'));
        $this->assertSame(16.0, $service->aggregateAcrossMunicipalities($rows, [1, 2], 'sum'));
    }

    public function test_it_returns_the_deterministic_mode(): void
    {
        $service = app(GeographicAggregationService::class);
        $rows = new Collection([
            (object) ['municipio_id' => 1, 'valor' => 'Alto'],
            (object) ['municipio_id' => 2, 'valor' => 'Bajo'],
            (object) ['municipio_id' => 3, 'valor' => 'Alto'],
            (object) ['municipio_id' => 4, 'valor' => 'Bajo'],
        ]);

        $this->assertSame('Alto', $service->mode($rows));
    }

    public function test_it_selects_the_latest_year_with_complete_municipal_coverage(): void
    {
        $service = app(GeographicAggregationService::class);
        $rows = new Collection([
            (object) ['municipio_id' => 1, 'variable_id' => 10, 'anio' => 2020, 'valor' => 1],
            (object) ['municipio_id' => 2, 'variable_id' => 10, 'anio' => 2020, 'valor' => 2],
            (object) ['municipio_id' => 1, 'variable_id' => 10, 'anio' => 2022, 'valor' => 3],
        ]);

        $this->assertSame(2020, $service->commonLatestYear($rows, [1, 2], [10]));
    }

    public function test_it_calculates_a_weighted_sex_ratio_from_population_totals(): void
    {
        $service = app(GeographicAggregationService::class);
        $rows = new Collection([
            (object) ['municipio_id' => 1, 'variable_id' => 2, 'anio' => 2020, 'valor' => 60],
            (object) ['municipio_id' => 1, 'variable_id' => 3, 'anio' => 2020, 'valor' => 40],
            (object) ['municipio_id' => 2, 'variable_id' => 2, 'anio' => 2020, 'valor' => 340],
            (object) ['municipio_id' => 2, 'variable_id' => 3, 'anio' => 2020, 'valor' => 260],
        ]);

        $this->assertSame(75.0, $service->ratio($rows, 3, 2));
    }
}
