<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 50)->unique();
            $table->foreignId('complainant_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('complaint_categories')->restrictOnDelete();
            
            $table->text('title_encrypted');
            $table->text('description_encrypted');
            
            $table->string('priority', 30)->default('medium')->index();
            $table->string('status', 40)->default('submitted')->index();
            $table->string('source', 40)->default('web');
            
            $table->timestamp('submitted_at')->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
