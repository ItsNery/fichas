<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_fichas', function (Blueprint $table) {
            $table->boolean('destacar_en_resumen')->default(false)->after('mostrar_comparativa');
            $table->unsignedTinyInteger('prioridad_editorial')->nullable()->after('destacar_en_resumen');
            $table->string('rol_editorial')->default('principal')->after('prioridad_editorial');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_fichas', function (Blueprint $table) {
            $table->dropColumn(['destacar_en_resumen', 'prioridad_editorial', 'rol_editorial']);
        });
    }
};
