<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nombre_tecnico')->unique();
            $table->text('descripcion')->nullable();
            $table->string('ambito', 30)->default('estatal');
            $table->text('expresion');
            $table->json('slots')->nullable();
            $table->boolean('activa')->default(true);
            $table->foreignId('usuario_creacion_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulas');
    }
};
