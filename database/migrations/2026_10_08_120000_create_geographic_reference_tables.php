<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_geograficas', function (Blueprint $table) {
            $table->id();
            $table->string('nivel', 30);
            $table->string('clave_inegi', 10);
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('unidades_geograficas')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nivel', 'clave_inegi'], 'unidad_geo_nivel_clave_unique');
            $table->index(['nivel', 'activo']);
        });

        Schema::create('dato_geografico_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_geografica_id')->constrained('unidades_geograficas')->restrictOnDelete();
            $table->foreignId('variable_id')->constrained('variables')->restrictOnDelete();
            $table->year('anio');
            $table->decimal('valor', 20, 4)->nullable();
            $table->foreignId('motivo_sin_dato_id')->nullable()->constrained('cat_motivos_sin_dato')->restrictOnDelete();
            $table->foreignId('lote_datos_id')->nullable()->constrained('lotes_datos')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['unidad_geografica_id', 'variable_id', 'anio'],
                'dato_geo_unidad_variable_anio_unique'
            );
            $table->index(['variable_id', 'anio']);
        });

        Schema::create('lote_dato_geografico_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_datos_id');
            $table->unsignedInteger('fila_origen');
            $table->foreignId('unidad_geografica_id');
            $table->foreignId('variable_id');
            $table->unsignedSmallInteger('anio');
            $table->decimal('valor', 20, 4)->nullable();
            $table->foreignId('motivo_sin_dato_id')->nullable();
            $table->string('accion', 20);
            $table->decimal('valor_original', 20, 4)->nullable();
            $table->foreignId('motivo_sin_dato_original_id')->nullable();
            $table->timestamp('dato_geografico_historico_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('lote_datos_id', 'ldgh_lote_fk')->references('id')->on('lotes_datos')->cascadeOnDelete();
            $table->foreign('unidad_geografica_id', 'ldgh_unidad_fk')->references('id')->on('unidades_geograficas')->restrictOnDelete();
            $table->foreign('variable_id', 'ldgh_variable_fk')->references('id')->on('variables')->restrictOnDelete();
            $table->foreign('motivo_sin_dato_id', 'ldgh_motivo_fk')->references('id')->on('cat_motivos_sin_dato')->restrictOnDelete();
            $table->foreign('motivo_sin_dato_original_id', 'ldgh_motivo_original_fk')->references('id')->on('cat_motivos_sin_dato')->restrictOnDelete();

            $table->unique(
                ['lote_datos_id', 'unidad_geografica_id', 'variable_id', 'anio'],
                'lote_dato_geo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lote_dato_geografico_historicos');
        Schema::dropIfExists('dato_geografico_historicos');
        Schema::dropIfExists('unidades_geograficas');
    }
};
