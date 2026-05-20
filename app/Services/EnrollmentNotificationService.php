<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\EnrollmentNotificationSnapshot;

/**
 * Enrollment create/update notification rules (meaningful business changes only).
 */
class EnrollmentNotificationService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function afterCreate(Enrollment $enrollment): void
    {
        $enrollment->loadMissing(['child', 'therapist', 'service', 'branch']);

        $status = (string) $enrollment->status;

        if ($status === 'draft') {
            return;
        }

        if ($status === 'pending_super_admin_approval') {
            $this->notifications->notifyHighDiscountApprovalRequired($enrollment);

            return;
        }

        if ($status === 'active') {
            $this->notifyEnrollmentActive($enrollment);
        }
    }

    public function afterUpdate(Enrollment $enrollment, EnrollmentNotificationSnapshot $before): void
    {
        $enrollment->loadMissing(['child', 'therapist', 'service', 'branch', 'schedules']);
        $after = EnrollmentNotificationSnapshot::fromEnrollment($enrollment);

        if ($before->status !== $after->status) {
            $this->handleStatusChange($enrollment, $before, $after);

            return;
        }

        if ($before->status === 'pending_super_admin_approval' && $after->status === 'pending_super_admin_approval') {
            // High discount still pending — no spam on minor edits.
            return;
        }

        if ($before->status === 'draft' && $after->status === 'draft') {
            return;
        }

        if (! $this->shouldNotifyForActiveEnrollment($after, $before)) {
            return;
        }

        if ($before->therapistId !== $after->therapistId) {
            $this->notifyTherapistChanged($enrollment, $before->therapistId, $after->therapistId);
        }

        if ($before->schedulesFingerprint !== $after->schedulesFingerprint) {
            $this->notifyScheduleChanged($enrollment);
        }

        if ($before->serviceId !== $after->serviceId) {
            $this->notifyServiceChanged($enrollment);
        }

        if ($before->branchId !== $after->branchId) {
            $this->notifyBranchChanged($enrollment);
        }

        if ($before->finalTotal !== $after->finalTotal || $before->paymentStatus !== $after->paymentStatus) {
            $this->notifyFeeChanged($enrollment);
        }
    }

    public function afterApproved(Enrollment $enrollment, bool $wasHighDiscountPending): void
    {
        $enrollment->loadMissing(['child', 'therapist', 'service', 'branch']);
        $this->notifyEnrollmentActive($enrollment);

        if ($wasHighDiscountPending) {
            $this->notifications->notifyHighDiscountApproved($enrollment);
        }
    }

    public function afterRejected(
        Enrollment $enrollment,
        string $reason,
        bool $wasHighDiscountPending,
        EnrollmentNotificationSnapshot $before,
    ): void {
        $enrollment->loadMissing(['child', 'therapist', 'service']);
        $this->notifyEnrollmentCancelled($enrollment, $reason, $before);

        if ($wasHighDiscountPending) {
            $this->notifications->notifyHighDiscountRejected($enrollment, $reason);
        }
    }

    private function handleStatusChange(
        Enrollment $enrollment,
        EnrollmentNotificationSnapshot $before,
        EnrollmentNotificationSnapshot $after,
    ): void {
        if ($after->status === 'pending_super_admin_approval' && $before->status !== 'pending_super_admin_approval') {
            $this->notifications->notifyHighDiscountApprovalRequired($enrollment);

            return;
        }

        if ($after->status === 'active' && $before->status !== 'active') {
            $this->notifyEnrollmentActive($enrollment);

            return;
        }

        if (in_array($after->status, ['rejected', 'cancelled'], true)
            && ! in_array($before->status, ['rejected', 'cancelled'], true)) {
            $this->notifyEnrollmentCancelled($enrollment, $enrollment->rejection_reason, $before);
        }
    }

    private function shouldNotifyForActiveEnrollment(
        EnrollmentNotificationSnapshot $after,
        EnrollmentNotificationSnapshot $before,
    ): bool {
        if ($after->status === 'active') {
            return true;
        }

        return $before->isChildNotifiable() && $after->isChildNotifiable();
    }

    public function notifyEnrollmentActive(Enrollment $enrollment): void
    {
        $child = $enrollment->child;
        if ($child !== null) {
            $this->notifications->notifyChildEnrollmentActive($enrollment, $child);
        }

        if ($enrollment->therapist_id) {
            $this->notifications->notifyTherapistEnrollmentAssigned($enrollment);
        }
    }

    private function notifyEnrollmentCancelled(
        Enrollment $enrollment,
        ?string $reason,
        ?EnrollmentNotificationSnapshot $before = null,
    ): void {
        $child = $enrollment->child;
        $notifyChild = $before === null || $before->isChildNotifiable();

        if ($child !== null && $notifyChild) {
            $this->notifications->notifyChildEnrollmentCancelled($enrollment, $child, $reason);
        }

        $therapistId = (int) ($enrollment->therapist_id ?? 0);
        if ($therapistId > 0 && ($before === null || $before->status === 'active')) {
            $this->notifications->notifyTherapistEnrollmentCancelled($enrollment, $therapistId);
        }
    }

    private function notifyTherapistChanged(Enrollment $enrollment, ?int $oldTherapistId, ?int $newTherapistId): void
    {
        $child = $enrollment->child;
        $therapist = $enrollment->therapist;

        if ($child !== null) {
            $this->notifications->notifyChildTherapistUpdated($enrollment, $child, $therapist?->full_name ?? '—');
        }

        if ($newTherapistId && $newTherapistId > 0) {
            $this->notifications->notifyTherapistEnrollmentAssigned($enrollment, $newTherapistId);
        }

        if ($oldTherapistId && $oldTherapistId > 0 && $oldTherapistId !== $newTherapistId) {
            $this->notifications->notifyTherapistAssignmentRemoved($enrollment, $oldTherapistId);
        }
    }

    private function notifyScheduleChanged(Enrollment $enrollment): void
    {
        $child = $enrollment->child;
        if ($child !== null) {
            $this->notifications->notifyChildScheduleUpdated($enrollment, $child);
        }

        if ($enrollment->therapist_id) {
            $this->notifications->notifyTherapistScheduleUpdated($enrollment);
        }
    }

    private function notifyServiceChanged(Enrollment $enrollment): void
    {
        $child = $enrollment->child;
        if ($child !== null) {
            $this->notifications->notifyChildServiceUpdated($enrollment, $child);
        }

        if ($enrollment->therapist_id) {
            $this->notifications->notifyTherapistEnrollmentUpdated($enrollment, 'Service Updated', 'Service for ' . ($child?->full_name ?? 'child') . ' has been updated to ' . ($enrollment->service?->name ?? '—') . '.');
        }
    }

    private function notifyBranchChanged(Enrollment $enrollment): void
    {
        $child = $enrollment->child;
        if ($child !== null) {
            $this->notifications->notifyChildBranchUpdated($enrollment, $child);
        }

        if ($enrollment->therapist_id) {
            $this->notifications->notifyTherapistEnrollmentUpdated($enrollment, 'Branch Updated', 'Branch for ' . ($child?->full_name ?? 'child') . ' has been updated to ' . ($enrollment->branch?->name ?? '—') . '.');
        }
    }

    private function notifyFeeChanged(Enrollment $enrollment): void
    {
        $child = $enrollment->child;
        if ($child !== null) {
            $this->notifications->notifyChildFeeUpdated($enrollment, $child);
        }

        $this->notifications->notifyFinanceEnrollmentFeeUpdated($enrollment);
    }
}
