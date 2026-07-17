<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private array $titles = [
        'Focalización FAISMUN: recursos aprobados vs pobreza extrema',
        'FAISMUN y carencia de servicios básicos en la vivienda',
        'FAISMUN y rezago educativo',
        'FORTAMUN frente a percepción de inseguridad',
        'Productividad agrícola frente a pobreza',
    ];

    public function up(): void
    {
        $configurations = [
            [
                'title' => $this->titles[0],
                'subtitle' => 'Asignación aprobada por habitante frente a la pobreza extrema municipal',
                'narrative' => 'Permite observar si la asignación inicial del FAISMUN se concentra en los municipios con mayores niveles de pobreza extrema. La relación es descriptiva y no implica causalidad.',
                'x' => 'FAISMUN APROBADO',
                'y' => 'Porcentaje de población en situación de pobreza extrema',
                'icon' => 'fa-solid fa-bullseye',
                'colors' => ['otros_color' => '#c79b66', 'municipio_color' => '#861e34'],
            ],
            [
                'title' => $this->titles[1],
                'subtitle' => 'Gasto devengado por habitante frente a necesidades de infraestructura básica',
                'narrative' => 'Contrasta el ejercicio del FAISMUN con la proporción de población que carece de servicios básicos en la vivienda, una necesidad directamente vinculada con la infraestructura social.',
                'x' => 'FAISMUN DEVENGADO',
                'y' => 'Porcentaje de población con carencia por acceso a los servicios básicos en la vivienda',
                'icon' => 'fa-solid fa-house-circle-exclamation',
                'colors' => ['otros_color' => '#69a2a4', 'municipio_color' => '#7a1732'],
            ],
            [
                'title' => $this->titles[2],
                'subtitle' => 'Inversión social ejercida por habitante frente al rezago educativo',
                'narrative' => 'Identifica municipios donde un rezago educativo elevado convive con un menor ejercicio per cápita del FAISMUN, aportando contexto para la priorización territorial.',
                'x' => 'FAISMUN DEVENGADO',
                'y' => 'Porcentaje de personas con rezago educativo',
                'icon' => 'fa-solid fa-graduation-cap',
                'colors' => ['otros_color' => '#7597bd', 'municipio_color' => '#9a6b24'],
            ],
            [
                'title' => $this->titles[3],
                'subtitle' => 'Recursos ejercidos por habitante frente a la percepción ciudadana de inseguridad',
                'narrative' => 'Explora si los municipios con mayor percepción de inseguridad presentan niveles diferenciados de gasto FORTAMUN por habitante. Debe interpretarse como una comparación contextual.',
                'x' => 'FORTAMUN DEVENGADO',
                'y' => 'Percepción de inseguridad',
                'icon' => 'fa-solid fa-shield-halved',
                'colors' => ['otros_color' => '#d19b65', 'municipio_color' => '#5f1b2d'],
            ],
            [
                'title' => $this->titles[4],
                'subtitle' => 'Valor agrícola por habitante frente a la incidencia municipal de pobreza',
                'narrative' => 'Muestra territorios donde una alta producción agrícola por habitante no necesariamente se traduce en menor pobreza, revelando posibles brechas entre actividad económica y bienestar.',
                'x' => 'Valor de la producción agrícola (Miles de pesos)',
                'y' => 'Porcentaje de población en situación de pobreza',
                'icon' => 'fa-solid fa-wheat-awn',
                'colors' => ['otros_color' => '#7ca982', 'municipio_color' => '#8a4b24'],
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
                        'orden' => 9 + $index,
                        'tipo_visualizacion' => 'scatter',
                        'anios_historial' => 5,
                        'subtitulo_reporte' => $configuration['subtitle'],
                        'plantilla_narrativa' => $configuration['narrative'],
                        'clase_grid' => 'col-12 col-xl-6',
                        'icono' => $configuration['icon'],
                        'mostrar_comparativa' => false,
                        'ajustes_visuales' => json_encode($configuration['colors']),
                        'activo' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $configId = DB::table('configuracion_fichas')
                    ->where('titulo_reporte', $configuration['title'])
                    ->value('id');

                DB::table('configuracion_ficha_variable')
                    ->where('configuracion_ficha_id', $configId)
                    ->delete();
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
