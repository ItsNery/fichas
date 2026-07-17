<?php

namespace App\Console\Commands;

use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\Municipio;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateSlugs extends Command
{
    protected $signature = 'app:generate-slugs {model? : municipios, microrregions, macrorregions, or all}';
    protected $description = 'Genera slugs para registros que no tengan slug';

    public function handle()
    {
        $model = $this->argument('model') ?? 'all';
        $count = 0;

        $models = match ($model) {
            'municipios'    => [Municipio::class],
            'microrregions' => [Microrregion::class],
            'macrorregions' => [Macrorregion::class],
            default         => [Municipio::class, Microrregion::class, Macrorregion::class],
        };

        foreach ($models as $class) {
            $label = class_basename($class);
            $this->line("Procesando {$label}...");
            foreach ($class::all() as $item) {
                if (empty($item->slug)) {
                    $item->slug = Str::slug($item->nombre);
                    $item->save();
                    $count++;
                    $this->info("  {$item->nombre} -> {$item->slug}");
                }
            }
        }

        $this->info("Completado. Se generaron {$count} slugs.");
        return 0;
    }
}
