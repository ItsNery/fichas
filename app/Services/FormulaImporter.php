<?php

namespace App\Services;

use App\Models\Formula;
use App\Models\Variable;
use Illuminate\Support\Str;

/**
 * Importa al catálogo de fórmulas las fórmulas que ya existen en variables
 * construidas (variables.formula_tipo / variables.formula_config).
 *
 * No modifica las variables de origen: solo crea registros en `formulas`.
 */
class FormulaImporter
{
    public const MOTIVO_INCOMPATIBLE = 'incompatible';

    public const MOTIVO_DUPLICADA = 'duplicada';

    /**
     * @return array{creadas: array<int, Formula>, omitidas: array<int, array{nombre: string, motivo: string, detalle: string}>}
     */
    public function importar(string $ambito = Formula::AMBITO_MUNICIPAL): array
    {
        $variables = Variable::with('indicador')
            ->where('es_construida', true)
            ->whereNotNull('formula_tipo')
            ->whereNotNull('formula_config')
            ->orderBy('id')
            ->get();

        $grupos = $this->agrupar($variables);

        $creadas = [];
        $omitidas = [];
        $nombresUsados = [];

        foreach ($grupos as $grupo) {
            $expresion = $this->construirExpresion($grupo);

            if ($expresion === null) {
                $omitidas[] = [
                    'nombre' => $grupo['variables'][0]->nombre_amigable,
                    'motivo' => self::MOTIVO_INCOMPATIBLE,
                    'detalle' => 'El tipo de fórmula "' . $grupo['tipo'] . '" requiere comparar años '
                        . 'y no puede expresarse con el motor de fórmulas actual.',
                ];
                continue;
            }

            $nombreTecnico = $this->nombreTecnico($grupo, $expresion);
            $existente = Formula::where('nombre_tecnico', $nombreTecnico)->first();

            if ($existente && $existente->expresion === $expresion) {
                $omitidas[] = [
                    'nombre' => $grupo['variables'][0]->nombre_amigable,
                    'motivo' => self::MOTIVO_DUPLICADA,
                    'detalle' => 'Ya existe una fórmula importada con la misma definición.',
                ];
                continue;
            }

            if ($existente) {
                $nombreTecnico = $this->nombreTecnicoLibre($nombreTecnico);
            }

            $nombresUsados[] = $nombreTecnico;

            $creadas[] = Formula::create([
                'nombre' => $this->construirNombre($grupo),
                'nombre_tecnico' => $nombreTecnico,
                'descripcion' => $this->construirDescripcion($grupo),
                'ambito' => $ambito,
                'expresion' => $expresion,
                'slots' => $this->construirSlots($grupo),
                'activa' => true,
                'usuario_creacion_id' => auth()->id(),
            ]);
        }

        return ['creadas' => $creadas, 'omitidas' => $omitidas];
    }

    /**
     * Agrupa las variables construidas que comparten la misma definición.
     *
     * @param  \Illuminate\Support\Collection<int, Variable>  $variables
     * @return array<int, array{tipo: string, config: array, variables: array<int, Variable>}>
     */
    private function agrupar($variables): array
    {
        $grupos = [];

        foreach ($variables as $variable) {
            $config = (array) $variable->formula_config;
            $firma = $this->firma($variable->formula_tipo, $config);

            $grupos[$firma] ??= [
                'tipo' => $variable->formula_tipo,
                'config' => $config,
                'variables' => [],
            ];

            $grupos[$firma]['variables'][] = $variable;
        }

        return $grupos;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function firma(string $tipo, array $config): string
    {
        $referencias = collect($config)
            ->map(fn ($valor, $clave) => is_array($valor)
                ? $clave . '[' . implode('-', $valor) . ']'
                : $clave . '=' . (string) $valor)
            ->sort()
            ->implode('|');

        return $tipo . '#' . $referencias;
    }

    /**
     * @param  array{tipo: string, config: array, variables: array<int, Variable>}  $grupo
     */
    private function construirExpresion(array $grupo): ?string
    {
        $config = $grupo['config'];

        return match ($grupo['tipo']) {
            'division' => $this->expresionDivision($config),
            'sumatoria' => $this->expresionSumatoria($config),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function expresionDivision(array $config): ?string
    {
        if (empty($config['numerador_variable_id']) || empty($config['denominador_variable_id'])) {
            return null;
        }

        $multiplicador = (float) ($config['multiplicador'] ?? 1);
        $expresion = 'SUM(A) / SUM(B)';

        if (abs($multiplicador - 1.0) > 0.000001) {
            $expresion .= ' * ' . rtrim(rtrim(number_format($multiplicador, 6, '.', ''), '0'), '.');
        }

        return $expresion;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function expresionSumatoria(array $config): ?string
    {
        $ids = collect($config['variable_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $partes = [];
        $letras = $this->letras($ids->count());

        foreach ($ids->values() as $indice => $id) {
            $partes[] = 'SUM(' . $letras[$indice] . ')';
        }

        return implode(' + ', $partes);
    }

    /**
     * @return array<int, string>
     */
    private function letras(int $cantidad): array
    {
        $letras = [];

        for ($i = 0; $i < $cantidad; $i++) {
            $letras[] = chr(65 + $i);
        }

        return $letras;
    }

    /**
     * @param  array{tipo: string, config: array, variables: array<int, Variable>}  $grupo
     * @return array<int, array{clave: string, nombre: string, variable_id: int}>
     */
    private function construirSlots(array $grupo): array
    {
        $slots = [];

        foreach ($grupo['variables'] as $variable) {
            $referencias = $this->referenciasDe($grupo, $variable);

            foreach ($referencias as $referencia) {
                $slots[] = [
                    'clave' => $referencia['clave'],
                    'nombre' => $referencia['nombre'],
                    'variable_id' => $referencia['variable_id'],
                ];
            }
        }

        return $slots;
    }

    /**
     * @param  array{tipo: string, config: array, variables: array<int, Variable>}  $grupo
     * @return array<int, array{clave: string, nombre: string, variable_id: int}>
     */
    private function referenciasDe(array $grupo, Variable $variable): array
    {
        $config = $grupo['config'];
        $referencias = [];

        if ($grupo['tipo'] === 'division') {
            $numerador = Variable::find($config['numerador_variable_id'] ?? 0);
            $denominador = Variable::find($config['denominador_variable_id'] ?? 0);

            if ($numerador) {
                $referencias[] = [
                    'clave' => 'A',
                    'nombre' => $numerador->nombre_amigable,
                    'variable_id' => $numerador->id,
                ];
            }

            if ($denominador) {
                $referencias[] = [
                    'clave' => 'B',
                    'nombre' => $denominador->nombre_amigable,
                    'variable_id' => $denominador->id,
                ];
            }

            return $referencias;
        }

        $ids = collect($config['variable_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->values();
        $letras = $this->letras($ids->count());

        foreach ($ids as $indice => $id) {
            $origen = Variable::find($id);

            if (! $origen) {
                continue;
            }

            $referencias[] = [
                'clave' => $letras[$indice],
                'nombre' => $origen->nombre_amigable,
                'variable_id' => $origen->id,
            ];
        }

        return $referencias;
    }

    /**
     * @param  array{tipo: string, config: array, variables: array<int, Variable>}  $grupo
     */
    private function construirNombre(array $grupo): string
    {
        $primera = $grupo['variables'][0]->nombre_amigable;

        return match ($grupo['tipo']) {
            'division' => 'División: ' . $primera,
            'sumatoria' => 'Sumatoria: ' . $primera,
            default => ucfirst($grupo['tipo']) . ': ' . $primera,
        };
    }

    /**
     * @param  array{tipo: string, config: array, variables: array<int, Variable>}  $grupo
     */
    private function construirDescripcion(array $grupo): string
    {
        $nombres = collect($grupo['variables'])
            ->map(fn (Variable $variable) => '«' . $variable->nombre_amigable . '»')
            ->implode(', ');

        return 'Importada desde la variable construida ' . $nombres
            . '. Tipo original: ' . $grupo['tipo'] . '.';
    }

    /**
     * Nombre técnico único basado en las variables de origen (no en la expresión,
     * que puede ser idéntica para definiciones distintas).
     *
     * @param  array{tipo: string, config: array, variables: array<int, Variable>}  $grupo
     */
    private function nombreTecnico(array $grupo, string $expresion): string
    {
        $tecnicos = [];

        foreach ($this->referenciasDe($grupo, $grupo['variables'][0]) as $referencia) {
            $variable = Variable::find($referencia['variable_id']);
            $tecnicos[] = $variable?->nombre_tecnico ?? ('var' . $referencia['variable_id']);
        }

        $multiplicador = (float) ($grupo['config']['multiplicador'] ?? 1);
        $sufijo = abs($multiplicador - 1.0) > 0.000001 ? '_x' . rtrim(rtrim(number_format($multiplicador, 4, '.', ''), '0'), '.') : '';

        $base = Str::slug(Str::ascii($grupo['tipo'] . '_' . implode('_', $tecnicos) . $sufijo), '_');
        $base = trim(Str::limit($base, 120, ''), '_') ?: 'formula_importada';

        return $base;
    }

    private function nombreTecnicoLibre(string $base): string
    {
        $nombre = $base;
        $sufijo = 2;

        while (Formula::where('nombre_tecnico', $nombre)->exists()) {
            $nombre = $base . '_' . $sufijo++;
        }

        return $nombre;
    }
}
