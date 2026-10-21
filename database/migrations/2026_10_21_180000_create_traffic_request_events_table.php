<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_request_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->index();
            $table->string('route_name')->index();
            $table->string('method', 10);
            $table->unsignedSmallInteger('status')->index();
            $table->unsignedInteger('duration_ms');
            $table->string('client_ip', 45)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->index(['client_ip', 'occurred_at']);
            $table->index(['route_name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_request_events');
    }
};
