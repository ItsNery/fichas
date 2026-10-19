<?php

namespace App\Services\Formula;

/**
 * Sistema de tipos para las variables de los indicadores.
 *
 * Cada variable hereda el `tipo_dato` de su indicador:
 *
 *   absoluto   -> cantidad (N)          ej. viviendas, población
 *   porcentaje -> razón en %            ej. 27.03
 *   tasa       -> razón por base        ej. 15 por cada 10 mil habitantes
 *   indice     -> razón indexada        ej. 104.2
 *
 * A partir de ahí se define qué operaciones son matemáticamente correctas.
 */
class FormulaTypeSystem
{
    public const ABSOLUTO = 'absoluto';

    public const PORCENTAJE = 'porcentaje';

    public const TASA = 'tasa';

    public const INDICE = 'indice';

    /** Resultado de un cociente entre magnitudes comparables. */
    public const RAZON = 'razon';

    /** Constante literal dentro de la expresión (100, 2, 0.5...). */
    public const NUMERO = 'numero';

    /** Tipo desconocido: no se valida. */
    public const DESCONOCIDO = 'desconocido';

    public static function esRazon(string $tipo): bool
    {
        return in_array($tipo, [self::PORCENTAJE, self::TASA, self::INDICE, self::RAZON], true);
    }

    public static function esCuenta(string $tipo): bool
    {
        return $tipo === self::ABSOLUTO;
    }

    public static function etiqueta(string $tipo): string
    {
        return match ($tipo) {
            self::ABSOLUTO => 'Absoluto',
            self::PORCENTAJE => 'Porcentaje',
            self::TASA => 'Tasa',
            self::INDICE => 'Índice',
            self::RAZON => 'Razón',
            self::NUMERO => 'Constante',
            self::DESCONOCIDO => 'Sin tipo',
            default => ucfirst($tipo),
        };
    }

    /**
     * ¿La operación es matemáticamente válida para esos tipos?
     */
    public static function compatible(string $operador, string $izquierda, string $derecha): bool
    {
        if ($izquierda === self::DESCONOCIDO || $derecha === self::DESCONOCIDO) {
            return true;
        }

        if ($izquierda === self::NUMERO || $derecha === self::NUMERO) {
            return true;
        }

        return match ($operador) {
            '+', '-' => $izquierda === $derecha,
            '*' => ! (self::esCuenta($izquierda) && self::esCuenta($derecha))
                && ! (self::esRazon($izquierda) && self::esRazon($derecha)),
            '/' => (self::esCuenta($izquierda) && self::esCuenta($derecha))
                || (self::esRazon($izquierda) && self::esRazon($derecha) && $izquierda === $derecha),
            default => true,
        };
    }

    /**
     * Explicación de por qué la operación no es válida.
     */
    public static function explicar(string $operador, string $izquierda, string $derecha): string
    {
        $a = self::etiqueta($izquierda);
        $b = self::etiqueta($derecha);

        return match ($operador) {
            '+', '-' => sprintf(
                'No se puede %s valores de tipo "%s" y "%s".',
                $operador === '+' ? 'sumar' : 'restar',
                $a,
                $b
            ),
            '*' => match (true) {
                self::esCuenta($izquierda) && self::esCuenta($derecha) => sprintf(
                    'No se puede multiplicar "%s" por "%s": el resultado sería una cantidad al cuadrado.',
                    $a,
                    $b
                ),
                self::esRazon($izquierda) && self::esRazon($derecha) => sprintf(
                    'No se puede multiplicar "%s" por "%s": el resultado sería una razón al cuadrado.',
                    $a,
                    $b
                ),
                default => sprintf('No se puede multiplicar "%s" por "%s".', $a, $b),
            },
            '/' => match (true) {
                self::esCuenta($izquierda) && self::esRazon($derecha) => sprintf(
                    'No se puede dividir "%s" entre "%s".',
                    $a,
                    $b
                ),
                self::esRazon($izquierda) && self::esCuenta($derecha) => sprintf(
                    'No se puede dividir "%s" entre "%s".',
                    $a,
                    $b
                ),
                self::esRazon($izquierda) && self::esRazon($derecha) => sprintf(
                    'No se puede dividir "%s" entre "%s": las razones deben ser del mismo tipo.',
                    $a,
                    $b
                ),
                default => sprintf('No se puede dividir "%s" entre "%s".', $a, $b),
            },
            default => sprintf('Operación "%s" no válida entre "%s" y "%s".', $operador, $a, $b),
        };
    }

    /**
     * Tipo resultante de la operación.
     *
     * @param  float|null  $constante  valor literal si el operando es una constante
     */
    public static function resultado(
        string $operador,
        string $izquierda,
        string $derecha,
        ?float $constante = null
    ): string {
        if ($izquierda === self::DESCONOCIDO || $derecha === self::DESCONOCIDO) {
            return self::DESCONOCIDO;
        }

        if ($operador === '+' || $operador === '-') {
            return $izquierda === self::NUMERO ? $derecha : $izquierda;
        }

        if ($operador === '*') {
            if ($izquierda === self::NUMERO) {
                return self::resultadoMultiplicacion($derecha, $constante);
            }

            if ($derecha === self::NUMERO) {
                return self::resultadoMultiplicacion($izquierda, $constante);
            }

            // Cuenta × razón = cantidad (con la escala de la razón).
            return self::esCuenta($izquierda) ? self::ABSOLUTO : self::NUMERO;
        }

        if ($operador === '/') {
            if ($izquierda === self::NUMERO) {
                return $derecha;
            }

            if ($derecha === self::NUMERO) {
                return $izquierda;
            }

            return self::RAZON;
        }

        return self::NUMERO;
    }

    private static function resultadoMultiplicacion(string $tipo, ?float $constante): string
    {
        if (! self::esRazon($tipo)) {
            return $tipo;
        }

        // Una razón multiplicada por 100 se expresa como porcentaje.
        if ($constante !== null && abs($constante - 100.0) < 0.000001) {
            return self::PORCENTAJE;
        }

        return self::RAZON;
    }

    /**
     * ¿El tipo calculado por la fórmula es compatible con el tipo declarado
     * por el indicador de la variable destino?
     */
    public static function destinoCompatible(string $resultado, string $destino): bool
    {
        if ($resultado === self::DESCONOCIDO || $resultado === self::NUMERO) {
            return true;
        }

        if ($destino === self::DESCONOCIDO || $destino === '') {
            return true;
        }

        if ($resultado === $destino) {
            return true;
        }

        // Una razón sin escalar no es un porcentaje.
        return false;
    }

    public static function explicarDestino(string $resultado, string $destino): string
    {
        return sprintf(
            'El resultado de la fórmula es de tipo "%s" pero la variable destino declara "%s".',
            self::etiqueta($resultado),
            self::etiqueta($destino)
        );
    }
}
