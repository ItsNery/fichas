<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetodoCalculoToIndicadorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->text('metodo_calculo')->nullable()->after('fuente');
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
            $table->dropColumn('metodo_calculo');
        });
    }
}
