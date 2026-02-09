<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMapeoValoresToVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variables', function (Blueprint $table) {
              $table->json('mapeo_valores')->nullable()->after('unidad_medida');
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
             $table->dropColumn('mapeo_valores');
        });
    }
}
