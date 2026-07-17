<?php

namespace App\Console\Commands;

use App\Services\MunicipioReferenceDataService;
use Illuminate\Console\Command;

class SyncMunicipioSuperficies extends Command
{
    protected $signature = 'municipios:sync-superficie {--dry-run : Valida sin modificar la base de datos}';
    protected $description = 'Sincroniza la superficie municipal en km² desde el último dato oficial en hectáreas';

    public function handle(MunicipioReferenceDataService $service): int
    {
        $result = $service->syncSuperficies((bool) $this->option('dry-run'));

        $this->table(
            ['Variable', 'Años', 'Procesados', 'Sincronizados', 'Sin dato', 'Modo'],
            [[
                $result['variable_id'],
                ($result['anio_min'] ?? 'N/D') . ' - ' . ($result['anio_max'] ?? 'N/D'),
                $result['procesados'],
                $result['sincronizados'],
                count($result['sin_dato']),
                $result['dry_run'] ? 'Validación' : 'Escritura',
            ]]
        );

        if ($result['sin_dato']) {
            $this->warn('Sin dato: ' . implode(', ', $result['sin_dato']));
            return self::FAILURE;
        }

        $this->info('La superficie municipal quedó sincronizada correctamente.');
        return self::SUCCESS;
    }
}
