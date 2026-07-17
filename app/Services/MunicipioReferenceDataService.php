<?php

namespace App\Services;

use App\Models\DatoHistorico;
use App\Models\Municipio;
use App\Models\Variable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MunicipioReferenceDataService
{
    public const SUPERFICIE_VARIABLE = 'superficie_territorial_hectareas_superficie_territorial_hectareas';

    public function syncSuperficies(bool $dryRun = false, ?array $municipioIds = null): array
    {
        $variable = Variable::where('nombre_tecnico', self::SUPERFICIE_VARIABLE)->first();

        if (!$variable) {
            throw ValidationException::withMessages([
                'superficie' => 'No se encontró la variable técnica de superficie territorial.',
            ]);
        }

        if (!str_contains(mb_strtolower(trim((string) $variable->unidad_medida)), 'hect')) {
            throw ValidationException::withMessages([
                'superficie' => 'La variable de superficie debe estar expresada en hectáreas.',
            ]);
        }

        $municipios = Municipio::query()
            ->when($municipioIds, fn($query) => $query->whereIn('id', $municipioIds))
            ->get(['id', 'nombre', 'superficie']);

        $datos = DatoHistorico::query()
            ->where('variable_id', $variable->id)
            ->whereIn('municipio_id', $municipios->pluck('id'))
            ->orderBy('municipio_id')
            ->orderByDesc('anio')
            ->orderByDesc('id')
            ->get(['id', 'municipio_id', 'anio', 'valor'])
            ->unique('municipio_id')
            ->keyBy('municipio_id');

        $rows = collect();
        $sinDato = collect();

        foreach ($municipios as $municipio) {
            $dato = $datos->get($municipio->id);

            if (!$dato || !is_numeric($dato->valor) || (float) $dato->valor <= 0) {
                $sinDato->push($municipio->nombre);
                continue;
            }

            $rows->push([
                'id' => $municipio->id,
                'superficie' => round((float) $dato->valor / 100, 2),
                'updated_at' => now(),
            ]);
        }

        if (!$dryRun && $rows->isNotEmpty()) {
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    DB::table('municipios')->where('id', $row['id'])->update([
                        'superficie' => $row['superficie'],
                        'updated_at' => $row['updated_at'],
                    ]);
                }
            });
        }

        return [
            'variable_id' => $variable->id,
            'anio_min' => $datos->min('anio'),
            'anio_max' => $datos->max('anio'),
            'procesados' => $municipios->count(),
            'sincronizados' => $rows->count(),
            'sin_dato' => $sinDato->values()->all(),
            'dry_run' => $dryRun,
        ];
    }
}
