<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserNotification extends Model
{
    use SoftDeletes;

    public const TYPE_CHILD_REGISTERED = 'child_registered';

    public const TYPE_CHILD_APPROVED = 'child_approved';

    public const TYPE_CHILD_REJECTED = 'child_rejected';

    /** Assessment notifications: module assessments, record_id = assessment id. */
    public const TYPE_ASSESSMENT_SCHEDULED = 'assessment_scheduled';

    public const TYPE_ASSESSMENT_UPDATED = 'assessment_updated';

    public const TYPE_ASSESSMENT_COMPLETED = 'assessment_completed';

    public const TYPE_ASSESSMENT_CANCELLED = 'assessment_cancelled';

    /** @var list<string> */
    public const ASSESSMENT_NOTIFICATION_TYPES = [
        self::TYPE_ASSESSMENT_SCHEDULED,
        self::TYPE_ASSESSMENT_UPDATED,
        self::TYPE_ASSESSMENT_COMPLETED,
        self::TYPE_ASSESSMENT_CANCELLED,
    ];

    public const TYPE_STAFF_ACCOUNT_CREATED = 'staff_account_created';

    public const TYPE_ENROLLMENT_CREATED = 'enrollment_created';

    public const TYPE_ENROLLMENT_ACTIVE = 'enrollment_active';

    public const TYPE_ENROLLMENT_UPDATED = 'enrollment_updated';

    public const TYPE_ENROLLMENT_CANCELLED = 'enrollment_cancelled';

    public const TYPE_ENROLLMENT_ASSIGNED = 'enrollment_assigned';

    public const TYPE_ENROLLMENT_SCHEDULE_UPDATED = 'enrollment_schedule_updated';

    public const TYPE_ENROLLMENT_FEE_UPDATED = 'enrollment_fee_updated';

    /** @deprecated Use {@see TYPE_ENROLLMENT_ACTIVE} for new notifications. */
    public const TYPE_ENROLLMENT_APPROVED = 'enrollment_approved';

    /** @deprecated Use {@see TYPE_ENROLLMENT_CANCELLED} for new notifications. */
    public const TYPE_ENROLLMENT_REJECTED = 'enrollment_rejected';

    public const TYPE_HIGH_DISCOUNT_REQUESTED = 'high_discount_requested';

    public const TYPE_HIGH_DISCOUNT_APPROVED = 'high_discount_approved';

    public const TYPE_HIGH_DISCOUNT_REJECTED = 'high_discount_rejected';

    public const TYPE_PAYMENT_SLIP_UPLOADED = 'payment_slip_uploaded';

    public const TYPE_PAYMENT_APPROVED = 'payment_approved';

    public const TYPE_PAYMENT_REJECTED = 'payment_rejected';

    public const TYPE_MANUAL_PAYMENT_ADDED = 'manual_payment_added';

    public const TYPE_FEE_FULLY_PAID = 'fee_fully_paid';

    public const TYPE_SESSION_STARTED = 'session_started';

    public const TYPE_SESSION_COMPLETED = 'session_completed';

    public const TYPE_SESSION_CANCELLED = 'session_cancelled';

    public const TYPE_PROGRESS_NOTE_ADDED = 'progress_note_added';

    public const TYPE_PROGRESS_NOTE_COMPLETED = 'progress_note_completed';

    public const TYPE_CHILD_APPROVAL_EMAIL_SENT = 'child_approval_email_sent';

    public const TYPE_CHILD_APPROVAL_EMAIL_FAILED = 'child_approval_email_failed';

    /** @return array<int, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_CHILD_REGISTERED            => 'Child registered',
            self::TYPE_CHILD_APPROVED              => 'Child approved',
            self::TYPE_CHILD_REJECTED             => 'Child rejected',
            self::TYPE_ASSESSMENT_SCHEDULED        => 'Assessment scheduled',
            self::TYPE_ASSESSMENT_UPDATED         => 'Assessment updated',
            self::TYPE_ASSESSMENT_COMPLETED        => 'Assessment completed',
            self::TYPE_ASSESSMENT_CANCELLED        => 'Assessment cancelled',
            self::TYPE_STAFF_ACCOUNT_CREATED       => 'Staff account created',
            self::TYPE_ENROLLMENT_CREATED          => 'Enrollment created',
            self::TYPE_ENROLLMENT_ACTIVE           => 'Enrollment active',
            self::TYPE_ENROLLMENT_UPDATED          => 'Enrollment updated',
            self::TYPE_ENROLLMENT_CANCELLED        => 'Enrollment cancelled',
            self::TYPE_ENROLLMENT_ASSIGNED         => 'Enrollment assigned',
            self::TYPE_ENROLLMENT_SCHEDULE_UPDATED => 'Schedule updated',
            self::TYPE_ENROLLMENT_FEE_UPDATED      => 'Enrollment fee updated',
            self::TYPE_ENROLLMENT_APPROVED         => 'Enrollment approved',
            self::TYPE_ENROLLMENT_REJECTED         => 'Enrollment rejected',
            self::TYPE_HIGH_DISCOUNT_REQUESTED     => 'High discount requested',
            self::TYPE_HIGH_DISCOUNT_APPROVED      => 'High discount approved',
            self::TYPE_HIGH_DISCOUNT_REJECTED      => 'High discount rejected',
            self::TYPE_PAYMENT_SLIP_UPLOADED      => 'Payment slip uploaded',
            self::TYPE_PAYMENT_APPROVED            => 'Payment approved',
            self::TYPE_PAYMENT_REJECTED            => 'Payment rejected',
            self::TYPE_MANUAL_PAYMENT_ADDED        => 'Manual payment added',
            self::TYPE_FEE_FULLY_PAID              => 'Fee fully paid',
            self::TYPE_SESSION_STARTED             => 'Session started',
            self::TYPE_SESSION_COMPLETED           => 'Session completed',
            self::TYPE_SESSION_CANCELLED          => 'Session cancelled',
            self::TYPE_PROGRESS_NOTE_ADDED        => 'Progress note added',
            self::TYPE_PROGRESS_NOTE_COMPLETED    => 'Progress note completed',
            self::TYPE_CHILD_APPROVAL_EMAIL_SENT  => 'Child approval email sent',
            self::TYPE_CHILD_APPROVAL_EMAIL_FAILED => 'Child approval email failed',
        ];
    }

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'module',
        'record_id',
        'action_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read'  => 'boolean',
        'read_at'  => 'datetime',
        'record_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function markRead(): void
    {
        if ($this->is_read) {
            return;
        }
        $this->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();
    }

    public function markUnread(): void
    {
        $this->forceFill([
            'is_read' => false,
            'read_at' => null,
        ])->save();
    }
}
