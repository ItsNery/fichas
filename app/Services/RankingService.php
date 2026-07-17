<?php

namespace App\Services;

use App\Models\ConfiguracionFicha;
use App\Models\DatoHistorico;
use App\Models\Municipio;
use App\Models\Variable;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function getMunicipalityRanking($variableIds, int $municipioId, $anio): array
    {
        $variableIds = $this->normalizeVariableIds($variableIds);
        $anio = (int) $anio;
        $valores = DatoHistorico::whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->select('municipio_id', DB::raw('SUM(valor) as total'))
            ->groupBy('municipio_id')
            ->orderByDesc('total')
            ->get();

        $posicion = $valores->search(fn($v) => $v->municipio_id === $municipioId);

        return [
            'posicion' => $posicion !== false ? $posicion + 1 : 'N/D',
            'total_municipios' => $valores->count(),
        ];
    }

    public function getMunicipalityRankingInMemory(
        FichaDataStore $dataStore,
        $variableIds,
        int $municipioId,
        $anio
    ): array {
        $variableIds = $this->normalizeVariableIds($variableIds);
        $anio = (int) $anio;
        $valores = $dataStore->globalData
            ->whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->groupBy('municipio_id')
            ->map(fn($rows) => (float) $rows->sum('valor'))
            ->sortDesc();

        $municipioIds = $valores->keys()->map(fn($id) => (int) $id)->values();
        $posicion = $municipioIds->search($municipioId);

        return [
            'posicion' => $posicion !== false ? $posicion + 1 : 'N/D',
            'total_municipios' => $valores->count(),
        ];
    }

    public function getStateAverage($variableIds, $anio, string $operation = 'avg'): float|int
    {
        $variableIds = $this->normalizeVariableIds($variableIds);
        $anio = (int) $anio;
        $operation = $this->normalizeOperation($operation);
        $result = DatoHistorico::whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->select(DB::raw("$operation(valor) as valor"))
            ->first();

        return $result?->valor ?? 0;
    }

    public function getStateAverageInMemory(
        FichaDataStore $dataStore,
        $variableIds,
        $anio,
        string $operation = 'avg'
    ): float|int {
        $variableIds = $this->normalizeVariableIds($variableIds);
        $anio = (int) $anio;
        $valores = $dataStore->globalData
            ->whereIn('variable_id', $variableIds)
            ->where('anio', $anio)
            ->pluck('valor')
            ->filter(fn($valor) => $valor !== null);

        return $this->aggregate($valores, $operation);
    }

    public function getMacrorregionalAverage($variableIds, Municipio $municipio, $anio, string $operation = 'avg'): float|int
    {
        $variableIds = $this->normalizeVariableIds($variableIds);
        $anio = (int) $anio;
        $macrorregionId = $municipio->microrregion?->macrorregion_id;
        if (!$macrorregionId) {
            return 0;
        }

        $operation = $this->normalizeOperation($operation);
        $result = DatoHistorico::whereIn('dato_historicos.variable_id', $variableIds)
            ->where('dato_historicos.anio', $anio)
            ->join('municipios', 'dato_historicos.municipio_id', '=', 'municipios.id')
            ->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
            ->where('microrregions.macrorregion_id', $macrorregionId)
            ->select(DB::raw("$operation(dato_historicos.valor) as valor"))
            ->first();

        return $result?->valor ?? 0;
    }

    public function getMacrorregionalAverageInMemory(
        FichaDataStore $dataStore,
        $variableIds,
        $municipioIds,
        $anio,
        string $operation = 'avg'
    ): float|int {
        $variableIds = $this->normalizeVariableIds($variableIds);
        $anio = (int) $anio;
        $municipioIds = collect($municipioIds)->map(fn($id) => (int) $id)->all();
        if (!$municipioIds) {
            return 0;
        }

        $valores = $dataStore->globalData
            ->whereIn('variable_id', $variableIds)
            ->whereIn('municipio_id', $municipioIds)
            ->where('anio', $anio)
            ->pluck('valor')
            ->filter(fn($valor) => $valor !== null);

        return $this->aggregate($valores, $operation);
    }

    public function getSimilitud(Municipio $municipio, $configKeyOrId): array
    {
        if (!is_numeric($configKeyOrId) && strtolower($configKeyOrId) === 'superficie') {
            return $this->calcularSimilitudSuperficie($municipio);
        }

        $variable = null;
        $isPresupuesto = false;

        if (is_numeric($configKeyOrId)) {
            $config = ConfiguracionFicha::with(['variables', 'indicador.variables'])->find($configKeyOrId);
            if ($config) {
                $variable = $config->variables->first() ?? $config->indicador->variables->first();
            }
        } else {
            $result = $this->resolveHeroKey($configKeyOrId, $municipio);
            if (isset($result['presupuesto'])) {
                $isPresupuesto = true;
            } else {
                $variable = $result['variable'] ?? null;
            }
        }

        if (!$variable && !$isPresupuesto) {
            return [
                'success' => false,
                'message' => 'No se encontró la variable asociada a esta métrica.'
            ];
        }

        if ($isPresupuesto) {
            return $this->calcularSimilitudPresupuesto($municipio);
        }

        return $this->calcularSimilitudVariable($municipio, $variable, $configKeyOrId, $config ?? null);
    }

    public function cargarComparativa(string $slug1, string $slug2): array
    {
        $municipio1 = Municipio::where('slug', $slug1)->firstOrFail();
        $municipio2 = Municipio::where('slug', $slug2)->firstOrFail();

        $municipio1->load('microrregion.macrorregion');
        $municipio2->load('microrregion.macrorregion');

        $hero1 = app(FichaProfilerService::class)->getHeroStats($municipio1);
        $hero2 = app(FichaProfilerService::class)->getHeroStats($municipio2);

        $configuraciones = ConfiguracionFicha::with(['indicador.variables', 'indicador.tematica.dimension', 'variables'])
            ->where('activo', true)
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get();

        $allVariableIds = FichaDataStore::extractVariableIds($configuraciones);
        $globalData = DB::table('dato_historicos')
            ->whereIn('variable_id', $allVariableIds)
            ->select('municipio_id', 'variable_id', 'anio', 'valor')
            ->get();

        return [
            'municipio1' => $municipio1,
            'municipio2' => $municipio2,
            'hero1' => $hero1,
            'hero2' => $hero2,
            'configuraciones' => $configuraciones,
            'dataStore1' => new FichaDataStore($municipio1, $allVariableIds, $globalData),
            'dataStore2' => new FichaDataStore($municipio2, $allVariableIds, $globalData),
            'comparativa' => [],
        ];
    }

    public function getSimilaresPorPoblacion(Municipio $municipio, float $poblacionTotal)
    {
        $varPob = Variable::where('nombre_amigable', 'Poblacion total')
            ->whereHas('indicador', fn($q) => $q->where('nombre_amigable', 'Población total segun sexo'))
            ->first();

        if (!$varPob || $poblacionTotal <= 0) {
            return collect();
        }

        $macrorregionId = $municipio->microrregion?->macrorregion_id;

        $query = Municipio::where('municipios.id', '!=', $municipio->id)
            ->join('dato_historicos', 'municipios.id', '=', 'dato_historicos.municipio_id')
            ->where('dato_historicos.variable_id', $varPob->id)
            ->where('dato_historicos.anio', fn($q) => $q->selectRaw('max(d2.anio)')
                ->from('dato_historicos as d2')
                ->whereColumn('d2.municipio_id', 'municipios.id')
                ->where('d2.variable_id', $varPob->id));

        if ($macrorregionId) {
            $query->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
                ->where('microrregions.macrorregion_id', $macrorregionId);
        }

        return $query->select('municipios.*', 'dato_historicos.valor as poblacion_valor')
            ->orderByRaw('ABS(dato_historicos.valor - ?) ASC', [$poblacionTotal])
            ->limit(4)
            ->get();
    }

    public function getSimilaresPorRegion(Municipio $municipio)
    {
        if (!$municipio->microrregion_id) {
            return collect();
        }

        return Municipio::with('microrregion')
            ->where('id', '!=', $municipio->id)
            ->where('microrregion_id', $municipio->microrregion_id)
            ->limit(4)
            ->get();
    }

    private function resolveHeroKey(string $key, Municipio $municipio): array
    {
        return match (strtolower($key)) {
            'poblacion' => ['variable' => Variable::where('nombre_amigable', 'Población total')
                ->whereHas('indicador', fn($q) => $q->where('nombre_amigable', 'Población total según sexo'))
                ->first()],
            'pea' => ['variable' => Variable::where('nombre_amigable', 'Población Económicamente Activa (PEA)')->first()],
            'pobreza' => ['variable' => Variable::where('nombre_amigable', 'Porcentaje de población en situación de pobreza')->first()],
            'marginacion' => ['variable' => Variable::where('nombre_amigable', 'Grado de Marginación')->first()],
            'presupuesto' => ['presupuesto' => true],
            default => ['variable' => null],
        };
    }

    private function calcularSimilitudPresupuesto(Municipio $municipio): array
    {
        $varsPresupuestoIds = Variable::whereIn('nombre_amigable', ['FORTAMUN APROBADO', 'FAISMUN APROBADO'])->pluck('id');
        $anio = DatoHistorico::whereIn('variable_id', $varsPresupuestoIds)
            ->where('municipio_id', $municipio->id)
            ->max('anio');

        if (!$anio) {
            return [
                'success' => false,
                'message' => 'No hay datos históricos disponibles para el presupuesto.'
            ];
        }

        $valorActual = DatoHistorico::whereIn('variable_id', $varsPresupuestoIds)
            ->where('municipio_id', $municipio->id)
            ->where('anio', $anio)
            ->sum('valor');

        $macrorregionId = $municipio->microrregion?->macrorregion_id;

        $query = Municipio::where('municipios.id', '!=', $municipio->id)
            ->join('dato_historicos', 'municipios.id', '=', 'dato_historicos.municipio_id')
            ->whereIn('dato_historicos.variable_id', $varsPresupuestoIds)
            ->where('dato_historicos.anio', $anio);

        if ($macrorregionId) {
            $query->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
                ->where('microrregions.macrorregion_id', $macrorregionId);
        }

        $similares = $query->select('municipios.nombre', 'municipios.slug', DB::raw('SUM(dato_historicos.valor) as total_valor'))
            ->groupBy('municipios.id', 'municipios.nombre', 'municipios.slug')
            ->orderByRaw('ABS(SUM(dato_historicos.valor) - ?) ASC', [$valorActual])
            ->limit(4)
            ->get();

        return [
            'success' => true,
            'indicador' => 'Presupuesto de Egresos',
            'variable' => 'FORTAMUN + FAISMUN',
            'anio' => $anio,
            'valor_actual' => '$' . number_format((float)$valorActual, 0),
            'similares' => $similares->map(fn($s) => [
                'nombre' => $s->nombre,
                'slug' => $s->slug,
                'valor' => '$' . number_format((float)$s->total_valor, 0)
            ]),
        ];
    }

    private function calcularSimilitudSuperficie(Municipio $municipio): array
    {
        $valorActual = (float) ($municipio->superficie ?? 0);
        if ($valorActual <= 0) {
            return [
                'success' => false,
                'message' => 'No hay superficie municipal disponible.',
            ];
        }

        $macrorregionId = $municipio->microrregion?->macrorregion_id;
        $query = Municipio::where('municipios.id', '!=', $municipio->id)
            ->whereNotNull('municipios.superficie')
            ->where('municipios.superficie', '>', 0);

        if ($macrorregionId) {
            $query->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
                ->where('microrregions.macrorregion_id', $macrorregionId);
        }

        $similares = $query
            ->select('municipios.nombre', 'municipios.slug', 'municipios.superficie')
            ->orderByRaw('ABS(municipios.superficie - ?) ASC', [$valorActual])
            ->limit(4)
            ->get();

        return [
            'success' => true,
            'indicador' => 'Superficie territorial',
            'variable' => 'Superficie municipal',
            'anio' => null,
            'valor_actual' => number_format($valorActual, 2) . ' km²',
            'similares' => $similares->map(fn($similar) => [
                'nombre' => $similar->nombre,
                'slug' => $similar->slug,
                'valor' => number_format((float) $similar->superficie, 2) . ' km²',
            ]),
        ];
    }

    private function calcularSimilitudVariable(Municipio $municipio, Variable $variable, $configKeyOrId, ?ConfiguracionFicha $config = null): array
    {
        $ultimoDato = DatoHistorico::where('variable_id', $variable->id)
            ->where('municipio_id', $municipio->id)
            ->orderBy('anio', 'desc')
            ->first();

        if (!$ultimoDato) {
            return [
                'success' => false,
                'message' => 'No hay datos históricos disponibles para la variable seleccionada.'
            ];
        }

        $valorActual = $ultimoDato->valor;
        $anio = $ultimoDato->anio;
        $macrorregionId = $municipio->microrregion?->macrorregion_id;

        $query = Municipio::where('municipios.id', '!=', $municipio->id)
            ->join('dato_historicos', 'municipios.id', '=', 'dato_historicos.municipio_id')
            ->where('dato_historicos.variable_id', $variable->id)
            ->where('dato_historicos.anio', $anio);

        if ($macrorregionId) {
            $query->join('microrregions', 'municipios.microrregion_id', '=', 'microrregions.id')
                ->where('microrregions.macrorregion_id', $macrorregionId);
        }

        if (is_numeric($valorActual)) {
            $similares = $query->select('municipios.nombre', 'municipios.slug', 'dato_historicos.valor')
                ->orderByRaw('ABS(dato_historicos.valor - ?) ASC', [(float)$valorActual])
                ->limit(4)
                ->get();
        } else {
            $similares = $query->select('municipios.nombre', 'municipios.slug', 'dato_historicos.valor')
                ->where('dato_historicos.valor', $valorActual)
                ->limit(4)
                ->get();
        }

        $similaresFormateados = $similares->map(fn($s) => $this->formatearValorSimilar($s, $variable, $configKeyOrId));

        $nombreIndicador = is_numeric($configKeyOrId)
            ? ($config->titulo_reporte ?? $config->indicador->nombre_amigable)
            : ucwords(str_replace('marginacion', 'marginación', $configKeyOrId));

        return [
            'success' => true,
            'indicador' => $nombreIndicador,
            'variable' => $variable->nombre_amigable,
            'anio' => $anio,
            'valor_actual' => $this->formatearValor($valorActual, $configKeyOrId, $variable),
            'similares' => $similaresFormateados,
        ];
    }

    private function formatearValorSimilar($s, Variable $variable, $configKeyOrId): array
    {
        return [
            'nombre' => $s->nombre,
            'slug' => $s->slug,
            'valor' => $this->formatearValor($s->valor, $configKeyOrId, $variable),
        ];
    }

    private function formatearValor($valor, $configKeyOrId, Variable $variable): string
    {
        if (!is_numeric($valor)) {
            return $valor;
        }

        $valFloat = (float)$valor;

        $unidad = strtolower($variable->unidad ?? '');
        if (str_contains($unidad, '%') || str_contains($unidad, 'porcentaje')) {
            return number_format($valFloat, 1) . '%';
        }
        if (str_contains($unidad, '$') || str_contains($unidad, 'pesos') || str_contains($unidad, 'monto')) {
            return '$' . number_format($valFloat, 0);
        }

        return number_format($valFloat, 0) . ' ' . $variable->unidad;
    }

    private function normalizeOperation(string $operation): string
    {
        return strtolower($operation) === 'sum' ? 'sum' : 'avg';
    }

    private function normalizeVariableIds($variableIds): array
    {
        return collect($variableIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function aggregate($values, string $operation): float|int
    {
        if ($values->isEmpty()) {
            return 0;
        }

        return $this->normalizeOperation($operation) === 'sum'
            ? $values->sum()
            : $values->avg();
    }
}
