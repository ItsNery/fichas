<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variables', function (Blueprint $table) {
            $table->boolean('es_construida')->default(false)->after('es_kpi');
            $table->string('formula_tipo', 30)->nullable()->after('es_construida');
            $table->json('formula_config')->nullable()->after('formula_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('variables', function (Blueprint $table) {
            $table->dropColumn(['es_construida', 'formula_tipo', 'formula_config']);
        });
    }
};
