<?php

use App\Models\User;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 150)->nullable()->after('role_id');
            $table->string('last_name', 150)->nullable()->after('first_name');
        });

        // Backfill existing records: copy 'name' to 'first_name', leave 'last_name' null
        User::query()->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $user->updateQuietly([
                    'first_name' => $user->name,
                    'last_name' => null,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};