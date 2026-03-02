<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrepareMicrorregionsUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('municipios', function (Blueprint $table) {
            $table->unsignedBigInteger('microrregion_id')->nullable()->change();
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('municipios', function (Blueprint $table) {
            $table->unsignedBigInteger('microrregion_id')->nullable(false)->change();
        });
        Schema::enableForeignKeyConstraints();
    }
}
