<?php

namespace App\Services\Formula;

/**
 * Valida que las dimensiones de los operandos sean compatibles usando el
 * sistema de tipos (FormulaTypeSystem) y determina el tipo del resultado.
 */
class FormulaCompatibilityChecker
{
    /**
     * @param  array<string, string>  $tiposPorSlot  clave (A, B...) => tipo de dato
     * @return array{errores: array<int, string>, tipoResultado: string}
     */
    public function validar(array $arbol, array $tiposPorSlot): array
    {
        $errores = [];

        $resultado = $this->revisar($arbol, $tiposPorSlot, $errores);

        return [
            'errores' => $errores,
            'tipoResultado' => $resultado['tipo'],
        ];
    }

    /**
     * @param  array<int, string>  $errores
     * @return array{tipo: string, constante: ?float, slots: array<int, string>}
     */
    private function revisar(array $nodo, array $tiposPorSlot, array &$errores): array
    {
        switch ($nodo['tipo']) {
            case 'numero':
                return ['tipo' => FormulaTypeSystem::NUMERO, 'constante' => (float) $nodo['valor'], 'slots' => []];

            case 'slot':
            case 'agregacion':
                return [
                    'tipo' => $tiposPorSlot[$nodo['clave']] ?? FormulaTypeSystem::DESCONOCIDO,
                    'constante' => null,
                    'slots' => [$nodo['clave']],
                ];

            case 'unario':
                $interno = $this->revisar($nodo['operando'], $tiposPorSlot, $errores);

                return [
                    'tipo' => $interno['tipo'],
                    'constante' => $interno['constante'] === null ? null : -$interno['constante'],
                    'slots' => $interno['slots'],
                ];
        }

        $izquierda = $this->revisar($nodo['izquierda'], $tiposPorSlot, $errores);
        $derecha = $this->revisar($nodo['derecha'], $tiposPorSlot, $errores);
        $slots = array_values(array_unique(array_merge($izquierda['slots'], $derecha['slots'])));

        $operador = $nodo['operador'];

        if (! FormulaTypeSystem::compatible($operador, $izquierda['tipo'], $derecha['tipo'])) {
            $errores[] = FormulaTypeSystem::explicar($operador, $izquierda['tipo'], $derecha['tipo'])
                . ' Revisa las variables asignadas a ' . $this->mencionSlots($slots) . '.';

            return ['tipo' => FormulaTypeSystem::DESCONOCIDO, 'constante' => null, 'slots' => $slots];
        }

        $constante = null;
        if ($operador === '*') {
            if ($izquierda['tipo'] === FormulaTypeSystem::NUMERO) {
                $constante = $izquierda['constante'];
            } elseif ($derecha['tipo'] === FormulaTypeSystem::NUMERO) {
                $constante = $derecha['constante'];
            }
        }

        return [
            'tipo' => FormulaTypeSystem::resultado($operador, $izquierda['tipo'], $derecha['tipo'], $constante),
            'constante' => null,
            'slots' => $slots,
        ];
    }

    /**
     * @param  array<int, string>  $slots
     */
    private function mencionSlots(array $slots): string
    {
        if (empty($slots)) {
            return 'las variables de la fórmula';
        }

        return 'los slots ' . implode(', ', $slots);
    }
}
