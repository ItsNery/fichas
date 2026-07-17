<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lote_dato_historicos', function (Blueprint $table) {
            $table->decimal('valor_original', 20, 4)->nullable()->after('accion');
            $table->foreignId('motivo_sin_dato_original_id')->nullable()->after('valor_original')
                ->constrained('cat_motivos_sin_dato')->nullOnDelete();
            $table->timestamp('dato_historico_updated_at')->nullable()->after('motivo_sin_dato_original_id');
        });

        Schema::create('lote_dato_indicador_complejos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_datos_id')->constrained('lotes_datos')->cascadeOnDelete();
            $table->unsignedInteger('fila_origen');
            $table->foreignId('indicador_id')->constrained('indicadors')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->json('datos');
            $table->json('datos_originales')->nullable();
            $table->timestamp('dato_complejo_updated_at')->nullable();
            $table->string('accion', 20);
            $table->timestamps();
            $table->unique(['lote_datos_id', 'indicador_id', 'municipio_id', 'anio'], 'lote_complejo_fila_unique');
        });

        Schema::table('dato_indicador_complejos', function (Blueprint $table) {
            $table->foreignId('lote_datos_id')->nullable()->after('datos')
                ->constrained('lotes_datos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dato_indicador_complejos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lote_datos_id');
        });
        Schema::dropIfExists('lote_dato_indicador_complejos');
        Schema::table('lote_dato_historicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('motivo_sin_dato_original_id');
            $table->dropColumn(['valor_original', 'dato_historico_updated_at']);
        });
    }
};
