<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->boolean('visible_en_ficha')->default(true)->after('orden');
        });

        Schema::table('tematicas', function (Blueprint $table) {
            $table->boolean('visible_en_ficha')->default(true)->after('orden');
        });

        Schema::table('indicadors', function (Blueprint $table) {
            $table->boolean('visible_en_ficha')->default(true)->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('dimensions', fn(Blueprint $table) => $table->dropColumn('visible_en_ficha'));
        Schema::table('tematicas', fn(Blueprint $table) => $table->dropColumn('visible_en_ficha'));
        Schema::table('indicadors', fn(Blueprint $table) => $table->dropColumn('visible_en_ficha'));
    }
};
