<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriorizarTotalToIndicadorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->boolean('priorizar_total')->default(true)->after('es_complejo')->comment('Indica si se debe priorizar el total en los cálculos y visualizaciones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->dropColumn('priorizar_total');
        });
    }
}
