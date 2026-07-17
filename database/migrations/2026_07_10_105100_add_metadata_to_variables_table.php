<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('variables', function (Blueprint $table) {
            $table->string('tipo_valor')->nullable();
            $table->decimal('valor_minimo', 20, 4)->nullable();
            $table->decimal('valor_maximo', 20, 4)->nullable();
            $table->text('definicion_operativa')->nullable();
            $table->string('fuente_primaria')->nullable();
        });
    }

    public function down()
    {
        Schema::table('variables', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_valor', 'valor_minimo', 'valor_maximo',
                'definicion_operativa', 'fuente_primaria',
            ]);
        });
    }
};
