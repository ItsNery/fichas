<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->string('responsable')->nullable();
            $table->string('periodicidad')->nullable();
            $table->date('fecha_vigencia_inicio')->nullable();
            $table->date('fecha_vigencia_fin')->nullable();
            $table->text('metodologia')->nullable();
            $table->string('metodologia_url')->nullable();
            $table->enum('clasificacion', ['publica', 'uso_interno', 'confidencial'])->default('publica');
            $table->enum('estado_publicacion', ['borrador', 'en_revision', 'publicado', 'deprecado'])->default('publicado');
            $table->string('cobertura_geografica')->nullable();
            $table->string('unidad_responsable')->nullable();
            $table->text('notas_metodologicas')->nullable();
            $table->string('norma_tecnica')->nullable();
        });
    }

    public function down()
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->dropColumn([
                'responsable', 'periodicidad', 'fecha_vigencia_inicio', 'fecha_vigencia_fin',
                'metodologia', 'metodologia_url', 'clasificacion', 'estado_publicacion',
                'cobertura_geografica', 'unidad_responsable', 'notas_metodologicas', 'norma_tecnica',
            ]);
        });
    }
};
