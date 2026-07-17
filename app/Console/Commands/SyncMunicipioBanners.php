<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use App\Services\BannerImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMunicipioBanners extends Command
{
    protected $signature = 'municipios:sync-banners
        {--dry-run : Validar sin escribir en BD}';
    protected $description = 'Precarga imágenes de banner para cada municipio desde Wikipedia con fallback regional';

    public function handle(BannerImageService $service): int
    {
        $municipios = Municipio::with('microrregion.macrorregion')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'microrregion_id', 'banner_image_url', 'banner_attribution']);

        $stats = ['wikipedia' => 0, 'representative' => 0, 'picsum' => 0, 'skipped' => 0];

        foreach ($municipios as $municipio) {
            if ($municipio->banner_image_url) {
                $this->line("  ↬ {$municipio->nombre}: ya tiene banner, omitido");
                $stats['skipped']++;
                continue;
            }

            $result = $service->resolve($municipio);

            if (!$this->option('dry-run')) {
                DB::table('municipios')->where('id', $municipio->id)->update([
                    'banner_image_url' => $result['url'],
                    'banner_attribution' => $result['attribution']
                        ? json_encode($result['attribution'])
                        : null,
                    'updated_at' => now(),
                ]);
            }

            $stats[$result['source']]++;
            $this->line("  {$municipio->nombre}: {$result['source']}");
        }

        $this->table(
            ['Fuente', 'Cantidad'],
            [
                ['Wikipedia', $stats['wikipedia']],
                ['Regional representativa', $stats['representative']],
                ['Picsum (fallback)', $stats['picsum']],
                ['Ya tenía banner', $stats['skipped']],
            ]
        );

        $total = $stats['wikipedia'] + $stats['representative'] + $stats['picsum'] + $stats['skipped'];
        $this->info("{$total} municipios procesados.");

        return self::SUCCESS;
    }
}
