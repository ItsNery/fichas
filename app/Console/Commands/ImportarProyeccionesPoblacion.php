<?php

namespace App\Console\Commands;

use App\Models\LoteDatos;
use App\Models\User;
use App\Services\LoteDatosService;
use App\Services\ProyeccionesPoblacionService;
use Illuminate\Console\Command;

class ImportarProyeccionesPoblacion extends Command
{
    protected $signature = 'proyecciones:importar
        {--archivo= : Ruta al archivo 1_Grupo_Quinq_21_PU.xlsx}
        {--usuario= : ID del usuario responsable del lote}
        {--aprobar : Enviar a revisión y aprobar el lote en esta ejecución}
        {--sin-total : No generar la población total construida}
        {--fortamun : Generar y activar FORTAMUN per cápita con población proyectada}
        {--solo-fortamun : No releer el Excel; usar las proyecciones ya importadas}';

    protected $description = 'Importa las proyecciones demográficas ocultas de Puebla 1990-2040';

    public function handle(ProyeccionesPoblacionService $service, LoteDatosService $lotes): int
    {
        $path = $this->option('archivo') ?: public_path('documentos/Proyecciones19902040Puebla/1_Grupo_Quinq_21_PU.xlsx');
        $user = $this->option('usuario')
            ? User::find((int) $this->option('usuario'))
            : User::query()->orderBy('id')->first();

        if (!$user) {
            $this->error('No existe un usuario para registrar el lote. Usa --usuario=ID.');
            return self::FAILURE;
        }

        try {
            $this->info('Creando o verificando el catálogo oculto en Demográfica y Social...');
            $catalog = $service->ensureCatalog();
            $this->info("Indicador oculto #{$catalog['indicador']->id}; variables fuente listas.");

            if ($this->option('solo-fortamun')) {
                if (!$this->option('fortamun')) {
                    $this->error('Usa --fortamun junto con --solo-fortamun.');
                    return self::FAILURE;
                }
                $fortamun = $service->createFortamunPerCapita($catalog, $user);
                $this->info("FORTAMUN per cápita proyectado: {$fortamun['filas']} registros; configuración KPI actualizada.");
                return self::SUCCESS;
            }

            $this->info("Importando {$path}");
            $lote = $service->import($path, $user, $this->output);
            $this->newLine();
            $this->info("Lote #{$lote->id}: {$lote->total_filas} filas ({$lote->filas_insertar} nuevas, {$lote->filas_actualizar} actualizaciones).");

            $lotes->enviarRevision($lote, $user);
            $this->info("Lote #{$lote->id} enviado a revisión.");

            if ($this->option('aprobar')) {
                $service->approve($lote, $user);
                $this->info("Lote #{$lote->id} aprobado y aplicado.");
            }

            if (!$this->option('sin-total') && $this->option('aprobar')) {
                $count = $service->generateTotal($catalog, $user);
                $this->info("Población total construida: {$count} registros.");
            }

            if ($this->option('fortamun') && $this->option('aprobar')) {
                $fortamun = $service->createFortamunPerCapita($catalog, $user);
                $this->info("FORTAMUN per cápita proyectado: {$fortamun['filas']} registros; configuración KPI actualizada.");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            report($e);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
