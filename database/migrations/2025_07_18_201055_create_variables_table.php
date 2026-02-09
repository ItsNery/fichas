<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->constrained('indicadors')->onDelete('cascade');
            $table->string('nombre_tecnico')->unique(); // Ej: "pob_hom_60mas"
            $table->string('nombre_amigable'); // Ej: "Hombres de 60 y más"
            $table->string('unidad_medida')->default('Personas'); // Ej: '%', 'Tasa', etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variables');
    }
}
