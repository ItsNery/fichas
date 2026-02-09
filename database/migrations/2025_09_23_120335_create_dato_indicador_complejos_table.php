<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatoIndicadorComplejosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dato_indicador_complejos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->constrained('indicadors')->onDelete('cascade');
            $table->foreignId('municipio_id')->constrained('municipios')->onDelete('cascade');
            $table->year('anio');
            $table->json('datos'); // La columna mágica para nuestros cultivos
            $table->timestamps();

            $table->unique(['indicador_id', 'municipio_id', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dato_indicador_complejos');
    }
}
