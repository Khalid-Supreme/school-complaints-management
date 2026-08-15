<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Durable "email verification pending" gate for password changes.
        // Distinct from must_change_password: during verification the user
        // already has a new password, but must confirm the change by entering
        // a one-time code before any dashboard/protected access is granted.
        // Survives restarts and re-logins so the backend remains authoritative.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_change_pending_verification')->default(false)->after('must_change_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_change_pending_verification');
        });
    }
};
