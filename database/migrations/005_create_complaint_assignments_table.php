<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->text('assignment_note_encrypted')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('complaint_id');
            $table->index('assigned_to');
            $table->index('assigned_by');
        });

        // Partial unique index: only one current assignment per complaint
        DB::statement(
            'CREATE UNIQUE INDEX complaint_assignments_current_unique
             ON complaint_assignments (complaint_id)
             WHERE is_current = true AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_assignments');
    }
};
