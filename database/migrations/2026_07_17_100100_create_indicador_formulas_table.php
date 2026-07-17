<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicador_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->constrained('indicadors')->cascadeOnDelete();
            $table->string('tipo', 30); // 'division', 'tasa_crecimiento'
            $table->json('configuracion');
            $table->foreignId('variable_output_id')->constrained('variables')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('indicador_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicador_formulas');
    }
};
