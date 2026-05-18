<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('therapist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignId('enrollment_schedule_id')->nullable()->constrained('enrollment_schedules')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->date('session_date');
            $table->string('therapy_goal')->nullable();
            $table->text('notes')->nullable();
            $table->text('child_response')->nullable();
            $table->string('progress_level', 32)->default('good'); // excellent, good, average, needs_improvement, no_response
            $table->text('parent_instructions')->nullable();
            $table->text('next_plan')->nullable();
            $table->string('status', 20)->default('draft'); // draft, completed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['therapist_id', 'session_date']);
            $table->index(['child_id', 'therapist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_notes');
    }
};
