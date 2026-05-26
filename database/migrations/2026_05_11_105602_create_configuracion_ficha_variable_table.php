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
        Schema::create('configuracion_ficha_variable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuracion_ficha_id')->constrained('configuracion_fichas')->onDelete('cascade');
            $table->foreignId('variable_id')->constrained('variables')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_ficha_variable');
    }
};
