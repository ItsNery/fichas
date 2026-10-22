<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('traffic_request_events', function (Blueprint $table) {
            $table->string('visitor_hash', 64)->nullable()->after('client_ip');
            $table->string('session_hash', 64)->nullable()->after('visitor_hash');
            $table->string('referer_hash', 64)->nullable()->after('user_agent_hash');

            $table->index(['visitor_hash', 'occurred_at'], 'traffic_events_visitor_seen_index');
            $table->index(['session_hash', 'occurred_at'], 'traffic_events_session_seen_index');
            $table->index(['user_agent_hash', 'occurred_at'], 'traffic_events_agent_seen_index');
            $table->index(['referer_hash', 'occurred_at'], 'traffic_events_referer_seen_index');
        });
    }

    public function down(): void
    {
        Schema::table('traffic_request_events', function (Blueprint $table) {
            $table->dropIndex('traffic_events_visitor_seen_index');
            $table->dropIndex('traffic_events_session_seen_index');
            $table->dropIndex('traffic_events_agent_seen_index');
            $table->dropIndex('traffic_events_referer_seen_index');
            $table->dropColumn(['visitor_hash', 'session_hash', 'referer_hash']);
        });
    }
};
