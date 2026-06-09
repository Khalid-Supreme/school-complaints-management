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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 150)->nullable()->index();
            $table->string('ip_address', 45)->index(); // string for inet
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->index();
            $table->string('failure_reason', 100)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
        
        // Add composite index using raw statement if needed or standard blueprint
        Schema::table('login_attempts', function (Blueprint $table) {
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
