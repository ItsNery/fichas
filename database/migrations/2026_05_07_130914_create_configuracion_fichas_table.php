<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracion_fichas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->constrained('indicadors')->onDelete('cascade');
            $table->string('seccion'); // ej: 'general', 'demografia', 'economia'
            $table->integer('orden')->default(0);
            $table->string('tipo_visualizacion'); // ej: 'piramide', 'treemap', 'kpi', 'barras', 'lineas', 'mapa'
            $table->string('titulo_reporte')->nullable();
            $table->string('subtitulo_reporte')->nullable();
            $table->text('plantilla_narrativa')->nullable();
            $table->string('clase_grid')->default('col-12');
            $table->string('icono')->nullable();
            $table->boolean('mostrar_comparativa')->default(false);
            $table->json('ajustes_visuales')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_fichas');
    }
};
