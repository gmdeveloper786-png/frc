<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('therapist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->json('recommended_services')->nullable();
            $table->text('child_response')->nullable();
            $table->text('initial_recommendation')->nullable();
            $table->text('additional_notes')->nullable();
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assessment_id', 'therapist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_notes');
    }
};
