<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_datos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->default('datos_historicos');
            $table->string('estado')->default('borrador')->index();
            $table->string('archivo_original');
            $table->string('archivo_path');
            $table->string('archivo_hash', 64)->nullable();
            $table->foreignId('usuario_carga_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('usuario_revision_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_insertar')->default(0);
            $table->unsignedInteger('filas_actualizar')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('enviado_revision_at')->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamp('aplicado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lote_dato_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_datos_id')->constrained('lotes_datos')->cascadeOnDelete();
            $table->unsignedInteger('fila_origen');
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->foreignId('variable_id')->constrained('variables')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->decimal('valor', 20, 4)->nullable();
            $table->foreignId('motivo_sin_dato_id')->nullable()->constrained('cat_motivos_sin_dato')->nullOnDelete();
            $table->string('accion', 20);
            $table->timestamps();

            $table->unique(
                ['lote_datos_id', 'municipio_id', 'variable_id', 'anio'],
                'lote_datos_fila_unique'
            );
        });

        Schema::table('dato_historicos', function (Blueprint $table) {
            $table->foreignId('lote_datos_id')
                ->nullable()
                ->after('motivo_sin_dato_id')
                ->constrained('lotes_datos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dato_historicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lote_datos_id');
        });

        Schema::dropIfExists('lote_dato_historicos');
        Schema::dropIfExists('lotes_datos');
    }
};
