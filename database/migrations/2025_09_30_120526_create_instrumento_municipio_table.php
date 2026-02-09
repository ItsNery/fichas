<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstrumentoMunicipioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instrumento_municipio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipio_id');
            $table->unsignedBigInteger('instrumento_id');
            $table->integer('anio');
            $table->timestamps();

            $table->foreign('municipio_id')->references('id')->on('municipios')->onDelete('cascade');
            $table->foreign('instrumento_id')->references('id')->on('instrumentos_planeacion')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instrumento_municipio');
    }
}
