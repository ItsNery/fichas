<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncMunicipioClimas extends Command
{
    protected $signature = 'municipios:sync-clima
        {--file=database/data/municipios_clima.json : Archivo generado por el proceso geoespacial}
        {--dry-run : Valida sin modificar la base de datos}';
    protected $description = 'Importa el clima predominante municipal desde el archivo geoespacial auditado';

    private const CLIMAS_VALIDOS = [
        'Cálido húmedo',
        'Cálido subhúmedo',
        'Seco o muy seco',
        'Templado o frío (húmedo o subhúmedo)',
    ];

    public function handle(): int
    {
        $path = base_path($this->option('file'));
        if (!is_file($path)) {
            $this->error("No existe el archivo climático: {$path}");
            return self::FAILURE;
        }

        $payload = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $items = collect($payload['municipios'] ?? []);
        $municipios = Municipio::get(['id', 'cvegeo', 'nombre'])->keyBy(fn($municipio) => (string) $municipio->cvegeo);
        $rows = collect();
        $errors = collect();

        foreach ($items as $item) {
            $cvegeo = (string) ($item['cvegeo'] ?? '');
            $clima = $item['clima'] ?? null;
            $municipio = $municipios->get($cvegeo);

            if (!$municipio) {
                $errors->push("CVEGEO {$cvegeo}: municipio inexistente");
                continue;
            }
            if (!in_array($clima, self::CLIMAS_VALIDOS, true)) {
                $errors->push("{$municipio->nombre}: clima inválido");
                continue;
            }

            $rows->push([
                'id' => $municipio->id,
                'clima' => $clima,
                'updated_at' => now(),
            ]);
        }

        $faltantes = $municipios->keys()->diff($items->pluck('cvegeo')->map(fn($value) => (string) $value));
        foreach ($faltantes as $cvegeo) {
            $errors->push("CVEGEO {$cvegeo}: sin clasificación climática");
        }

        if ($errors->isNotEmpty()) {
            $errors->each(fn($error) => $this->error($error));
            return self::FAILURE;
        }

        if (!$this->option('dry-run')) {
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    DB::table('municipios')->where('id', $row['id'])->update([
                        'clima' => $row['clima'],
                        'updated_at' => $row['updated_at'],
                    ]);
                }
            });
        }

        $this->info(sprintf(
            '%d municipios climáticos %s. Fuente: %s',
            $rows->count(),
            $this->option('dry-run') ? 'validados' : 'sincronizados',
            $payload['source']['name'] ?? 'No especificada'
        ));

        return self::SUCCESS;
    }
}
