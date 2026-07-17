<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipios', function (Blueprint $table) {
            $table->json('banner_attribution')->nullable()->after('banner_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('municipios', function (Blueprint $table) {
            $table->dropColumn('banner_attribution');
        });
    }
};
