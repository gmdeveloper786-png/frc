<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'child_id',
        'received_by',
        'verified_by',
        'amount',
        'payment_method',
        'transaction_reference',
        'receipt_number',
        'payment_slip',
        'payment_date',
        'notes',
        'rejection_reason',
        'submitted_by_role',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
        'verified_at'  => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', 'pending_verification');
    }

    /** Paid centre-issued receipt (cash at desk, or verified fee slip — both get FRC-… when recorded). */
    public function hasPrintableReceipt(): bool
    {
        return $this->status === 'paid' && filled($this->receipt_number);
    }

    public function getPaymentSlipUrlAttribute(): ?string
    {
        return frc_storage_url($this->payment_slip);
    }

    /** Human-readable payment channel (matches receipt / finance report wording). */
    public static function labelForPaymentMethod(?string $method): string
    {
        return match ($method) {
            'bank_transfer' => 'Bank Transfer',
            'jazzcash'      => 'JazzCash',
            'easypaisa'     => 'Easypaisa',
            'cash'          => 'Cash',
            'card'          => 'Card',
            'other'         => 'Other',
            default         => $method ? Str::title(str_replace('_', ' ', $method)) : '—',
        };
    }

    public static function labelForEnrollmentPaymentStatus(?string $status): string
    {
        return match ($status) {
            'partial_paid' => 'Partial Paid',
            'fully_paid'   => 'Fully Paid',
            'unpaid'       => 'Unpaid',
            'overdue'      => 'Overdue',
            default        => $status ? Str::title(str_replace('_', ' ', $status)) : '—',
        };
    }

    public static function labelForVerificationStatus(?string $status): string
    {
        return match ($status) {
            'pending_verification' => 'Pending Verification',
            'paid'                 => 'Paid',
            'rejected'             => 'Rejected',
            'cancelled'            => 'Cancelled',
            'refunded'             => 'Refunded',
            default                => $status ? Str::title(str_replace('_', ' ', $status)) : '—',
        };
    }
}
