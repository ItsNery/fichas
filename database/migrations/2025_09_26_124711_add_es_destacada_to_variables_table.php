<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEsDestacadaToVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variables', function (Blueprint $table) {
            $table->boolean('es_destacada')->default(false)->after('es_kpi')->comment('Indica si la variable es destacada en el carrusel de la página de inicio');
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
            $table->dropColumn('es_destacada');
        });
    }
}
