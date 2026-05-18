<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Payment;
use App\Models\ProgressNote;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;

/**
 * Domain notification dispatcher — persists rows to {@see UserNotification} (per-user inbox).
 */
class NotificationService
{
    public function __construct(
        private readonly UserNotificationService $inbox,
    ) {}

    public function notifyAdminsOfNewChild(User $child): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]);
        $this->inbox->createForUsers(
            $ids,
            'New Child Registration',
            "{$child->full_name} has registered and is awaiting approval.",
            UserNotification::TYPE_CHILD_REGISTERED,
            'children',
            $child->id,
            url(route('children.pending')),
        );
    }

    public function notifyChildApproved(User $child): void
    {
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Account Approved',
            'Your account has been approved. You can now log in and access your dashboard.',
            UserNotification::TYPE_CHILD_APPROVED,
            'children',
            $child->id,
            url(route('dashboard.child')),
        );
    }

    public function notifyChildRejected(User $child, string $reason): void
    {
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Registration Update',
            'Your registration was not approved. Reason: ' . mb_substr($reason, 0, 500),
            UserNotification::TYPE_CHILD_REJECTED,
            'children',
            $child->id,
            url(route('login')),
        );
    }

    public function notifyEnrollmentCreated(Enrollment $enrollment): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]);
        $child = $enrollment->child;
        $this->inbox->createForUsers(
            $ids,
            'New Enrollment',
            ($child?->full_name ?? 'A child') . ' has a new enrollment pending review.',
            UserNotification::TYPE_ENROLLMENT_CREATED,
            'enrollments',
            $enrollment->id,
            url(route('enrollments.show', $enrollment->id)),
        );
    }

    public function notifyHighDiscountApprovalRequired(Enrollment $enrollment): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN]);
        $this->inbox->createForUsers(
            $ids,
            'High Discount Approval Required',
            'Enrollment #' . $enrollment->id . ' requires Super Admin approval for the applied discount.',
            UserNotification::TYPE_HIGH_DISCOUNT_REQUESTED,
            'enrollments',
            $enrollment->id,
            url(route('enrollments.show', $enrollment->id)),
        );
    }

    public function notifyEnrollmentApproved(Enrollment $enrollment): void
    {
        $child = $enrollment->child;
        if ($child === null) {
            return;
        }
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Enrollment Approved',
            'Your enrollment has been approved. You can view fees and schedule from your dashboard.',
            UserNotification::TYPE_ENROLLMENT_APPROVED,
            'enrollments',
            $enrollment->id,
            url(route('child.enrollment')),
        );
    }

    public function notifyEnrollmentRejected(Enrollment $enrollment, string $reason): void
    {
        $child = $enrollment->child;
        if ($child === null) {
            return;
        }
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Enrollment Rejected',
            'Your enrollment was rejected. ' . mb_substr($reason, 0, 400),
            UserNotification::TYPE_ENROLLMENT_REJECTED,
            'enrollments',
            $enrollment->id,
            url(route('child.enrollment')),
        );
    }

    public function notifyHighDiscountApproved(Enrollment $enrollment): void
    {
        $ids = $this->staffIdsByRoles([Role::ADMIN]);
        $child = $enrollment->child;
        if ($child !== null) {
            $ids[] = (int) $child->id;
        }
        $ids = array_values(array_unique(array_filter($ids)));
        $this->inbox->createForUsers(
            $ids,
            'High Discount Approved',
            'High discount enrollment #' . $enrollment->id . ' has been approved.',
            UserNotification::TYPE_HIGH_DISCOUNT_APPROVED,
            'enrollments',
            $enrollment->id,
            url(route('enrollments.show', $enrollment->id)),
        );
    }

    public function notifyHighDiscountRejected(Enrollment $enrollment, string $reason): void
    {
        $ids = $this->staffIdsByRoles([Role::ADMIN]);
        $this->inbox->createForUsers(
            $ids,
            'High Discount Rejected',
            'Enrollment #' . $enrollment->id . ' high discount request was rejected. ' . mb_substr($reason, 0, 300),
            UserNotification::TYPE_HIGH_DISCOUNT_REJECTED,
            'enrollments',
            $enrollment->id,
            url(route('enrollments.show', $enrollment->id)),
        );
    }

    public function notifyPaymentSlipUploaded(Payment $payment): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN, Role::FINANCE]);
        $child = $payment->child;
        $amount = number_format((float) $payment->amount, 2);
        $this->inbox->createForUsers(
            $ids,
            'Payment Slip Uploaded',
            ($child?->full_name ?? 'A child') . " uploaded a payment slip of PKR {$amount} pending verification.",
            UserNotification::TYPE_PAYMENT_SLIP_UPLOADED,
            'payments',
            $payment->id,
            url(route('finance.payments.pending')),
        );
    }

    public function notifyPaymentApproved(Payment $payment): void
    {
        $child = $payment->child;
        if ($child === null) {
            return;
        }
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Payment Approved',
            'Your payment of PKR ' . number_format((float) $payment->amount, 2) . ' has been verified.',
            UserNotification::TYPE_PAYMENT_APPROVED,
            'payments',
            $payment->id,
            url(route('child.payments')),
        );
    }

    public function notifyPaymentRejected(Payment $payment): void
    {
        $child = $payment->child;
        if ($child === null) {
            return;
        }
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Payment Rejected',
            'Your uploaded payment could not be verified. Please review your payment history for details.',
            UserNotification::TYPE_PAYMENT_REJECTED,
            'payments',
            $payment->id,
            url(route('child.payments')),
        );
    }

    public function notifyManualPaymentAdded(Payment $payment): void
    {
        $child = $payment->child;
        if ($child === null) {
            return;
        }
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Payment Recorded',
            'A manual payment of PKR ' . number_format((float) $payment->amount, 2) . ' has been added to your account.',
            UserNotification::TYPE_MANUAL_PAYMENT_ADDED,
            'payments',
            $payment->id,
            url(route('child.payments')),
        );
    }

    public function notifyFeeFullyPaid(Enrollment $enrollment): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN, Role::FINANCE]);
        $child = $enrollment->child;
        if ($child !== null) {
            $ids[] = (int) $child->id;
        }
        $ids = array_values(array_unique(array_filter($ids)));
        $this->inbox->createForUsers(
            $ids,
            'Fees Fully Paid',
            'Enrollment #' . $enrollment->id . ' for ' . ($child?->full_name ?? 'child') . ' is fully paid.',
            UserNotification::TYPE_FEE_FULLY_PAID,
            'enrollments',
            $enrollment->id,
            $child ? url(route('child.enrollment')) : url(route('enrollments.show', $enrollment->id)),
        );
    }

    public function notifyAssessmentPublished(Assessment $assessment): void
    {
        foreach ($assessment->children as $child) {
            $this->inbox->createForUsers(
                [(int) $child->id],
                'Assessment Scheduled',
                'Your assessment has been scheduled on ' . ($assessment->date?->format('d M Y') ?? '—') . ' at ' . ($assessment->time ?? '—') . '.',
                UserNotification::TYPE_ASSESSMENT_SCHEDULED,
                'assessments',
                $assessment->id,
                route('child.assessments.show', $assessment, false),
            );
        }

        if ($assessment->therapist) {
            $this->inbox->createForUsers(
                [(int) $assessment->therapist_id],
                'Assessment Assigned',
                'You have been assigned an assessment on ' . ($assessment->date?->format('d M Y') ?? '—') . '.',
                UserNotification::TYPE_ASSESSMENT_SCHEDULED,
                'assessments',
                $assessment->id,
                route('therapist.assessments.show', $assessment, false),
            );
        }
    }

    public function notifyAssessmentUpdated(Assessment $assessment): void
    {
        if ($assessment->therapist) {
            $this->inbox->createForUsers(
                [(int) $assessment->therapist_id],
                'Assessment Updated',
                'Assessment #' . $assessment->id . ' has been updated.',
                UserNotification::TYPE_ASSESSMENT_UPDATED,
                'assessments',
                $assessment->id,
                route('therapist.assessments.show', $assessment, false),
            );
        }

        foreach ($assessment->children as $child) {
            $this->inbox->createForUsers(
                [(int) $child->id],
                'Assessment Updated',
                'Your assessment has been updated.',
                UserNotification::TYPE_ASSESSMENT_UPDATED,
                'assessments',
                $assessment->id,
                route('child.assessments.show', $assessment, false),
            );
        }
    }

    public function notifyAssessmentCompleted(Assessment $assessment): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]);
        $this->inbox->createForUsers(
            $ids,
            'Assessment Completed',
            'Assessment #' . $assessment->id . ' has been marked completed.',
            UserNotification::TYPE_ASSESSMENT_COMPLETED,
            'assessments',
            $assessment->id,
            route('assessments.show', $assessment, false),
        );

        foreach ($assessment->children as $child) {
            $this->inbox->createForUsers(
                [(int) $child->id],
                'Assessment Completed',
                'Your assessment has been completed.',
                UserNotification::TYPE_ASSESSMENT_COMPLETED,
                'assessments',
                $assessment->id,
                route('child.assessments.show', $assessment, false),
            );
        }
    }

    public function notifyAssessmentCancelled(Assessment $assessment): void
    {
        $dateLabel = $assessment->date?->format('d M Y') ?? '—';
        $timeLabel = $assessment->time
            ? \Carbon\Carbon::parse($assessment->time)->format('g:i A')
            : '—';
        $childNames = $assessment->children->pluck('full_name')->filter()->values();
        $namesLabel = $childNames->isNotEmpty() ? $childNames->join(', ') : 'the child';

        if ($assessment->therapist) {
            $this->inbox->createForUsers(
                [(int) $assessment->therapist_id],
                'Assessment Cancelled',
                "Assessment for {$namesLabel} on {$dateLabel} at {$timeLabel} has been cancelled.",
                UserNotification::TYPE_ASSESSMENT_CANCELLED,
                'assessments',
                $assessment->id,
                route('therapist.assessments.show', $assessment, false),
            );
        }

        foreach ($assessment->children as $child) {
            $this->inbox->createForUsers(
                [(int) $child->id],
                'Assessment Cancelled',
                "Your assessment on {$dateLabel} at {$timeLabel} has been cancelled. Please contact the centre for rescheduling.",
                UserNotification::TYPE_ASSESSMENT_CANCELLED,
                'assessments',
                $assessment->id,
                route('child.assessments.show', $assessment, false),
            );
        }
    }

    public function notifySessionCancelled(EnrollmentSchedule $schedule, User $child): void
    {
        $dateLabel = $schedule->session_date?->format('d M Y') ?? ($schedule->day ?? 'scheduled day');
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Session Cancelled',
            "Your session on {$dateLabel} has been cancelled. Please contact the centre for rescheduling.",
            UserNotification::TYPE_SESSION_CANCELLED,
            'sessions',
            $schedule->id,
            url(route('child.schedule.index')),
        );

        $staffIds = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]);
        $this->inbox->createForUsers(
            $staffIds,
            'Session Cancelled',
            $child->full_name . "'s session on {$dateLabel} was cancelled.",
            UserNotification::TYPE_SESSION_CANCELLED,
            'sessions',
            $schedule->id,
            url(route('enrollments.schedule', $schedule->enrollment_id)),
        );
    }

    public function notifySessionStarted(EnrollmentSchedule $schedule, User $child): void
    {
        $this->inbox->createForUsers(
            $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]),
            'Session Started',
            'Session for ' . $child->full_name . ' was started.',
            UserNotification::TYPE_SESSION_STARTED,
            'sessions',
            $schedule->id,
            url(route('enrollments.schedule', $schedule->enrollment_id)),
        );
    }

    public function notifySessionCompleted(EnrollmentSchedule $schedule, User $child): void
    {
        $this->inbox->createForUsers(
            $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]),
            'Session Completed',
            'Session for ' . $child->full_name . ' was marked completed.',
            UserNotification::TYPE_SESSION_COMPLETED,
            'sessions',
            $schedule->id,
            url(route('enrollments.schedule', $schedule->enrollment_id)),
        );
    }

    public function notifyProgressNoteAdded(ProgressNote $note): void
    {
        $url = $note->child_id ? url(route('children.show', $note->child_id)) : url('/');

        $this->inbox->createForUsers(
            $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]),
            'Progress Note Added',
            'A progress note was added for ' . $note->child?->full_name . '.',
            UserNotification::TYPE_PROGRESS_NOTE_ADDED,
            'progress_notes',
            $note->id,
            $url,
        );
    }

    public function notifyProgressNoteCompleted(ProgressNote $note): void
    {
        $url = $note->child_id ? url(route('children.show', $note->child_id)) : url('/');

        $this->inbox->createForUsers(
            $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]),
            'Progress Note Completed',
            'Progress note for ' . $note->child?->full_name . ' was finalized.',
            UserNotification::TYPE_PROGRESS_NOTE_COMPLETED,
            'progress_notes',
            $note->id,
            $url,
        );
    }

    public function notifyChildApprovalEmailSent(User $child): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]);
        $this->inbox->createForUsers(
            $ids,
            'Child approval email sent',
            'Approval email was sent to ' . $child->full_name . ' (' . $child->email . ').',
            UserNotification::TYPE_CHILD_APPROVAL_EMAIL_SENT,
            'children',
            $child->id,
            url(route('children.show', $child->id)),
        );
    }

    public function notifyChildApprovalEmailFailed(User $child, string $error): void
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN, Role::ADMIN]);
        $this->inbox->createForUsers(
            $ids,
            'Child approval email failed',
            'Approval email could not be sent for ' . $child->full_name . '. ' . mb_substr($error, 0, 200),
            UserNotification::TYPE_CHILD_APPROVAL_EMAIL_FAILED,
            'children',
            $child->id,
            url(route('children.show', $child->id)),
        );
    }

    /**
     * @param  array<string>  $roleNames
     * @return array<int>
     */
    private function staffIdsByRoles(array $roleNames): array
    {
        return User::query()
            ->whereHas('role', fn($q) => $q->whereIn('name', $roleNames))
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
