<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('nombre_tecnico');
        });
        Schema::table('tematicas', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('nombre_tecnico');
        });
        Schema::table('indicadors', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('nombre_tecnico');
        });
        Schema::table('variables', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('nombre_tecnico');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
        Schema::table('tematicas', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
        Schema::table('indicadors', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
        Schema::table('variables', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
};
