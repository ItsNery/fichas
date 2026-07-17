<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipios', function (Blueprint $table) {
            $table->decimal('superficie', 12, 2)->nullable()->change();
            $table->dropColumn(['logo_url', 'presidente_municipal', 'periodo_gobierno']);
        });
    }

    public function down(): void
    {
        Schema::table('municipios', function (Blueprint $table) {
            $table->string('superficie')->nullable()->change();
            $table->string('logo_url')->nullable();
            $table->string('presidente_municipal')->nullable();
            $table->string('periodo_gobierno')->nullable();
        });
    }
};
