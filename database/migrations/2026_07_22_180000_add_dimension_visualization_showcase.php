<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $examples = [
            [
                'indicador_id' => 33,
                'variable_ids' => [84],
                'seccion' => 'demografica_y_social',
                'orden' => 20,
                'tipo_visualizacion' => 'mapa',
                'titulo_reporte' => 'Pobreza en el territorio',
                'subtitulo_reporte' => 'Distribución municipal del último corte disponible',
                'icono' => 'fa-solid fa-map-location-dot',
                'anios_historial' => 3,
                'mostrar_comparativa' => false,
                'benchmark_mode' => 'avg',
            ],
            [
                'indicador_id' => 56,
                'variable_ids' => [113, 114, 115, 116],
                'seccion' => 'economico',
                'orden' => 21,
                'tipo_visualizacion' => 'barras',
                'titulo_reporte' => 'Valor agregado por sector',
                'subtitulo_reporte' => 'Comparación regional por sumatoria',
                'icono' => 'fa-solid fa-chart-column',
                'anios_historial' => 2,
                'mostrar_comparativa' => true,
                'benchmark_mode' => 'sum',
            ],
            [
                'indicador_id' => 98,
                'variable_ids' => [187, 188, 189, 190],
                'seccion' => 'geografica_y_medio_ambiente',
                'orden' => 22,
                'tipo_visualizacion' => 'treemap',
                'titulo_reporte' => 'Composición de emisiones atmosféricas',
                'subtitulo_reporte' => 'Participación de cada contaminante en el último corte',
                'icono' => 'fa-solid fa-layer-group',
                'anios_historial' => 1,
                'mostrar_comparativa' => false,
                'benchmark_mode' => 'avg',
            ],
            [
                'indicador_id' => 144,
                'variable_ids' => [295, 296, 297],
                'seccion' => 'Gobierno_Seguridad_Imparticion_Justicia',
                'orden' => 23,
                'tipo_visualizacion' => 'lineas',
                'titulo_reporte' => 'Evolución de llamadas atendidas',
                'subtitulo_reporte' => 'Comparación regional por sumatoria',
                'icono' => 'fa-solid fa-chart-line',
                'anios_historial' => 4,
                'mostrar_comparativa' => true,
                'benchmark_mode' => 'sum',
            ],
        ];

        foreach ($examples as $example) {
            $exists = DB::table('configuracion_fichas')
                ->where('titulo_reporte', $example['titulo_reporte'])
                ->exists();

            if ($exists || !DB::table('indicadors')->where('id', $example['indicador_id'])->exists()) {
                continue;
            }

            $variableIds = $example['variable_ids'];
            unset($example['variable_ids'], $example['benchmark_mode']);

            $configId = DB::table('configuracion_fichas')->insertGetId([
                ...$example,
                'plantilla_narrativa' => 'En el último corte, {municipio} registra {valor}. La evolución histórica muestra {tendencia_historica}.',
                'clase_grid' => 'col-md-6',
                'ajustes_visuales' => json_encode(['benchmark_mode' => $example['mostrar_comparativa'] ? 'sum' : 'avg']),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($variableIds as $variableId) {
                if (DB::table('variables')->where('id', $variableId)->exists()) {
                    DB::table('configuracion_ficha_variable')->insert([
                        'configuracion_ficha_id' => $configId,
                        'variable_id' => $variableId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $titles = [
            'Pobreza en el territorio',
            'Valor agregado por sector',
            'Composición de emisiones atmosféricas',
            'Evolución de llamadas atendidas',
        ];

        DB::table('configuracion_fichas')->whereIn('titulo_reporte', $titles)->delete();
    }
};
