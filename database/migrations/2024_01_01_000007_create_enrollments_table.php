<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('price_per_session', 10, 2)->default(0);
            $table->unsignedInteger('total_sessions')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial_paid', 'fully_paid', 'overdue'])->default('unpaid');
            $table->boolean('repeat_weekly')->default(false);
            $table->unsignedSmallInteger('duration_value')->nullable();
            $table->enum('duration_unit', ['weekly', 'monthly', 'yearly'])->nullable();
            $table->text('discount_reason')->nullable();
            $table->string('discount_file')->nullable();
            $table->enum('status', [
                'draft',
                'pending_super_admin_approval',
                'approved',
                'rejected',
                'active',
                'completed',
                'cancelled',
            ])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('child_id');
            $table->index('branch_id');
            $table->index('service_id');
            $table->index('therapist_id');
            $table->index('status');
            $table->index('payment_status');
        });

        Schema::create('enrollment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('day', 20);
            $table->string('time_slot', 30);
            $table->date('session_date')->nullable();
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

            $table->index('enrollment_id');
            $table->index('therapist_id');
            $table->index('session_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_schedules');
        Schema::dropIfExists('enrollments');
    }
};
