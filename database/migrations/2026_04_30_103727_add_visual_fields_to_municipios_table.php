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
        Schema::table('municipios', function (Blueprint $table) {
            $table->string('banner_image_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('presidente_municipal')->nullable();
            $table->string('periodo_gobierno')->nullable();
            $table->string('cabecera')->nullable();
            $table->string('clima')->nullable(); // Extra visual context
            $table->string('superficie')->nullable(); // Extra visual context
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('municipios', function (Blueprint $table) {
            $table->dropColumn(['banner_image_url', 'logo_url', 'presidente_municipal', 'periodo_gobierno', 'cabecera', 'clima', 'superficie']);
        });
    }
};
