<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->string('polaridad')->default('neutro')->after('metodo_calculo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicadors', function (Blueprint $table) {
            $table->dropColumn('polaridad');
        });
    }
};
