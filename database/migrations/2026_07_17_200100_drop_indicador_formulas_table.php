<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('indicador_formulas');
    }

    public function down(): void
    {
        // no revert — table was created in previous migration in this same batch
    }
};
