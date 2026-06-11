<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use SoftDeletes;

    public const ZAKAT_ELIGIBLE = 'eligible_for_zakat';

    public const ZAKAT_NOT_ELIGIBLE = 'not_eligible_for_zakat';

    public const ZAKAT_SYED = 'syed';

    protected $fillable = [
        'child_id',
        'enrollment_group_id',
        'branch_id',
        'service_id',
        'therapist_id',
        'price_per_session',
        'total_sessions',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'final_total',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'repeat_weekly',
        'schedule_start_date',
        'duration_value',
        'duration_unit',
        'discount_reason',
        'discount_file',
        'zakat_eligibility',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price_per_session'  => 'decimal:2',
        'subtotal'           => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'final_total'        => 'decimal:2',
        'paid_amount'        => 'decimal:2',
        'remaining_amount'   => 'decimal:2',
        'repeat_weekly'      => 'boolean',
        'schedule_start_date' => 'date',
        'approved_at'        => 'datetime',
        'rejected_at'        => 'datetime',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isGroupEnrollment(): bool
    {
        return filled($this->enrollment_group_id);
    }

    /**
     * Other enrollments in the same group therapy batch (excludes this row).
     *
     * @return Collection<int, Enrollment>
     */
    public function groupMembers(): Collection
    {
        if (! $this->isGroupEnrollment()) {
            return new Collection;
        }

        return static::query()
            ->where('enrollment_group_id', $this->enrollment_group_id)
            ->where('id', '!=', $this->id)
            ->with('child:id,full_name,gr_number')
            ->orderBy('id')
            ->get();
    }

    /**
     * Total enrollments in this group (including self), or 1 when not grouped.
     */
    public function groupSize(): int
    {
        if (! $this->isGroupEnrollment()) {
            return 1;
        }

        return (int) static::query()
            ->where('enrollment_group_id', $this->enrollment_group_id)
            ->count();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EnrollmentSchedule::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paidPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'paid');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }

    public function scopeVisibleToChild($query)
    {
        return $query->whereIn('status', ['approved', 'active', 'completed']);
    }

    public function scopePendingHighDiscount($query)
    {
        return $query->where('status', 'pending_super_admin_approval');
    }

    public function isHighDiscount(): bool
    {
        return app(\App\Services\SettingService::class)
            ->isHighDiscount((float) $this->discount_percentage);
    }

    /** @return array<string, string> */
    public static function zakatEligibilityOptions(): array
    {
        return [
            self::ZAKAT_ELIGIBLE     => 'Eligible for zakat',
            self::ZAKAT_NOT_ELIGIBLE => 'Not eligible for zakat',
            self::ZAKAT_SYED         => 'Syed',
        ];
    }

    public function zakatEligibilityLabel(): ?string
    {
        if (! filled($this->zakat_eligibility)) {
            return null;
        }

        return self::zakatEligibilityOptions()[$this->zakat_eligibility]
            ?? ucfirst(str_replace('_', ' ', (string) $this->zakat_eligibility));
    }

    public function isVisibleToChild(): bool
    {
        return in_array($this->status, ['approved', 'active', 'completed'], true);
    }

    /**
     * Sum of verified (paid) payment rows using decimal-safe arithmetic.
     */
    public function sumPaidFromPayments(): float
    {
        $total = '0.00';
        $payments = $this->relationLoaded('paidPayments')
            ? $this->paidPayments
            : $this->paidPayments()->get(['amount']);

        foreach ($payments as $payment) {
            $total = bcadd($total, Money::format($payment->amount), 2);
        }

        return (float) $total;
    }

    /**
     * Sum of child/staff slips awaiting finance verification (not yet in paid_amount).
     */
    public function sumPendingVerificationAmount(): float
    {
        return Money::round(
            (float) $this->payments()->where('status', 'pending_verification')->sum('amount')
        );
    }

    /**
     * Rupees still owed (final minus sum of paid payments), rounded to 2 dp.
     */
    public function outstandingAmount(): float
    {
        $final = Money::format($this->getAttributes()['final_total'] ?? 0);

        return Money::sub($final, $this->sumPaidFromPayments());
    }

    /**
     * Balance still available to pay (slip upload or manual desk payment), excluding pending verification.
     */
    public function outstandingForSlipUpload(): float
    {
        $out = $this->outstandingAmount();
        $pending = $this->sumPendingVerificationAmount();

        return max(0, Money::sub($out, $pending));
    }

    protected function paidAmount(): Attribute
    {
        return Attribute::get(fn () => $this->sumPaidFromPayments());
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::get(fn () => $this->outstandingAmount());
    }

    /**
     * Payment bucket for display / slip eligibility from final_total vs paid_amount (not stale payment_status alone).
     */
    public function effectivePaymentStatus(): string
    {
        $final = Money::round($this->getAttributes()['final_total'] ?? 0);
        $paid  = $this->sumPaidFromPayments();

        if ($final <= 0) {
            return (string) $this->payment_status;
        }

        $out = $this->outstandingAmount();

        if ($paid > 0 && $out <= 0) {
            return 'fully_paid';
        }

        if ($paid > 0 && $out > 0) {
            return 'partial_paid';
        }

        return 'unpaid';
    }
}
