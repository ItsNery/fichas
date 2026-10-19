<?php

namespace Tests\Unit;

use App\Exceptions\FormulaException;
use App\Services\Formula\FormulaEvaluator;
use App\Services\Formula\FormulaExpressionParser;
use PHPUnit\Framework\TestCase;

class FormulaExpressionParserTest extends TestCase
{
    private FormulaExpressionParser $parser;

    private FormulaEvaluator $evaluador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FormulaExpressionParser();
        $this->evaluador = new FormulaEvaluator();
    }

    public function test_detecta_las_variables_de_la_expresion(): void
    {
        $analisis = $this->parser->analizar('SUM(A) / SUM(B) * 100');

        $this->assertEquals(['A', 'B'], $analisis['slots']);
    }

    public function test_acepta_combinaciones_de_sumas_y_divisiones(): void
    {
        $analisis = $this->parser->analizar('((SUM(A) + SUM(B)) / SUM(C)) * 100');

        $this->assertEquals(['A', 'B', 'C'], $analisis['slots']);

        $valor = $this->evaluador->evaluar($analisis['arbol'], function (string $clave) {
            return match ($clave) {
                'A' => 10.0,
                'B' => 20.0,
                'C' => 6.0,
                default => null,
            };
        });

        $this->assertEqualsWithDelta(500.0, $valor, 0.0001);
    }

    public function test_respeta_la_funcion_de_agregacion(): void
    {
        $analisis = $this->parser->analizar('AVG(A) * 100');

        $valor = $this->evaluador->evaluar($analisis['arbol'], fn (string $clave, ?string $funcion) => match ($funcion) {
            'avg' => 25.0,
            default => null,
        });

        $this->assertEqualsWithDelta(2500.0, $valor, 0.0001);
    }

    public function test_devuelve_null_en_division_por_cero(): void
    {
        $analisis = $this->parser->analizar('SUM(A) / SUM(B)');

        $valor = $this->evaluador->evaluar($analisis['arbol'], fn (string $clave) => $clave === 'A' ? 10.0 : 0.0);

        $this->assertNull($valor);
    }

    public function test_rechaza_funciones_no_permitidas(): void
    {
        $this->expectException(FormulaException::class);

        $this->parser->analizar('EXEC(A)');
    }

    public function test_rechaza_expresiones_incompletas(): void
    {
        $this->expectException(FormulaException::class);

        $this->parser->analizar('SUM(A) /');
    }
}
