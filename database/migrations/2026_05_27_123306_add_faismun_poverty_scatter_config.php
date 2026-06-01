<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Buscar el indicador por nombre/técnico
        $indicador = DB::table('indicadors')
            ->where('nombre_tecnico', 'recursos_federales_transferidos_al_municipio_fortamun_y_faismun_en_miles_de_pesos')
            ->orWhere('nombre_amigable', 'like', '%Recursos federales transferidos%')
            ->first();

        if (!$indicador) {
            throw new \Exception("No se encontró el indicador de Recursos Federales (FORTAMUN y FAISMUN) en la base de datos.");
        }

        // Buscar variables por nombre
        $varX = DB::table('variables')->where('nombre_amigable', 'FAISMUN DEVENGADO')->first();
        $varY = DB::table('variables')->where('nombre_amigable', 'Porcentaje de población en situación de pobreza')->first();

        if (!$varX || !$varY) {
            throw new \Exception("No se encontraron las variables de FAISMUN DEVENGADO o de Porcentaje de Pobreza en la base de datos.");
        }

        // 1. Insertar en configuracion_fichas
        $configId = DB::table('configuracion_fichas')->insertGetId([
            'indicador_id' => $indicador->id,
            'seccion' => 'economia',
            'orden' => 8,
            'tipo_visualizacion' => 'scatter',
            'clase_grid' => 'col-12',
            'titulo_reporte' => 'Eficiencia Presupuestal FAISMUN vs Pobreza',
            'subtitulo_reporte' => 'Comparación estatal de inversión por habitante contra porcentaje de pobreza',
            'plantilla_narrativa' => 'Este gráfico cruza la inversión FAISMUN per cápita acumulada con el porcentaje de pobreza del municipio. Ayuda a entender si la asignación de recursos está alineada con las carencias sociales de la población local.',
            'activo' => true,
            'mostrar_comparativa' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Asociar variables en la tabla pivote configuracion_ficha_variable
        DB::table('configuracion_ficha_variable')->insert([
            [
                'configuracion_ficha_id' => $configId,
                'variable_id' => $varX->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'configuracion_ficha_id' => $configId,
                'variable_id' => $varY->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Buscar el indicador por nombre/técnico
        $indicador = DB::table('indicadors')
            ->where('nombre_tecnico', 'recursos_federales_transferidos_al_municipio_fortamun_y_faismun_en_miles_de_pesos')
            ->orWhere('nombre_amigable', 'like', '%Recursos federales transferidos%')
            ->first();

        if ($indicador) {
            // Obtener el registro de la configuración
            $config = DB::table('configuracion_fichas')
                ->where('indicador_id', $indicador->id)
                ->where('tipo_visualizacion', 'scatter')
                ->first();

            if ($config) {
                // Eliminar de la tabla pivote
                DB::table('configuracion_ficha_variable')
                    ->where('configuracion_ficha_id', $config->id)
                    ->delete();

                // Eliminar de configuracion_fichas
                DB::table('configuracion_fichas')
                    ->where('id', $config->id)
                    ->delete();
            }
        }
    }
};
