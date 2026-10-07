<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('municipios')
            ->whereNull('slug')
            ->orWhere('slug', '')
            ->orderBy('id')
            ->each(function (object $municipio): void {
                $slug = Str::slug($municipio->nombre);

                if (DB::table('municipios')->where('slug', $slug)->exists()) {
                    $slug .= '-'.$municipio->id;
                }

                DB::table('municipios')
                    ->where('id', $municipio->id)
                    ->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        // Existing slugs cannot be distinguished from values created by this repair.
    }
};
