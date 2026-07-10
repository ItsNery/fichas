<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentToSiteEvaluationsTable extends Migration
{
    public function up()
    {
        Schema::table('site_evaluations', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('score');
        });
    }

    public function down()
    {
        Schema::table('site_evaluations', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
}
