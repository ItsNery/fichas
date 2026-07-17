<?php

namespace App\Console\Commands;

use App\Models\Indicador;
use App\Services\IndicadorConstruidoService;
use Illuminate\Console\Command;

class GenerarIndicadorConstruido extends Command
{
    protected $signature = 'indicadores:generar {indicador? : ID del indicador construido (opcional, omite para todos)}';
    protected $description = 'Genera o regenera los datos históricos de indicadores construidos';

    public function handle(IndicadorConstruidoService $service): int
    {
        $query = Indicador::where('es_construido', true)->with('formula');

        if ($id = $this->argument('indicador')) {
            $query->where('id', $id);
        }

        $indicadores = $query->get();

        if ($indicadores->isEmpty()) {
            $this->warn('No se encontraron indicadores construidos.');
            return self::SUCCESS;
        }

        $total = 0;
        foreach ($indicadores as $indicador) {
            if (!$indicador->formula) {
                $this->warn("Indicador #{$indicador->id} '{$indicador->nombre_amigable}' no tiene fórmula configurada.");
                continue;
            }

            $count = $service->regenerar($indicador->formula);
            $this->info("Indicador #{$indicador->id} '{$indicador->nombre_amigable}': {$count} registros generados.");
            $total += $count;
        }

        $this->info("Total: {$total} registros generados en {$indicadores->count()} indicadores.");
        return self::SUCCESS;
    }
}
