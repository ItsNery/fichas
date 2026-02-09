<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatoHistoricosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dato_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->onDelete('cascade');
            $table->foreignId('variable_id')->constrained('variables')->onDelete('cascade');
            $table->year('anio');
            $table->decimal('valor', 15, 4); // Suficientemente grande para cualquier número
            $table->timestamps();
            $table->unique(['municipio_id', 'variable_id', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dato_historicos');
    }
}
