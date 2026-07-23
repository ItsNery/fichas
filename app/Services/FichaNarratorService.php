<?php

namespace App\Services;

use App\Models\Municipio;

class FichaNarratorService
{
    /**
     * Procesa la plantilla de texto inyectando las variables dinámicas básicas e inteligentes.
     *
     * @param string $plantilla
     * @param Municipio $municipio
     * @param array $datos
     * @return string
     */
    public static function procesar(?string $plantilla, Municipio $municipio, ?array $datos): string
    {
        if (!$plantilla) {
            return '';
        }

        if (!$datos) {
            // Reemplazos mínimos de seguridad si no hay datos históricos
            return str_replace('{municipio}', $municipio->nombre, $plantilla);
        }

        $polaridad = self::normalizarPolaridad($datos['polaridad'] ?? 'neutro');
        $valorActualNum = $datos['total'] ?? 0;

        // Generar textos dinámicos inteligentes
        $tendenciaTxt = self::generarTendencia($datos['tendencia'] ?? [], $polaridad);
        $compEstatalTxt = self::generarComparativa($valorActualNum, $datos['promedio_estatal'] ?? null, 'estatal', $polaridad);
        $compRegionalTxt = self::generarComparativa($valorActualNum, $datos['promedio_macrorregional'] ?? null, 'macrorregional', $polaridad);
        $rankingTxt = self::generarContextoRanking($datos['ranking'] ?? null, $polaridad);

        $rankingBasico = isset($datos['ranking'])
            ? "lugar {$datos['ranking']['posicion']} de {$datos['ranking']['total_municipios']}"
            : 'N/D';

        $promedioEstatalVal = isset($datos['promedio_estatal']) ? number_format($datos['promedio_estatal'], 1) : 'N/D';
        $promedioRegionalVal = isset($datos['promedio_macrorregional']) ? number_format($datos['promedio_macrorregional'], 1) : 'N/D';

        // Reemplazos finales
        $reemplazos = [
            '{municipio}' => $municipio->nombre,
            '{anio}' => $datos['anio'] ?? '',
            '{valor}' => $datos['valor_actual'] ?? '',
            '{ranking}' => $rankingBasico,
            '{promedio_estatal}' => $promedioEstatalVal,
            '{promedio_macrorregional}' => $promedioRegionalVal,
            '{tendencia_historica}' => $tendenciaTxt,
            '{comparativa_estatal}' => $compEstatalTxt,
            '{comparativa_regional}' => $compRegionalTxt,
            '{contexto_ranking}' => $rankingTxt,
        ];

        // Tags dinámicos por variable
        if (isset($datos['variables']) && is_array($datos['variables'])) {
            foreach ($datos['variables'] as $var) {
                $slug = \Illuminate\Support\Str::slug($var['nombre'], '_');
                $reemplazos["{{$slug}_valor}"] = "<strong>" . number_format($var['valor']) . "</strong>";
                $reemplazos["{{$slug}_nombre}"] = $var['nombre'];
            }
        }

        return str_replace(array_keys($reemplazos), array_values($reemplazos), $plantilla);
    }

    private static function normalizarPolaridad(string $polaridad): string
    {
        return match ($polaridad) {
            'asendente', 'alta_mejor' => 'alta_mejor',
            'descendente', 'baja_mejor' => 'baja_mejor',
            default => 'neutro',
        };
    }

    /**
     * Analiza el historial de tendencia para redactar el progreso histórico en lenguaje natural.
     */
    protected static function generarTendencia(array $tendencia, string $polaridad): string
    {
        $count = count($tendencia);
        if ($count < 2) {
            return 'con estabilidad en su registro reciente';
        }

        // Ordenar tendencia por año para asegurar orden cronológico
        usort($tendencia, fn($a, $b) => $a['anio'] <=> $b['anio']);

        $oldest = $tendencia[0];
        $newest = $tendencia[$count - 1];

        $diffVal = $newest['valor'] - $oldest['valor'];
        $oldVal = (float)$oldest['valor'];

        if ($oldVal === 0.0) {
            return "con una variación registrada desde " . $oldest['anio'];
        }

        $pctChange = ($diffVal / $oldVal) * 100;
        $absPct = number_format(abs($pctChange), 1) . '%';
        $periodo = "desde " . $oldest['anio'];

        if (abs($pctChange) < 0.5) {
            return "manteniéndose estable (con una variación mínima del {$absPct}) {$periodo}";
        }

        if ($pctChange > 0) {
            // El valor subió
            if ($polaridad === 'baja_mejor') {
                // Sube indicador negativo (ej. pobreza): Favorable = falso
                return "registrando un incremento desfavorable del {$absPct} {$periodo}";
            } elseif ($polaridad === 'alta_mejor') {
                // Sube indicador positivo (ej. alfabetismo): Favorable = verdadero
                return "mostrando un crecimiento acumulado del {$absPct} {$periodo}";
            }
            return "registrando un incremento del {$absPct} {$periodo}";
        } else {
            // El valor bajó
            if ($polaridad === 'baja_mejor') {
                // Baja indicador negativo: Favorable = verdadero
                return "logrando una reducción favorable del {$absPct} {$periodo}";
            } elseif ($polaridad === 'alta_mejor') {
                // Baja indicador positivo: Favorable = falso
                return "registrando un retroceso del {$absPct} {$periodo}";
            }
            return "registrando una reducción del {$absPct} {$periodo}";
        }
    }

    /**
     * Calcula la desviación relativa respecto a una media y genera el texto comparativo.
     */
    protected static function generarComparativa(float $valorActual, ?float $promedio, string $tipo, string $polaridad): string
    {
        if ($promedio === null || $promedio === 0.0) {
            return '';
        }

        $diff = $valorActual - $promedio;
        if (abs($diff) < 0.05) {
            return "siendo prácticamente igual al promedio {$tipo}";
        }

        $pctDiff = ($diff / $promedio) * 100;
        $absPct = number_format(abs($pctDiff), 1) . '%';

        // Si la diferencia es mayor al 100%, la expresamos en "veces"
        if (abs($pctDiff) >= 100.0) {
            $veces = $valorActual / $promedio;
            if ($veces > 1.0) {
                if ($polaridad === 'alta_mejor') {
                    return "siendo " . number_format($veces, 1) . " veces superior al promedio {$tipo}";
                } elseif ($polaridad === 'baja_mejor') {
                    return "siendo " . number_format($veces, 1) . " veces superior al promedio {$tipo} (desfavorable)";
                } else {
                    return "lo que representa " . number_format($veces, 1) . " veces la media {$tipo}";
                }
            } else {
                $fraccion = $promedio / ($valorActual ?: 1);
                return "lo que equivale a apenas la " . number_format($fraccion, 0) . "ª parte del promedio {$tipo}";
            }
        }

        // Expresión en porcentajes
        if ($diff > 0) {
            if ($polaridad === 'alta_mejor') {
                return "lo que posiciona al municipio un {$absPct} por encima del promedio {$tipo}";
            } elseif ($polaridad === 'baja_mejor') {
                return "lo que representa un {$absPct} más que la media {$tipo}";
            }
            return "ubicándose un {$absPct} por encima del promedio {$tipo}";
        } else {
            if ($polaridad === 'alta_mejor') {
                return "lo que representa un rezago del {$absPct} respecto al promedio {$tipo}";
            } elseif ($polaridad === 'baja_mejor') {
                return "lo que sitúa al municipio un {$absPct} por debajo de la media {$tipo}";
            }
            return "ubicándose un {$absPct} por debajo del promedio {$tipo}";
        }
    }

    /**
     * Clasifica cualitativamente el desempeño según el ranking del municipio.
     */
    protected static function generarContextoRanking(?array $ranking, string $polaridad): string
    {
        if ($ranking === null || !isset($ranking['posicion']) || !isset($ranking['total_municipios'])) {
            return '';
        }

        $pos = (int)$ranking['posicion'];
        $total = (int)$ranking['total_municipios'];

        if ($total === 0) {
            return '';
        }

        $percentil = ($pos / $total) * 100;

        if ($polaridad === 'neutro') {
            return "en la posición {$pos} de {$total} municipios a nivel estatal";
        }

        if ($polaridad === 'alta_mejor') {
            // Posiciones iniciales (1, 2, 3...) tienen valores altos, que es lo mejor
            if ($percentil <= 20.0) {
                return "dentro del 20% de municipios con mejor desempeño a nivel estatal";
            } elseif ($percentil <= 40.0) {
                return "en el segundo quintil de mejor desempeño en el estado";
            } elseif ($percentil <= 60.0) {
                return "en una posición intermedia a nivel estatal";
            } elseif ($percentil <= 80.0) {
                return "en el cuarto quintil (por debajo del promedio estatal)";
            } else {
                return "dentro del grupo de municipios con mayor rezago en el estado";
            }
        } else {
            // Posiciones iniciales tienen valores altos, lo cual es lo peor para indicadores negativos (ej. pobreza)
            if ($percentil <= 20.0) {
                return "dentro del grupo de municipios con mayor rezago a nivel estatal";
            } elseif ($percentil <= 40.0) {
                return "en el segundo quintil de mayor vulnerabilidad en el estado";
            } elseif ($percentil <= 60.0) {
                return "en una posición intermedia a nivel estatal";
            } elseif ($percentil <= 80.0) {
                return "en una posición relativamente favorable a nivel estatal";
            } else {
                return "dentro del 20% de municipios con mejor desempeño en el estado";
            }
        }
    }
}
