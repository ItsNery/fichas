<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private array $titles = [
        'Escolaridad y pobreza: brecha de capital humano',
        'Accesibilidad carretera y pobreza territorial',
        'Disponibilidad médica frente a carencia de acceso a salud',
        'Inclusión financiera frente a pobreza por ingresos',
        'Violencia registrada frente a percepción de inseguridad',
        'Gestión de residuos: recolección selectiva vs disposición inadecuada',
    ];

    public function up(): void
    {
        $configurations = [
            [
                'title' => $this->titles[0],
                'subtitle' => 'Años promedio de escolaridad frente a incidencia municipal de pobreza',
                'narrative' => 'Permite reconocer la estrecha relación territorial entre formación educativa y condiciones de pobreza, así como municipios que se apartan del patrón estatal.',
                'x' => 'Grado promedio de escolaridad de la población de 15 y más años',
                'y' => 'Porcentaje de población en situación de pobreza',
                'icon' => 'fa-solid fa-user-graduate',
                'visuals' => ['otros_color' => '#7f9db9', 'municipio_color' => '#861e34'],
            ],
            [
                'title' => $this->titles[1],
                'subtitle' => 'Aislamiento vial frente a incidencia municipal de pobreza',
                'narrative' => 'Explora la relación entre baja accesibilidad a carreteras pavimentadas y pobreza, útil para identificar territorios donde la conectividad puede limitar oportunidades y servicios.',
                'x' => 'Porcentaje de población con accesibilidad baja a carretera pavimentada',
                'y' => 'Porcentaje de población en situación de pobreza',
                'icon' => 'fa-solid fa-road-barrier',
                'visuals' => [
                    'otros_color' => '#c99858', 'municipio_color' => '#6f2638',
                    'unidad_x' => 'Porcentaje', 'eje_x_titulo' => 'Población con accesibilidad baja a carretera pavimentada (%)',
                ],
            ],
            [
                'title' => $this->titles[2],
                'subtitle' => 'Personal médico disponible frente a población sin acceso efectivo a salud',
                'narrative' => 'Contrasta capacidad médica y carencia de acceso. Los municipios fuera del patrón pueden revelar que la disponibilidad de personal no garantiza por sí sola cobertura efectiva.',
                'x' => 'Tasa de personal médico',
                'y' => 'Porcentaje de población con carencia por acceso a los servicios de salud',
                'icon' => 'fa-solid fa-user-doctor',
                'visuals' => ['otros_color' => '#73a6a8', 'municipio_color' => '#9c2948'],
            ],
            [
                'title' => $this->titles[3],
                'subtitle' => 'Acceso al sistema bancario frente a insuficiencia de ingresos',
                'narrative' => 'Examina si una mayor inclusión financiera coincide con menores niveles de pobreza por ingresos y permite detectar territorios donde el acceso bancario todavía no se traduce en bienestar.',
                'x' => 'Proporción de adultos que tienen una cuenta en un banco',
                'y' => 'Porcentaje de población con ingreso inferior a la línea de pobreza por ingresos',
                'icon' => 'fa-solid fa-building-columns',
                'visuals' => ['otros_color' => '#8297c5', 'municipio_color' => '#765020'],
            ],
            [
                'title' => $this->titles[4],
                'subtitle' => 'Tasa de homicidios frente a percepción ciudadana de inseguridad',
                'narrative' => 'Compara un indicador objetivo de violencia con la percepción social. Las divergencias ayudan a distinguir presión delictiva, experiencia ciudadana y posibles efectos de contexto.',
                'x' => 'Homicidios',
                'y' => 'Percepción de inseguridad',
                'icon' => 'fa-solid fa-scale-balanced',
                'visuals' => [
                    'otros_color' => '#b98b75', 'municipio_color' => '#5f1b2d',
                    'unidad_x' => 'Homicidios por cada 100 mil habitantes',
                    'eje_x_titulo' => 'Tasa anual de homicidios por cada 100 mil habitantes',
                ],
            ],
            [
                'title' => $this->titles[5],
                'subtitle' => 'Recolección selectiva por habitante frente a prácticas inadecuadas de disposición',
                'narrative' => 'Evalúa si una mayor capacidad de recolección selectiva coincide con menores prácticas inadecuadas de disposición de residuos y visibiliza brechas de gestión ambiental.',
                'x' => 'Promedio diario de residuos recolectados de manera selectiva (Kilogramos)',
                'y' => 'Viviendas particulares habitadas que desechan sus residuos de forma inadecuada (Porcentaje)',
                'icon' => 'fa-solid fa-recycle',
                'visuals' => [
                    'otros_color' => '#83a66e', 'municipio_color' => '#4d7168',
                    'normalizar_x_per_capita' => true,
                    'unidad_x' => 'kg por habitante al día',
                    'eje_x_titulo' => 'Recolección selectiva (kg por habitante al día)',
                ],
            ],
        ];

        DB::transaction(function () use ($configurations) {
            foreach ($configurations as $index => $configuration) {
                $varX = DB::table('variables')->where('nombre_amigable', $configuration['x'])->first();
                $varY = DB::table('variables')->where('nombre_amigable', $configuration['y'])->first();

                if (!$varX || !$varY) {
                    Log::warning("No fue posible crear la configuración '{$configuration['title']}': faltan variables.");
                    continue;
                }

                $now = now();
                DB::table('configuracion_fichas')->updateOrInsert(
                    ['titulo_reporte' => $configuration['title']],
                    [
                        'indicador_id' => $varX->indicador_id,
                        'seccion' => 'analisis_relacional',
                        'orden' => 14 + $index,
                        'tipo_visualizacion' => 'scatter',
                        'anios_historial' => 5,
                        'subtitulo_reporte' => $configuration['subtitle'],
                        'plantilla_narrativa' => $configuration['narrative'],
                        'clase_grid' => 'col-12 col-xl-6',
                        'icono' => $configuration['icon'],
                        'mostrar_comparativa' => false,
                        'ajustes_visuales' => json_encode($configuration['visuals']),
                        'activo' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $configId = DB::table('configuracion_fichas')
                    ->where('titulo_reporte', $configuration['title'])
                    ->value('id');
                DB::table('configuracion_ficha_variable')->where('configuracion_ficha_id', $configId)->delete();
                DB::table('configuracion_ficha_variable')->insert([
                    ['configuracion_ficha_id' => $configId, 'variable_id' => $varX->id, 'created_at' => $now, 'updated_at' => $now],
                    ['configuracion_ficha_id' => $configId, 'variable_id' => $varY->id, 'created_at' => $now, 'updated_at' => $now],
                ]);
            }
        });
    }

    public function down(): void
    {
        $ids = DB::table('configuracion_fichas')->whereIn('titulo_reporte', $this->titles)->pluck('id');
        DB::table('configuracion_ficha_variable')->whereIn('configuracion_ficha_id', $ids)->delete();
        DB::table('configuracion_fichas')->whereIn('id', $ids)->delete();
    }
};
