<?php

namespace App\Services\Formula;

use App\Exceptions\FormulaException;

/**
 * Analizador de expresiones para fórmulas.
 *
 * Gramática soportada:
 *   expresion  := termino (('+' | '-') termino)*
 *   termino    := factor (('*' | '/') factor)*
 *   factor     := unario ('^' factor)?
 *   unario     := ('+' | '-')? primario
 *   primario   := NUMERO | '(' expresion ')' | FUNCION '(' IDENTIFICADOR ')' | IDENTIFICADOR
 *
 * No se permite texto libre: solo números, identificadores (variables de la fórmula),
 * operadores aritméticos y funciones de agregación de una lista blanca.
 */
class FormulaExpressionParser
{
    public const FUNCIONES = ['sum', 'avg', 'count', 'min', 'max'];

    private string $expresion = '';

    private int $posicion = 0;

    private int $longitud = 0;

    /**
     * @return array{arbol: array, slots: array<int, string>}
     */
    public function analizar(string $expresion): array
    {
        $this->expresion = trim($expresion);
        $this->posicion = 0;
        $this->longitud = strlen($this->expresion);

        if ($this->expresion === '') {
            throw new FormulaException('La expresión está vacía.');
        }

        $arbol = $this->parseExpresion();
        $this->saltarEspacios();

        if ($this->posicion < $this->longitud) {
            throw new FormulaException(sprintf(
                'Carácter inesperado "%s" en la posición %d.',
                $this->expresion[$this->posicion],
                $this->posicion + 1
            ));
        }

        return [
            'arbol' => $arbol,
            'slots' => $this->slotsDelArbol($arbol),
        ];
    }

    private function parseExpresion(): array
    {
        $izquierda = $this->parseTermino();

        while (true) {
            $this->saltarEspacios();
            $operador = $this->peek();

            if ($operador !== '+' && $operador !== '-') {
                return $izquierda;
            }

            $this->posicion++;
            $derecha = $this->parseTermino();

            $izquierda = [
                'tipo' => 'operacion',
                'operador' => $operador,
                'izquierda' => $izquierda,
                'derecha' => $derecha,
            ];
        }
    }

    private function parseTermino(): array
    {
        $izquierda = $this->parseFactor();

        while (true) {
            $this->saltarEspacios();
            $operador = $this->peek();

            if ($operador !== '*' && $operador !== '/') {
                return $izquierda;
            }

            $this->posicion++;
            $derecha = $this->parseFactor();

            $izquierda = [
                'tipo' => 'operacion',
                'operador' => $operador,
                'izquierda' => $izquierda,
                'derecha' => $derecha,
            ];
        }
    }

    private function parseFactor(): array
    {
        $base = $this->parseUnario();

        $this->saltarEspacios();
        if ($this->peek() === '^') {
            $this->posicion++;

            return [
                'tipo' => 'operacion',
                'operador' => '^',
                'izquierda' => $base,
                'derecha' => $this->parseFactor(),
            ];
        }

        return $base;
    }

    private function parseUnario(): array
    {
        $this->saltarEspacios();
        $operador = $this->peek();

        if ($operador === '+' || $operador === '-') {
            $this->posicion++;

            return [
                'tipo' => 'unario',
                'operador' => $operador,
                'operando' => $this->parseUnario(),
            ];
        }

        return $this->parsePrimario();
    }

    private function parsePrimario(): array
    {
        $this->saltarEspacios();

        if ($this->posicion >= $this->longitud) {
            throw new FormulaException('La expresión termina de forma inesperada.');
        }

        $caracter = $this->expresion[$this->posicion];

        if ($caracter === '(') {
            $this->posicion++;
            $expresion = $this->parseExpresion();
            $this->saltarEspacios();

            if ($this->peek() !== ')') {
                throw new FormulaException('Falta un paréntesis de cierre.');
            }

            $this->posicion++;

            return $expresion;
        }

        if (ctype_digit($caracter) || $caracter === '.') {
            return $this->parseNumero();
        }

        if (ctype_alpha($caracter) || $caracter === '_') {
            return $this->parseIdentificador();
        }

        throw new FormulaException(sprintf(
            'Carácter no válido "%s" en la posición %d.',
            $caracter,
            $this->posicion + 1
        ));
    }

    private function parseNumero(): array
    {
        $inicio = $this->posicion;
        $punto = false;

        while ($this->posicion < $this->longitud) {
            $caracter = $this->expresion[$this->posicion];

            if (ctype_digit($caracter)) {
                $this->posicion++;
                continue;
            }

            if ($caracter === '.' && ! $punto) {
                $punto = true;
                $this->posicion++;
                continue;
            }

            break;
        }

        return [
            'tipo' => 'numero',
            'valor' => (float) substr($this->expresion, $inicio, $this->posicion - $inicio),
        ];
    }

    private function parseIdentificador(): array
    {
        $inicio = $this->posicion;

        while ($this->posicion < $this->longitud) {
            $caracter = $this->expresion[$this->posicion];

            if (ctype_alnum($caracter) || $caracter === '_') {
                $this->posicion++;
                continue;
            }

            break;
        }

        $identificador = substr($this->expresion, $inicio, $this->posicion - $inicio);
        $this->saltarEspacios();

        if ($this->peek() === '(') {
            $funcion = strtolower($identificador);

            if (! in_array($funcion, self::FUNCIONES, true)) {
                throw new FormulaException(sprintf(
                    'Función no permitida "%s". Disponibles: %s.',
                    $identificador,
                    strtoupper(implode(', ', self::FUNCIONES))
                ));
            }

            $this->posicion++;
            $this->saltarEspacios();
            $argumento = $this->parsePrimario();

            if ($argumento['tipo'] !== 'slot') {
                throw new FormulaException(sprintf(
                    'La función %s solo acepta una variable (A, B, C...).',
                    strtoupper($funcion)
                ));
            }

            $this->saltarEspacios();
            if ($this->peek() !== ')') {
                throw new FormulaException(sprintf(
                    'Falta cerrar la función %s(...).',
                    strtoupper($funcion)
                ));
            }

            $this->posicion++;

            return [
                'tipo' => 'agregacion',
                'funcion' => $funcion,
                'clave' => $argumento['clave'],
            ];
        }

        return [
            'tipo' => 'slot',
            'clave' => strtoupper($identificador),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function slotsDelArbol(array $nodo): array
    {
        $slots = [];

        $recorrer = function (array $nodo) use (&$recorrer, &$slots): void {
            switch ($nodo['tipo']) {
                case 'slot':
                case 'agregacion':
                    $slots[$nodo['clave']] = true;
                    break;
                case 'operacion':
                    $recorrer($nodo['izquierda']);
                    $recorrer($nodo['derecha']);
                    break;
                case 'unario':
                    $recorrer($nodo['operando']);
                    break;
            }
        };

        $recorrer($nodo);

        return array_keys($slots);
    }

    private function saltarEspacios(): void
    {
        while ($this->posicion < $this->longitud
            && ctype_space($this->expresion[$this->posicion])) {
            $this->posicion++;
        }
    }

    private function peek(): ?string
    {
        return $this->posicion < $this->longitud
            ? $this->expresion[$this->posicion]
            : null;
    }
}
