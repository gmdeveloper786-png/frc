<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_feedback_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('question_text', 500);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_id', 'is_active', 'sort_order']);
        });

        Schema::create('session_feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_schedule_id')->constrained('enrollment_schedules')->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->foreignId('service_feedback_question_id')->constrained('service_feedback_questions')->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->foreignId('answered_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['enrollment_schedule_id', 'occurrence_date', 'service_feedback_question_id'],
                'sess_feedback_resp_unique',
            );
            $table->index(['enrollment_schedule_id', 'occurrence_date'], 'sess_feedback_resp_sched_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_feedback_responses');
        Schema::dropIfExists('service_feedback_questions');
    }
};
