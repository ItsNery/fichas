<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('indicadors')
            ->where('tipo_grafico_default', 'Líneal')
            ->update(['tipo_grafico_default' => 'Lineal']);
    }

    public function down(): void
    {
        // The canonical spelling cannot be distinguished from values already stored as "Lineal".
    }
};
