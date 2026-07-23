<?php

namespace Tests\Unit;

use App\Models\Municipio;
use App\Services\FichaNarratorService;
use PHPUnit\Framework\TestCase;

class FichaNarratorServiceTest extends TestCase
{
    public function test_it_interprets_catalog_polarities_in_historical_trends(): void
    {
        $municipio = new Municipio(['nombre' => 'Municipio de prueba']);
        $base = [
            'valor_actual' => '110',
            'total' => 110,
            'tendencia' => [
                ['anio' => 2020, 'valor' => 100],
                ['anio' => 2021, 'valor' => 110],
            ],
        ];

        $this->assertStringContainsString(
            'crecimiento acumulado',
            FichaNarratorService::procesar('{tendencia_historica}', $municipio, $base + ['polaridad' => 'asendente']),
        );
        $this->assertStringContainsString(
            'incremento desfavorable',
            FichaNarratorService::procesar('{tendencia_historica}', $municipio, $base + ['polaridad' => 'descendente']),
        );
        $this->assertStringContainsString(
            'incremento del',
            FichaNarratorService::procesar('{tendencia_historica}', $municipio, $base + ['polaridad' => 'neutro']),
        );
    }
}
