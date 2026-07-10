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
        Schema::table('macrorregions', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nombre');
        });
        Schema::table('microrregions', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('macrorregions', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('microrregions', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
