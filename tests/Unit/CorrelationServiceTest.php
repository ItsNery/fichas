<?php

namespace Tests\Unit;

use App\Services\CorrelationService;
use PHPUnit\Framework\TestCase;

class CorrelationServiceTest extends TestCase
{
    public function test_it_calculates_perfect_positive_and_negative_pearson_correlations(): void
    {
        $service = new CorrelationService();

        $this->assertSame(1.0, $service->pearson([[1, 2], [2, 4], [3, 6]]));
        $this->assertSame(-1.0, $service->pearson([[1, 6], [2, 4], [3, 2]]));
    }

    public function test_it_calculates_spearman_using_average_ranks_for_ties(): void
    {
        $service = new CorrelationService();

        $this->assertSame(1.0, $service->spearman([[10, 2], [10, 2], [20, 7], [30, 9]]));
    }

    public function test_it_returns_null_when_a_variable_has_no_variation(): void
    {
        $service = new CorrelationService();

        $this->assertNull($service->pearson([[1, 2], [1, 4], [1, 6]]));
        $this->assertNull($service->spearman([[1, 2], [1, 4], [1, 6]]));
    }
}
