<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $repository,
        private readonly FeeCalculationService $feeCalc,
        private readonly NotificationService $notificationService,
    ) {}

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getAll($filters, $perPage);
        return $paginator->withQueryString();
    }

    public function getForChild(int $childId): Collection
    {
        return $this->repository->getForChild($childId);
    }

    public function getPendingHighDiscount(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getPendingHighDiscount($perPage);
        return $paginator->withQueryString();
    }

    public function findById(int $id): Enrollment
    {
        return $this->repository->findById($id) ?? abort(404, 'Enrollment not found.');
    }

    public function create(array $data, int $createdBy, $discountFile = null): Enrollment
    {
        $schedules      = $data['schedules'] ?? [];
        $therapistId    = (int) $data['therapist_id'];

        $childId = (int) $data['child_id'];

        foreach ($schedules as $s) {
            if ($this->repository->therapistSlotOccupied($therapistId, (string) $s['day'], (string) $s['time_slot'])) {
                throw ValidationException::withMessages([
                    'schedules' => ["This therapist is already booked for {$s['day']} ({$s['time_slot']}). Choose another slot."],
                ]);
            }
            if ($this->repository->childSlotOccupied($childId, (string) $s['day'], (string) $s['time_slot'])) {
                throw ValidationException::withMessages([
                    'schedules' => ["This child already has another programme at {$s['day']} ({$s['time_slot']}). They cannot be in two sessions at the same time — pick a different slot."],
                ]);
            }
        }

        $baseCount      = count($schedules);
        $totalSessions  = $this->feeCalc->calculateTotalSessions(
            $baseCount,
            $data['repeat_weekly'] ?? false,
            $data['duration_value'] ?? null,
            $data['duration_unit'] ?? null,
        );

        $totals = $this->feeCalc->calculate(
            (float) $data['price_per_session'],
            $totalSessions,
            (float) ($data['discount_percentage'] ?? 0),
        );

        $isHighDiscount = $this->feeCalc->isHighDiscount((float) ($data['discount_percentage'] ?? 0));

        if ($isHighDiscount) {
            if (empty($data['discount_reason'])) {
                throw ValidationException::withMessages([
                    'discount_reason' => ['Discount reason is required when discount exceeds the high-discount threshold.'],
                ]);
            }
        }

        $discountFilePath = null;
        if ($discountFile) {
            $discountFilePath = $discountFile->store('enrollments/discount-files', 'public');
        }

        $status = $isHighDiscount ? 'pending_super_admin_approval' : ($data['status'] ?? 'draft');

        $enrollmentData = [
            'child_id'           => $data['child_id'],
            'branch_id'          => $data['branch_id'],
            'service_id'         => $data['service_id'],
            'therapist_id'       => $data['therapist_id'],
            'price_per_session'  => $data['price_per_session'],
            'total_sessions'     => $totalSessions,
            'subtotal'           => $totals['subtotal'],
            'discount_percentage' => $data['discount_percentage'] ?? 0,
            'discount_amount'    => $totals['discount_amount'],
            'final_total'        => $totals['final_total'],
            'paid_amount'        => 0,
            'remaining_amount'   => $totals['remaining_amount'],
            'payment_status'     => 'unpaid',
            'repeat_weekly'        => $data['repeat_weekly'] ?? false,
            'schedule_start_date'  => $data['schedule_start_date'],
            'duration_value'       => $data['duration_value'] ?? null,
            'duration_unit'      => $data['duration_unit'] ?? null,
            'discount_reason'    => $data['discount_reason'] ?? null,
            'discount_file'      => $discountFilePath,
            'status'             => $status,
            'created_by'         => $createdBy,
            'updated_by'         => $createdBy,
        ];

        $scheduleData = array_map(fn($s) => [
            'therapist_id' => $data['therapist_id'],
            'branch_id'    => $data['branch_id'],
            'day'          => $s['day'],
            'time_slot'    => $s['time_slot'],
            'status'       => 'scheduled',
        ], $schedules);

        $enrollment = $this->repository->create($enrollmentData, $scheduleData);

        $this->notificationService->notifyEnrollmentCreated($enrollment);

        if ($isHighDiscount) {
            $this->notificationService->notifyHighDiscountApprovalRequired($enrollment);
        }

        return $enrollment;
    }

    public function update(Enrollment $enrollment, array $data, int $updatedBy, $discountFile = null): Enrollment
    {
        $schedules   = $data['schedules'] ?? [];
        $therapistId = (int) $data['therapist_id'];

        $childId = (int) $data['child_id'];

        foreach ($schedules as $s) {
            if ($this->repository->therapistSlotOccupied($therapistId, (string) $s['day'], (string) $s['time_slot'], $enrollment->id)) {
                throw ValidationException::withMessages([
                    'schedules' => ["This therapist is already booked for {$s['day']} ({$s['time_slot']}). Choose another slot."],
                ]);
            }
            if ($this->repository->childSlotOccupied($childId, (string) $s['day'], (string) $s['time_slot'], $enrollment->id)) {
                throw ValidationException::withMessages([
                    'schedules' => ["This child already has another programme at {$s['day']} ({$s['time_slot']}). They cannot be in two sessions at the same time — pick a different slot."],
                ]);
            }
        }

        $baseCount = count($schedules);
        $totalSessions = $this->feeCalc->calculateTotalSessions(
            $baseCount,
            $data['repeat_weekly'] ?? false,
            $data['duration_value'] ?? null,
            $data['duration_unit'] ?? null,
        );

        $totals = $this->feeCalc->calculate(
            (float) $data['price_per_session'],
            $totalSessions,
            (float) ($data['discount_percentage'] ?? 0),
        );

        $paidSum = $enrollment->sumPaidFromPayments();
        if ($totals['final_total'] + 1e-6 < $paidSum) {
            throw ValidationException::withMessages([
                'price_per_session' => ['Total fee cannot be less than verified payments (PKR ' . number_format($paidSum, 2) . ').'],
            ]);
        }

        $isHighDiscount = $this->feeCalc->isHighDiscount((float) ($data['discount_percentage'] ?? 0));

        if ($isHighDiscount && empty($data['discount_reason'])) {
            throw ValidationException::withMessages([
                'discount_reason' => ['Discount reason is required when discount exceeds the high-discount threshold.'],
            ]);
        }

        $discountFilePath = $enrollment->discount_file;
        if ($discountFile) {
            if ($enrollment->discount_file) {
                Storage::disk('public')->delete($enrollment->discount_file);
            }
            $discountFilePath = $discountFile->store('enrollments/discount-files', 'public');
        }

        $status = $isHighDiscount ? 'pending_super_admin_approval' : ($data['status'] ?? $enrollment->status);

        $enrollmentData = [
            'child_id'             => $data['child_id'],
            'branch_id'            => $data['branch_id'],
            'service_id'           => $data['service_id'],
            'therapist_id'         => $data['therapist_id'],
            'price_per_session'    => $data['price_per_session'],
            'total_sessions'       => $totalSessions,
            'subtotal'             => $totals['subtotal'],
            'discount_percentage'  => $data['discount_percentage'] ?? 0,
            'discount_amount'      => $totals['discount_amount'],
            'final_total'          => $totals['final_total'],
            'repeat_weekly'        => $data['repeat_weekly'] ?? false,
            'schedule_start_date'  => $data['schedule_start_date'],
            'duration_value'       => $data['duration_value'] ?? null,
            'duration_unit'        => $data['duration_unit'] ?? null,
            'discount_reason'      => $data['discount_reason'] ?? null,
            'discount_file'        => $discountFilePath,
            'status'               => $status,
            'updated_by'           => $updatedBy,
        ];

        $scheduleData = array_map(fn($s) => [
            'therapist_id' => $data['therapist_id'],
            'branch_id'    => $data['branch_id'],
            'day'          => $s['day'],
            'time_slot'    => $s['time_slot'],
            'status'       => 'scheduled',
        ], $schedules);

        $enrollment = $this->repository->updateWithSchedules($enrollment, $enrollmentData, $scheduleData);

        $this->repository->recalculatePaidAmount($enrollment);

        return $enrollment->fresh(['child', 'branch', 'service', 'therapist', 'schedules']);
    }

    public function approve(Enrollment $enrollment, User $approvedBy): Enrollment
    {
        $wasHighDiscountPending = $enrollment->status === 'pending_super_admin_approval';

        $this->repository->update($enrollment, [
            'status'      => 'active',
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
        ]);

        $enrollment->refresh();
        $this->notificationService->notifyEnrollmentApproved($enrollment);
        if ($wasHighDiscountPending) {
            $this->notificationService->notifyHighDiscountApproved($enrollment);
        }

        return $enrollment;
    }

    public function reject(Enrollment $enrollment, User $rejectedBy, string $reason): Enrollment
    {
        $wasHighDiscountPending = $enrollment->status === 'pending_super_admin_approval';

        $this->repository->update($enrollment, [
            'status'           => 'rejected',
            'rejected_by'      => $rejectedBy->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        $enrollment->refresh();
        $this->notificationService->notifyEnrollmentRejected($enrollment, $reason);
        if ($wasHighDiscountPending) {
            $this->notificationService->notifyHighDiscountRejected($enrollment, $reason);
        }

        return $enrollment;
    }

    public function delete(Enrollment $enrollment): bool
    {
        if ($enrollment->paidPayments()->exists()) {
            throw ValidationException::withMessages([
                'enrollment' => ['Cannot delete an enrollment that has verified payments.'],
            ]);
        }

        return $this->repository->delete($enrollment);
    }
}
