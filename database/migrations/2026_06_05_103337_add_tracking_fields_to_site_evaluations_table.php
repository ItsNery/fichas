<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrackingFieldsToSiteEvaluationsTable extends Migration
{
    public function up()
    {
        Schema::table('site_evaluations', function (Blueprint $table) {
            // Renombrar url a url_evaluated para mayor claridad
            $table->renameColumn('url', 'url_evaluated');

            // Datos del servidor
            $table->string('ip_address', 45)->nullable()->after('user_agent');
            $table->unsignedBigInteger('user_id')->nullable()->after('ip_address');
            $table->string('device_type', 20)->nullable()->after('user_id');
            $table->string('browser', 100)->nullable()->after('device_type');
            $table->string('browser_version', 50)->nullable()->after('browser');
            $table->string('os', 100)->nullable()->after('browser_version');
            $table->string('os_version', 50)->nullable()->after('os');

            // Datos del cliente (enviados por JS)
            $table->string('screen_resolution', 20)->nullable()->after('os_version');
            $table->string('language', 10)->nullable()->after('screen_resolution');
            $table->string('time_zone', 50)->nullable()->after('language');
        });
    }

    public function down()
    {
        Schema::table('site_evaluations', function (Blueprint $table) {
            $table->renameColumn('url_evaluated', 'url');
            $table->dropColumn([
                'ip_address', 'user_id', 'device_type',
                'browser', 'browser_version', 'os', 'os_version',
                'screen_resolution', 'language', 'time_zone',
            ]);
        });
    }
}
