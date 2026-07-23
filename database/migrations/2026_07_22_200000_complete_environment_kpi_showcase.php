<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $title = 'KPI de recolección selectiva';

        if (DB::table('configuracion_fichas')->where('titulo_reporte', $title)->exists()) {
            return;
        }

        if (!DB::table('indicadors')->where('id', 96)->exists()
            || !DB::table('variables')->where('id', 185)->exists()) {
            return;
        }

        $configId = DB::table('configuracion_fichas')->insertGetId([
            'indicador_id' => 96,
            'seccion' => 'geografica_y_medio_ambiente',
            'orden' => 47,
            'tipo_visualizacion' => 'kpi',
            'anios_historial' => 1,
            'titulo_reporte' => $title,
            'subtitulo_reporte' => 'Último corte disponible',
            'plantilla_narrativa' => 'En el último corte, {municipio} registra {valor}.',
            'clase_grid' => 'col-md-6',
            'icono' => 'fa-solid fa-recycle',
            'mostrar_comparativa' => false,
            'ajustes_visuales' => json_encode(['benchmark_mode' => 'avg']),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configuracion_ficha_variable')->insert([
            'configuracion_ficha_id' => $configId,
            'variable_id' => 185,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('configuracion_fichas')
            ->where('titulo_reporte', 'KPI de recolección selectiva')
            ->delete();
    }
};
