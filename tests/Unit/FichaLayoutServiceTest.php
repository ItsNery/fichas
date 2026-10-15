<?php

namespace Tests\Unit;

use App\Models\ConfiguracionFicha;
use App\Services\FichaLayoutService;
use Tests\TestCase;

class FichaLayoutServiceTest extends TestCase
{
    public function test_it_uses_a_compact_layout_for_a_kpi(): void
    {
        $config = new ConfiguracionFicha([
            'tipo_visualizacion' => 'kpi',
            'ajustes_visuales' => ['layout_mode' => 'auto'],
        ]);

        $layout = app(FichaLayoutService::class)->compose([['config' => $config, 'datos' => []]])[0]['layout'];

        $this->assertSame(['columns' => 4, 'variant' => 'compact'], $layout);
    }

    public function test_it_expands_a_dense_chart_and_honors_an_editorial_override(): void
    {
        $config = new ConfiguracionFicha([
            'tipo_visualizacion' => 'lineas',
            'ajustes_visuales' => ['layout_mode' => 'auto'],
        ]);
        $denseData = ['echarts' => ['xAxis' => ['data' => range(1, 6)], 'series' => []]];

        $layout = app(FichaLayoutService::class)->compose([['config' => $config, 'datos' => $denseData]])[0]['layout'];
        $this->assertSame(['columns' => 8, 'variant' => 'wide'], $layout);

        $config->ajustes_visuales = ['layout_mode' => 'destacado'];
        $featured = app(FichaLayoutService::class)->compose([['config' => $config, 'datos' => []]])[0]['layout'];
        $this->assertSame(['columns' => 12, 'variant' => 'featured'], $featured);

        $config->ajustes_visuales = ['layout_mode' => 'estandar'];
        $standard = app(FichaLayoutService::class)->compose([['config' => $config, 'datos' => []]])[0]['layout'];
        $this->assertSame(['columns' => 6, 'variant' => 'standard'], $standard);
    }
}
