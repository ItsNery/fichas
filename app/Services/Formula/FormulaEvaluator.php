<?php

namespace App\Services\Formula;

use App\Exceptions\FormulaException;
use Closure;

/**
 * Evalúa un árbol de expresión (ver FormulaExpressionParser) contra un resolvedor
 * de valores. No ejecuta código: solo resuelve nodos conocidos.
 */
class FormulaEvaluator
{
    /**
     * @param  Closure(string, ?string): (float|int|null)  $resolver
     */
    public function evaluar(array $arbol, Closure $resolver): ?float
    {
        switch ($arbol['tipo']) {
            case 'numero':
                return (float) $arbol['valor'];

            case 'slot':
            case 'agregacion':
                $valor = $resolver($arbol['clave'], $arbol['funcion'] ?? null);

                return $valor === null ? null : (float) $valor;

            case 'unario':
                $operando = $this->evaluar($arbol['operando'], $resolver);

                if ($operando === null) {
                    return null;
                }

                return $arbol['operador'] === '-' ? -$operando : $operando;

            case 'operacion':
                return $this->evaluarOperacion($arbol, $resolver);
        }

        throw new FormulaException('Nodo de expresión desconocido.');
    }

    private function evaluarOperacion(array $arbol, Closure $resolver): ?float
    {
        $izquierda = $this->evaluar($arbol['izquierda'], $resolver);
        $derecha = $this->evaluar($arbol['derecha'], $resolver);

        if ($izquierda === null || $derecha === null) {
            return null;
        }

        return match ($arbol['operador']) {
            '+' => $izquierda + $derecha,
            '-' => $izquierda - $derecha,
            '*' => $izquierda * $derecha,
            '/' => $this->dividir($izquierda, $derecha),
            '^' => $izquierda ** $derecha,
            default => throw new FormulaException('Operador no permitido.'),
        };
    }

    private function dividir(float $numerador, float $denominador): ?float
    {
        if ($denominador == 0.0) {
            return null;
        }

        return $numerador / $denominador;
    }
}
