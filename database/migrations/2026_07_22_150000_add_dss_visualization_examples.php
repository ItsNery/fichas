<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $examples = [
            [
                'indicador_id' => 96,
                'variable_id' => 185,
                'seccion' => 'geografica_y_medio_ambiente',
                'orden' => 996,
                'tipo_visualizacion' => 'lineas',
                'titulo_reporte' => 'Recolección selectiva de residuos',
                'subtitulo_reporte' => 'Evolución reciente del indicador en el municipio',
                'icono' => 'fa-solid fa-recycle',
            ],
            [
                'indicador_id' => 33,
                'variable_id' => 84,
                'seccion' => 'demografica_y_social',
                'orden' => 997,
                'tipo_visualizacion' => 'barras',
                'titulo_reporte' => 'Población en situación de pobreza',
                'subtitulo_reporte' => 'Comparación del último corte disponible',
                'icono' => 'fa-solid fa-chart-column',
            ],
        ];

        foreach ($examples as $example) {
            $exists = DB::table('configuracion_fichas')
                ->where('indicador_id', $example['indicador_id'])
                ->where('tipo_visualizacion', $example['tipo_visualizacion'])
                ->where('titulo_reporte', $example['titulo_reporte'])
                ->exists();

            if ($exists || !DB::table('indicadors')->where('id', $example['indicador_id'])->exists()) {
                continue;
            }

            $configData = $example;
            unset($configData['variable_id']);

            $configId = DB::table('configuracion_fichas')->insertGetId([
                ...$configData,
                'anios_historial' => 5,
                'plantilla_narrativa' => 'En el último corte, {municipio} registra {valor}. La evolución histórica muestra {tendencia_historica}.',
                'clase_grid' => 'col-md-6',
                'mostrar_comparativa' => true,
                'ajustes_visuales' => json_encode(['benchmark_mode' => 'avg']),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (DB::table('variables')->where('id', $example['variable_id'])->exists()) {
                DB::table('configuracion_ficha_variable')->insert([
                    'configuracion_ficha_id' => $configId,
                    'variable_id' => $example['variable_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $titles = [
            'Recolección selectiva de residuos',
            'Población en situación de pobreza',
        ];

        DB::table('configuracion_fichas')->whereIn('titulo_reporte', $titles)->delete();
    }
};
