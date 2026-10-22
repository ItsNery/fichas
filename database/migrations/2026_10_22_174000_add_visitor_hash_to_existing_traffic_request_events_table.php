<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('traffic_request_events', 'visitor_hash')) {
            return;
        }

        Schema::table('traffic_request_events', function (Blueprint $table) {
            $table->string('visitor_hash', 64)->nullable()->after('client_ip');
            $table->index(['visitor_hash', 'occurred_at'], 'traffic_events_visitor_seen_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('traffic_request_events', 'visitor_hash')) {
            return;
        }

        Schema::table('traffic_request_events', function (Blueprint $table) {
            $table->dropIndex('traffic_events_visitor_seen_index');
            $table->dropColumn('visitor_hash');
        });
    }
};
