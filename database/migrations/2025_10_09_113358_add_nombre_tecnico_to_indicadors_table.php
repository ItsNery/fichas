<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNombreTecnicoToIndicadorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->string('nombre_tecnico')->unique()->nullable()->after('nombre_amigable');
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
            $table->dropColumn('nombre_tecnico');
        });
    }
}
