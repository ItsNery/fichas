<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueFromNombreTecnicoInVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variables', function (Blueprint $table) {
            $table->dropUnique('variables_nombre_tecnico_unique');

            // 2. Después, creamos la nueva regla compuesta
            $table->unique(['indicador_id', 'nombre_tecnico']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('variables', function (Blueprint $table) {
            $table->dropUnique(['indicador_id', 'nombre_tecnico']);
            $table->unique('nombre_tecnico');
        });
    }
}
