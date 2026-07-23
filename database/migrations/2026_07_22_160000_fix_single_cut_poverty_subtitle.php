<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configuracion_fichas')
            ->where('titulo_reporte', 'Población en situación de pobreza')
            ->where('anios_historial', 1)
            ->update(['subtitulo_reporte' => 'Último corte disponible']);
    }

    public function down(): void
    {
        DB::table('configuracion_fichas')
            ->where('titulo_reporte', 'Población en situación de pobreza')
            ->where('anios_historial', 1)
            ->update(['subtitulo_reporte' => 'Comparación del último corte disponible']);
    }
};
