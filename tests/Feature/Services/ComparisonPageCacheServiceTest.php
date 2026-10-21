<?php

namespace Tests\Feature\Services;

use App\Services\ComparisonPageCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ComparisonPageCacheServiceTest extends TestCase
{
    public function test_it_reuses_a_cached_comparison_for_the_same_data_version(): void
    {
        Cache::flush();
        $renders = 0;
        $service = app(ComparisonPageCacheService::class);

        $first = $service->remember('puebla', 'tehuacan', 'version-1', function () use (&$renders) {
            $renders++;

            return '<html>comparativa</html>';
        });
        $second = $service->remember('puebla', 'tehuacan', 'version-1', function () use (&$renders) {
            $renders++;

            return '<html>no debe renderizarse</html>';
        });

        $this->assertSame('<html>comparativa</html>', $first);
        $this->assertSame('<html>comparativa</html>', $second);
        $this->assertSame(1, $renders);
    }

    public function test_it_regenerates_a_comparison_when_the_data_version_changes(): void
    {
        Cache::flush();
        $service = app(ComparisonPageCacheService::class);

        $service->remember('puebla', 'tehuacan', 'version-1', fn () => '<html>anterior</html>');
        $html = $service->remember('puebla', 'tehuacan', 'version-2', fn () => '<html>actualizada</html>');

        $this->assertSame('<html>actualizada</html>', $html);
    }
}
