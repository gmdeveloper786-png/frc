<?php

use App\Services\SessionOccurrenceStateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_schedule_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_schedule_id')->constrained('enrollment_schedules')->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('session_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completion_note')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_schedule_id', 'occurrence_date'], 'eso_schedule_date_unique');
            $table->index(['enrollment_schedule_id', 'status'], 'eso_schedule_status_idx');
        });

        app(SessionOccurrenceStateService::class)->normalizeRecurringTemplateRows();
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_schedule_occurrences');
    }
};
