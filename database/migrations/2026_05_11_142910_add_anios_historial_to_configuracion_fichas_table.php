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
        Schema::table('configuracion_fichas', function (Blueprint $table) {
            $table->integer('anios_historial')->default(5)->after('tipo_visualizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_fichas', function (Blueprint $table) {
            $table->dropColumn('anios_historial');
        });
    }
};
