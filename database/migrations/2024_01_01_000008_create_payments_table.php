<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('restrict');
            $table->foreignId('child_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'card', 'other']);
            $table->string('transaction_reference')->nullable();
            $table->string('receipt_number')->unique()->nullable();
            $table->string('payment_slip')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->enum('submitted_by_role', ['child', 'admin', 'finance', 'super_admin']);
            $table->enum('status', ['pending_verification', 'paid', 'rejected', 'cancelled', 'refunded'])->default('pending_verification');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enrollment_id');
            $table->index('child_id');
            $table->index('status');
            $table->index('payment_date');
            $table->index('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
