<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNombreTecnicoToTematicasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tematicas', function (Blueprint $table) {
            $table->string('nombre_tecnico')->unique()->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tematicas', function (Blueprint $table) {
            $table->dropColumn('nombre_tecnico');
        });
    }
}
