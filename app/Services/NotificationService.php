<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Payment;
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
        $child->loadMissing('branch');
        $branchLabel = $child->branch?->name ?? 'your branch';
        $ids         = $this->staffIdsForChildRegistration($child);

        if ($ids === []) {
            return;
        }

        $this->inbox->createForUsers(
            $ids,
            'New Child Registration',
            "{$child->full_name} registered at {$branchLabel} and is awaiting approval.",
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

    public function notifyHighDiscountApprovalRequired(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('child');
        $childName = $enrollment->child?->full_name ?? 'a child';
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN]);
        $this->inbox->createForUsers(
            $ids,
            'High Discount Approval Required',
            "Enrollment for {$childName} requires high discount approval.",
            UserNotification::TYPE_HIGH_DISCOUNT_REQUESTED,
            'enrollments',
            $enrollment->id,
            route('enrollments.high-discount', [], false),
        );
    }

    public function notifyChildEnrollmentActive(Enrollment $enrollment, User $child): void
    {
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Enrollment Active',
            'Your enrollment has been active. You can now view your enrollment details and schedule.',
            UserNotification::TYPE_ENROLLMENT_ACTIVE,
            'enrollments',
            $enrollment->id,
            route('child.enrollment.show', $enrollment->id, false),
        );
    }

    public function notifyTherapistEnrollmentAssigned(Enrollment $enrollment, ?int $therapistId = null): void
    {
        $enrollment->loadMissing(['child', 'service']);
        $tid = $therapistId ?? (int) $enrollment->therapist_id;
        if ($tid <= 0) {
            return;
        }

        $childName = $enrollment->child?->full_name ?? 'A child';
        $serviceName = $enrollment->service?->name ?? 'programme';

        $this->inbox->createForUsers(
            [$tid],
            'New Child Assigned',
            "{$childName} has been assigned to you for {$serviceName}.",
            UserNotification::TYPE_ENROLLMENT_ASSIGNED,
            'enrollments',
            $enrollment->id,
            $this->therapistEnrollmentUrl($enrollment),
        );
    }

    public function notifyTherapistAssignmentRemoved(Enrollment $enrollment, int $oldTherapistId): void
    {
        $enrollment->loadMissing('child');
        $childName = $enrollment->child?->full_name ?? 'A child';

        $this->inbox->createForUsers(
            [$oldTherapistId],
            'Child Assignment Removed',
            "{$childName} is no longer assigned to you.",
            UserNotification::TYPE_ENROLLMENT_UPDATED,
            'enrollments',
            $enrollment->id,
            route('therapist.children.index', [], false),
        );
    }

    public function notifyChildTherapistUpdated(Enrollment $enrollment, User $child, string $therapistName): void
    {
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Therapist Updated',
            "Your therapist has been updated to {$therapistName}.",
            UserNotification::TYPE_ENROLLMENT_UPDATED,
            'enrollments',
            $enrollment->id,
            route('child.enrollment.show', $enrollment->id, false),
        );
    }

    public function notifyChildScheduleUpdated(Enrollment $enrollment, User $child): void
    {
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Schedule Updated',
            'Your therapy schedule has been updated. Please check your schedule.',
            UserNotification::TYPE_ENROLLMENT_SCHEDULE_UPDATED,
            'enrollments',
            $enrollment->id,
            route('child.schedule.index', [], false),
        );
    }

    public function notifyTherapistScheduleUpdated(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('child');
        $tid = (int) ($enrollment->therapist_id ?? 0);
        if ($tid <= 0) {
            return;
        }

        $childName = $enrollment->child?->full_name ?? 'child';

        $this->inbox->createForUsers(
            [$tid],
            'Schedule Updated',
            "Schedule for {$childName} has been updated.",
            UserNotification::TYPE_ENROLLMENT_SCHEDULE_UPDATED,
            'enrollments',
            $enrollment->id,
            $this->therapistScheduleUrl($enrollment),
        );
    }

    public function notifyChildServiceUpdated(Enrollment $enrollment, User $child): void
    {
        $enrollment->loadMissing('service');
        $serviceName = $enrollment->service?->name ?? '—';

        $this->inbox->createForUsers(
            [(int) $child->id],
            'Service Updated',
            "Your enrolled service has been updated to {$serviceName}.",
            UserNotification::TYPE_ENROLLMENT_UPDATED,
            'enrollments',
            $enrollment->id,
            route('child.enrollment.show', $enrollment->id, false),
        );
    }

    public function notifyChildBranchUpdated(Enrollment $enrollment, User $child): void
    {
        $enrollment->loadMissing('branch');
        $branchName = $enrollment->branch?->name ?? '—';

        $this->inbox->createForUsers(
            [(int) $child->id],
            'Branch Updated',
            "Your branch has been updated to {$branchName}.",
            UserNotification::TYPE_ENROLLMENT_UPDATED,
            'enrollments',
            $enrollment->id,
            route('child.enrollment.show', $enrollment->id, false),
        );
    }

    public function notifyChildEnrollmentCancelled(Enrollment $enrollment, User $child, ?string $reason): void
    {
        $message = 'Your enrollment has been cancelled/rejected. Please contact the centre for details.';
        if (filled($reason)) {
            $message .= ' ' . mb_substr(trim($reason), 0, 300);
        }

        $this->inbox->createForUsers(
            [(int) $child->id],
            'Enrollment Cancelled',
            $message,
            UserNotification::TYPE_ENROLLMENT_CANCELLED,
            'enrollments',
            $enrollment->id,
            route('child.enrollment', [], false),
        );
    }

    public function notifyTherapistEnrollmentCancelled(Enrollment $enrollment, int $therapistId): void
    {
        $enrollment->loadMissing('child');
        $childName = $enrollment->child?->full_name ?? 'A child';

        $this->inbox->createForUsers(
            [$therapistId],
            'Enrollment Cancelled',
            "Enrollment for {$childName} has been cancelled.",
            UserNotification::TYPE_ENROLLMENT_CANCELLED,
            'enrollments',
            $enrollment->id,
            route('therapist.children.index', [], false),
        );
    }

    public function notifyChildFeeUpdated(Enrollment $enrollment, User $child): void
    {
        $this->inbox->createForUsers(
            [(int) $child->id],
            'Fee Details Updated',
            'Your enrollment fee details have been updated.',
            UserNotification::TYPE_ENROLLMENT_FEE_UPDATED,
            'enrollments',
            $enrollment->id,
            route('child.enrollment.show', $enrollment->id, false),
        );
    }

    public function notifyFinanceEnrollmentFeeUpdated(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('child');
        $childName = $enrollment->child?->full_name ?? 'a child';
        $ids = $this->staffIdsByRoles([Role::FINANCE]);

        $this->inbox->createForUsers(
            $ids,
            'Enrollment Fee Updated',
            "Fee details for {$childName} have been updated.",
            UserNotification::TYPE_ENROLLMENT_FEE_UPDATED,
            'enrollments',
            $enrollment->id,
            route('finance.payments', [], false),
        );
    }

    public function notifyTherapistEnrollmentUpdated(Enrollment $enrollment, string $title, string $message): void
    {
        $tid = (int) ($enrollment->therapist_id ?? 0);
        if ($tid <= 0) {
            return;
        }

        $this->inbox->createForUsers(
            [$tid],
            $title,
            $message,
            UserNotification::TYPE_ENROLLMENT_UPDATED,
            'enrollments',
            $enrollment->id,
            $this->therapistEnrollmentUrl($enrollment),
        );
    }

    /** Child profile — assignment / enrollment context. */
    private function therapistEnrollmentUrl(Enrollment $enrollment): string
    {
        $childId = (int) ($enrollment->child_id ?? 0);
        if ($childId > 0) {
            return route('therapist.children.show', $childId, false);
        }

        return route('therapist.sessions.index', [], false);
    }

    /** Sessions list filtered to this child — schedule changes. */
    private function therapistScheduleUrl(Enrollment $enrollment): string
    {
        $childId = (int) ($enrollment->child_id ?? 0);
        $params = $childId > 0 ? ['child_id' => $childId] : [];

        return route('therapist.sessions.index', $params, false);
    }

    public function notifyHighDiscountApproved(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('child');
        $ids = $this->staffIdsForEnrollmentBranch($enrollment);
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
        $enrollment->loadMissing('child');
        $ids = $this->staffIdsForEnrollmentBranch($enrollment);
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
        $payment->loadMissing(['child', 'enrollment']);
        $ids   = $this->staffIdsForPaymentSlipVerification($payment);
        $child = $payment->child;
        $amount = frc_pkr($payment->amount);

        if ($ids === []) {
            return;
        }

        $this->inbox->createForUsers(
            $ids,
            'Payment Slip Uploaded',
            ($child?->full_name ?? 'A child') . " uploaded a payment slip of {$amount} pending verification.",
            UserNotification::TYPE_PAYMENT_SLIP_UPLOADED,
            'payments',
            $payment->id,
            url(route('payments.pending')),
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
            'Your payment of ' . frc_pkr($payment->amount) . ' has been verified.',
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
            'A manual payment of ' . frc_pkr($payment->amount) . ' has been added to your account.',
            UserNotification::TYPE_MANUAL_PAYMENT_ADDED,
            'payments',
            $payment->id,
            url(route('child.payments')),
        );
    }

    public function notifyFeeFullyPaid(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('child');
        $ids = $this->staffIdsForEnrollmentBranch($enrollment, includeFinance: true);
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
        $ids = $this->staffIdsForAssessmentBranch($assessment);
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

        $schedule->loadMissing('enrollment');
        $enrollment = $schedule->enrollment;
        if ($enrollment === null) {
            return;
        }
        $staffIds = $this->staffIdsForEnrollmentBranch($enrollment);
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
        $schedule->loadMissing('enrollment');
        $enrollment = $schedule->enrollment;
        if ($enrollment === null) {
            return;
        }
        $this->inbox->createForUsers(
            $this->staffIdsForEnrollmentBranch($enrollment),
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
        $schedule->loadMissing('enrollment');
        $enrollment = $schedule->enrollment;
        if ($enrollment === null) {
            return;
        }
        $this->inbox->createForUsers(
            $this->staffIdsForEnrollmentBranch($enrollment),
            'Session Completed',
            'Session for ' . $child->full_name . ' was marked completed.',
            UserNotification::TYPE_SESSION_COMPLETED,
            'sessions',
            $schedule->id,
            url(route('enrollments.schedule', $schedule->enrollment_id)),
        );
    }

    public function notifyChildApprovalEmailSent(User $child, User $approvedBy): void
    {
        $this->inbox->createForUsers(
            [(int) $approvedBy->id],
            'Child approval email sent',
            'Approval email was sent to ' . $child->full_name . ' (' . $child->email . ').',
            UserNotification::TYPE_CHILD_APPROVAL_EMAIL_SENT,
            'children',
            $child->id,
            url(route('children.show', $child->id)),
        );
    }

    public function notifyChildApprovalEmailFailed(User $child, string $error, User $approvedBy): void
    {
        $this->inbox->createForUsers(
            [(int) $approvedBy->id],
            'Child approval email failed',
            'Approval email could not be sent for ' . $child->full_name . '. ' . mb_substr($error, 0, 200),
            UserNotification::TYPE_CHILD_APPROVAL_EMAIL_FAILED,
            'children',
            $child->id,
            url(route('children.show', $child->id)),
        );
    }

    /**
     * Super admin, finance, plus branch admins for the payment's enrollment branch.
     *
     * @return array<int>
     */
    private function staffIdsForPaymentSlipVerification(Payment $payment): array
    {
        $payment->loadMissing(['enrollment', 'child']);
        $branchId = (int) ($payment->enrollment?->branch_id ?? $payment->child?->branch_id ?? 0);

        return $this->staffIdsForBranch($branchId, includeFinance: true);
    }

    /**
     * Super admin, optional finance, plus branch admins for the enrollment's branch.
     *
     * @return array<int>
     */
    private function staffIdsForEnrollmentBranch(Enrollment $enrollment, bool $includeFinance = false): array
    {
        $enrollment->loadMissing('child');
        $branchId = (int) ($enrollment->branch_id ?? $enrollment->child?->branch_id ?? 0);

        return $this->staffIdsForBranch($branchId, $includeFinance);
    }

    /**
     * @return array<int>
     */
    private function staffIdsForAssessmentBranch(Assessment $assessment): array
    {
        return $this->staffIdsForBranch((int) ($assessment->branch_id ?? 0), false);
    }

    /**
     * @return array<int>
     */
    private function staffIdsForBranch(int $branchId, bool $includeFinance = false): array
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN]);

        if ($includeFinance) {
            $ids = array_merge($ids, $this->staffIdsByRoles([Role::FINANCE]));
        }

        if ($branchId > 0) {
            $branchAdminIds = User::query()
                ->whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))
                ->where('branch_id', $branchId)
                ->whereIn('status', ['active', 'approved'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $ids = array_merge($ids, $branchAdminIds);
        }

        return array_values(array_unique($ids));
    }

    /**
     * Super admins (all branches) plus branch admins for the child's selected branch.
     *
     * @return array<int>
     */
    private function staffIdsForChildRegistration(User $child): array
    {
        $ids = $this->staffIdsByRoles([Role::SUPER_ADMIN]);

        if ($child->branch_id) {
            $branchAdminIds = User::query()
                ->whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))
                ->where('branch_id', $child->branch_id)
                ->whereIn('status', ['active', 'approved'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $ids = array_merge($ids, $branchAdminIds);
        }

        return array_values(array_unique($ids));
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
