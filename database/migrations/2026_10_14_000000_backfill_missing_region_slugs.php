<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['macrorregions', 'microrregions'] as $table) {
            DB::table($table)
                ->whereNull('slug')
                ->orWhere('slug', '')
                ->orderBy('id')
                ->each(function (object $region) use ($table): void {
                    $baseSlug = Str::slug($region->nombre) ?: "region-{$region->id}";
                    $slug = $baseSlug;
                    $suffix = 2;

                    while (DB::table($table)->where('slug', $slug)->exists()) {
                        $slug = "{$baseSlug}-{$suffix}";
                        $suffix++;
                    }

                    DB::table($table)
                        ->where('id', $region->id)
                        ->update(['slug' => $slug]);
                });
        }
    }

    public function down(): void
    {
        // Existing slugs cannot be distinguished from values created by this repair.
    }
};
