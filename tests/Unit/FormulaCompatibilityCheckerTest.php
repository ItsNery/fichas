<?php

namespace Tests\Unit;

use App\Services\Formula\FormulaCompatibilityChecker;
use App\Services\Formula\FormulaExpressionParser;
use App\Services\Formula\FormulaTypeSystem;
use PHPUnit\Framework\TestCase;

class FormulaCompatibilityCheckerTest extends TestCase
{
    private FormulaExpressionParser $parser;

    private FormulaCompatibilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FormulaExpressionParser();
        $this->checker = new FormulaCompatibilityChecker();
    }

    public function test_permite_dividir_absolutos_entre_absolutos(): void
    {
        $errores = $this->errores('SUM(A) / SUM(B) * 100', [
            'A' => 'absoluto',
            'B' => 'absoluto',
        ]);

        $this->assertEmpty($errores);
    }

    public function test_rechaza_dividir_un_porcentaje_entre_un_absoluto(): void
    {
        $errores = $this->errores('SUM(A) / SUM(B)', [
            'A' => 'porcentaje',
            'B' => 'absoluto',
        ]);

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('Porcentaje', $errores[0]);
        $this->assertStringContainsString('Absoluto', $errores[0]);
    }

    public function test_rechaza_restar_tipos_incompatibles(): void
    {
        $errores = $this->errores('SUM(A) - SUM(B)', [
            'A' => 'porcentaje',
            'B' => 'absoluto',
        ]);

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('restar', $errores[0]);
    }

    public function test_rechaza_multiplicar_dos_absolutos(): void
    {
        $errores = $this->errores('SUM(A) * SUM(B)', [
            'A' => 'absoluto',
            'B' => 'absoluto',
        ]);

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('cuadrado', $errores[0]);
    }

    public function test_rechaza_multiplicar_dos_razones(): void
    {
        $errores = $this->errores('SUM(A) * SUM(B)', [
            'A' => 'porcentaje',
            'B' => 'porcentaje',
        ]);

        $this->assertCount(1, $errores);
    }

    public function test_permite_multiplicar_porcentaje_por_absoluto(): void
    {
        $errores = $this->errores('SUM(A) * SUM(B)', [
            'A' => 'porcentaje',
            'B' => 'absoluto',
        ]);

        $this->assertEmpty($errores);
    }

    public function test_permite_dividir_razones_del_mismo_tipo(): void
    {
        $errores = $this->errores('SUM(A) / SUM(B)', [
            'A' => 'porcentaje',
            'B' => 'porcentaje',
        ]);

        $this->assertEmpty($errores);
    }

    public function test_rechaza_dividir_razones_de_tipo_distinto(): void
    {
        $errores = $this->errores('SUM(A) / SUM(B)', [
            'A' => 'porcentaje',
            'B' => 'tasa',
        ]);

        $this->assertCount(1, $errores);
    }

    public function test_no_valida_variables_sin_tipo_conocido(): void
    {
        $errores = $this->errores('SUM(A) / SUM(B)', [
            'A' => 'desconocido',
            'B' => 'absoluto',
        ]);

        $this->assertEmpty($errores);
    }

    public function test_determina_el_tipo_del_resultado(): void
    {
        $tipo = $this->tipo('SUM(A) / SUM(B) * 100', [
            'A' => 'absoluto',
            'B' => 'absoluto',
        ]);

        $this->assertEquals(FormulaTypeSystem::PORCENTAJE, $tipo);
    }

    public function test_un_cociente_de_absolutos_es_una_razon(): void
    {
        $tipo = $this->tipo('SUM(A) / SUM(B)', [
            'A' => 'absoluto',
            'B' => 'absoluto',
        ]);

        $this->assertEquals(FormulaTypeSystem::RAZON, $tipo);
    }

    public function test_una_suma_de_absolutos_es_absoluto(): void
    {
        $tipo = $this->tipo('SUM(A) + SUM(B)', [
            'A' => 'absoluto',
            'B' => 'absoluto',
        ]);

        $this->assertEquals(FormulaTypeSystem::ABSOLUTO, $tipo);
    }

    /**
     * @param  array<string, string>  $tipos
     * @return array<int, string>
     */
    private function errores(string $expresion, array $tipos): array
    {
        return $this->validar($expresion, $tipos)['errores'];
    }

    /**
     * @param  array<string, string>  $tipos
     */
    private function tipo(string $expresion, array $tipos): string
    {
        return $this->validar($expresion, $tipos)['tipoResultado'];
    }

    /**
     * @param  array<string, string>  $tipos
     * @return array{errores: array<int, string>, tipoResultado: string}
     */
    private function validar(string $expresion, array $tipos): array
    {
        $analisis = $this->parser->analizar($expresion);

        return $this->checker->validar($analisis['arbol'], $tipos);
    }
}
