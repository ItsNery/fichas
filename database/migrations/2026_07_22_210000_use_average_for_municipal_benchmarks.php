<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $titles = [
        'Valor agregado por sector',
        'Evolución de llamadas atendidas',
        'Contaminantes atmosféricos por tipo',
    ];

    public function up(): void
    {
        foreach ($this->titles as $title) {
            $config = DB::table('configuracion_fichas')->where('titulo_reporte', $title)->first();
            if (!$config) {
                continue;
            }

            $adjustments = json_decode($config->ajustes_visuales ?: '{}', true) ?: [];
            $adjustments['benchmark_mode'] = 'avg';

            DB::table('configuracion_fichas')->where('id', $config->id)->update([
                'ajustes_visuales' => json_encode($adjustments),
                'subtitulo_reporte' => 'Comparación municipal por promedio',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->titles as $title) {
            $config = DB::table('configuracion_fichas')->where('titulo_reporte', $title)->first();
            if (!$config) {
                continue;
            }

            $adjustments = json_decode($config->ajustes_visuales ?: '{}', true) ?: [];
            $adjustments['benchmark_mode'] = 'sum';

            DB::table('configuracion_fichas')->where('id', $config->id)->update([
                'ajustes_visuales' => json_encode($adjustments),
                'updated_at' => now(),
            ]);
        }
    }
};
