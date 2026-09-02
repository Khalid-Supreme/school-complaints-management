<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Immediate peer (REMOTE_ADDR) — the TCP peer as seen by PHP/Heroku router.
            $table->string('peer_ip', 45)->nullable()->after('ip_address')->index();
            // Heroku / app request correlation ID (X-Request-ID).
            $table->string('request_id', 100)->nullable()->after('peer_ip')->index();
            // Raw X-Forwarded-For header (may contain multiple IPs, untrusted).
            $table->string('x_forwarded_for', 1000)->nullable()->after('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['peer_ip', 'request_id', 'x_forwarded_for']);
        });
    }
};
