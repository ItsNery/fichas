<?php

namespace App\Console\Commands;

use App\Models\TrafficRequestEvent;
use Illuminate\Console\Command;

class PruneTrafficRequestEvents extends Command
{
    protected $signature = 'traffic:prune';
    protected $description = 'Elimina eventos de tráfico que exceden la retención configurada';

    public function handle(): int
    {
        $deleted = TrafficRequestEvent::where('occurred_at', '<', now()->subDays(config('traffic.retention_days')))->delete();

        $this->info("Eventos de tráfico eliminados: {$deleted}");

        return self::SUCCESS;
    }
}
