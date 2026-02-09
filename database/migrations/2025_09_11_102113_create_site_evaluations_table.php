<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_evaluations', function (Blueprint $table) {
            $table->id();                           // Columna de ID auto-incremental (1, 2, 3...)
            $table->integer('score');               // Para guardar la puntuación (1, 2, o 3)
            $table->string('url')->nullable();      // Para guardar la URL donde se votó
            $table->text('user_agent')->nullable(); // Para el navegador del usuario
            $table->timestamps();                   // Columnas created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_evaluations');
    }
}
