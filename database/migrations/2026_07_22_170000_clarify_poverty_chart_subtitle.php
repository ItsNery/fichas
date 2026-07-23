<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configuracion_fichas')
            ->where('titulo_reporte', 'Población en situación de pobreza')
            ->update(['subtitulo_reporte' => 'Comparación municipal del último corte']);
    }

    public function down(): void
    {
        DB::table('configuracion_fichas')
            ->where('titulo_reporte', 'Población en situación de pobreza')
            ->update(['subtitulo_reporte' => 'Comparación del último corte disponible']);
    }
};
