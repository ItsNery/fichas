<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixUniqueConstraintOnVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variables', function (Blueprint $table) {
            // Paso 1: Eliminar la clave foránea que está causando el conflicto.
            // Laravel nombra esta restricción automáticamente como 'tabla_columna_foreign'.
            $table->dropForeign('variables_indicador_id_foreign');

            // Paso 2: Ahora que nada depende del índice, lo eliminamos.
            $table->dropUnique('variables_indicador_id_nombre_tecnico_unique');

            // Paso 3: Volvemos a crear la clave foránea. La base de datos ahora
            // creará un nuevo índice simple para esta clave, que es lo correcto.
            $table->foreign('indicador_id')
                ->references('id')
                ->on('indicadors')
                ->onDelete('cascade');
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
            // El método down() hace los pasos en orden inverso para poder revertir.
            $table->dropForeign(['indicador_id']);
            $table->unique(['indicador_id', 'nombre_tecnico']);
            $table->foreign('indicador_id')
                ->references('id')
                ->on('indicadors')
                ->onDelete('cascade');
        });
    }
}
