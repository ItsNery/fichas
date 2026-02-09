<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMotivoIdToDatoHistoricosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dato_historicos', function (Blueprint $table) {
            $table->decimal('valor', 20, 4)->nullable()->change();

            $table->foreignId('motivo_sin_dato_id')->nullable()->after('valor')->constrained('cat_motivos_sin_dato')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dato_historicos', function (Blueprint $table) {
            $table->dropForeign(['motivo_sin_dato_id']);
            $table->dropColumn('motivo_sin_dato_id');
        });
    }
}
