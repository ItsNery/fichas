<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttributeChangesToActivityLogTable extends Migration
{
    public function up()
    {
        $connection = Schema::connection(config('activitylog.database_connection'));
        $tableName = 'activity_log';

        if ($connection->hasColumn($tableName, 'attribute_changes')) {
            return;
        }

        $connection->table($tableName, function (Blueprint $table) {
            $table->json('attribute_changes')->nullable()->after('properties');
        });
    }

    public function down()
    {
        $connection = Schema::connection(config('activitylog.database_connection'));
        $tableName = 'activity_log';

        if ($connection->hasColumn($tableName, 'attribute_changes')) {
            $connection->table($tableName, function (Blueprint $table) {
                $table->dropColumn('attribute_changes');
            });
        }
    }
}
