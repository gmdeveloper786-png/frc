<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use App\Support\EnrollmentNotificationSnapshot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $repository,
        private readonly FeeCalculationService $feeCalc,
        private readonly EnrollmentNotificationService $enrollmentNotifications,
        private readonly SecureFileStorageService $secureFiles,
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

    public function getPendingHighDiscount(int $perPage = 15, ?int $branchId = null): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getPendingHighDiscount($perPage, $branchId);
        return $paginator->withQueryString();
    }

    public function findById(int $id): Enrollment
    {
        return $this->repository->findById($id) ?? abort(404, 'Enrollment not found.');
    }

    /**
     * @return array<int, Enrollment>
     */
    public function createEnrollments(array $data, int $createdBy, $discountFile = null): array
    {
        $schedules   = $data['schedules'] ?? [];
        $therapistId = (int) $data['therapist_id'];
        $childIds    = array_values(array_unique(array_map('intval', $data['child_ids'] ?? [])));

        if ($childIds === []) {
            throw ValidationException::withMessages([
                'child_ids' => ['Select at least one child.'],
            ]);
        }

        $isGroup = count($childIds) > 1;

        $childrenById = User::query()
            ->whereIn('id', $childIds)
            ->get(['id', 'full_name'])
            ->keyBy('id');

        foreach ($schedules as $s) {
            $day  = (string) $s['day'];
            $slot = (string) $s['time_slot'];

            if (! $isGroup && $this->repository->therapistSlotOccupied($therapistId, $day, $slot)) {
                throw ValidationException::withMessages([
                    'schedules' => ["This therapist is already booked for {$day} ({$slot}). Choose another slot."],
                ]);
            }

            if ($isGroup && $this->repository->therapistSlotOccupied($therapistId, $day, $slot)) {
                throw ValidationException::withMessages([
                    'schedules' => ["This therapist is already booked for {$day} ({$slot}). Choose another slot or therapist."],
                ]);
            }

            foreach ($childIds as $childId) {
                if ($this->repository->childSlotOccupied($childId, $day, $slot)) {
                    $name = $childrenById[$childId]?->full_name ?? 'Child';
                    throw ValidationException::withMessages([
                        'schedules' => ["{$name} already has another programme at {$day} ({$slot}). Pick a different slot or remove that child."],
                    ]);
                }
            }
        }

        $baseCount     = count($schedules);
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

        $isHighDiscount = $this->feeCalc->isHighDiscount((float) ($data['discount_percentage'] ?? 0));

        if ($isHighDiscount && empty($data['discount_reason'])) {
            throw ValidationException::withMessages([
                'discount_reason' => ['Discount reason is required when discount exceeds the high-discount threshold.'],
            ]);
        }

        $discountFilePath = null;
        if ($discountFile) {
            $discountFilePath = $this->secureFiles->store($discountFile, 'enrollments/discount-files');
        }

        $status            = $isHighDiscount ? 'pending_super_admin_approval' : ($data['status'] ?? 'draft');
        $enrollmentGroupId = $isGroup ? (string) Str::uuid() : null;

        $scheduleData = array_map(fn ($s) => [
            'therapist_id' => $data['therapist_id'],
            'branch_id'    => $data['branch_id'],
            'day'          => $s['day'],
            'time_slot'    => $s['time_slot'],
            'status'       => 'scheduled',
        ], $schedules);

        $sharedEnrollmentFields = [
            'enrollment_group_id' => $enrollmentGroupId,
            'branch_id'           => $data['branch_id'],
            'service_id'          => $data['service_id'],
            'therapist_id'        => $data['therapist_id'],
            'price_per_session'   => $data['price_per_session'],
            'total_sessions'      => $totalSessions,
            'subtotal'            => $totals['subtotal'],
            'discount_percentage' => $data['discount_percentage'] ?? 0,
            'discount_amount'     => $totals['discount_amount'],
            'final_total'         => $totals['final_total'],
            'paid_amount'         => 0,
            'remaining_amount'    => $totals['remaining_amount'],
            'payment_status'      => 'unpaid',
            'repeat_weekly'       => $data['repeat_weekly'] ?? false,
            'schedule_start_date' => $data['schedule_start_date'],
            'duration_value'      => $data['duration_value'] ?? null,
            'duration_unit'       => $data['duration_unit'] ?? null,
            'discount_reason'     => $data['discount_reason'] ?? null,
            'discount_file'       => $discountFilePath,
            'zakat_eligibility'   => $data['zakat_eligibility'],
            'status'              => $status,
            'created_by'          => $createdBy,
            'updated_by'          => $createdBy,
        ];

        return DB::transaction(function () use ($childIds, $sharedEnrollmentFields, $scheduleData): array {
            $created = [];

            foreach ($childIds as $childId) {
                $enrollment = $this->repository->create(
                    array_merge($sharedEnrollmentFields, ['child_id' => $childId]),
                    $scheduleData,
                );

                $enrollment = $enrollment->fresh(['child', 'therapist', 'service', 'branch', 'schedules']);
                $this->enrollmentNotifications->afterCreate($enrollment);
                $created[] = $enrollment;
            }

            return $created;
        });
    }

    public function create(array $data, int $createdBy, $discountFile = null): Enrollment
    {
        if (! isset($data['child_ids'])) {
            $data['child_ids'] = isset($data['child_id']) ? [(int) $data['child_id']] : [];
        }

        return $this->createEnrollments($data, $createdBy, $discountFile)[0];
    }

    public function update(Enrollment $enrollment, array $data, int $updatedBy, $discountFile = null): Enrollment
    {
        $beforeSnapshot = EnrollmentNotificationSnapshot::fromEnrollment(
            $enrollment->load(['schedules']),
        );

        $schedules   = $data['schedules'] ?? [];
        $therapistId = (int) $data['therapist_id'];
        $childId     = (int) $enrollment->child_id;

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
                'price_per_session' => ['Total fee cannot be less than verified payments (' . frc_pkr($paidSum) . ').'],
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
                $this->secureFiles->delete($enrollment->discount_file);
            }
            $discountFilePath = $this->secureFiles->store($discountFile, 'enrollments/discount-files');
        }

        $status = $isHighDiscount ? 'pending_super_admin_approval' : ($data['status'] ?? $enrollment->status);

        $enrollmentData = [
            'child_id'             => $childId,
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
            'zakat_eligibility'    => $data['zakat_eligibility'],
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

        $enrollment = $enrollment->fresh(['child', 'branch', 'service', 'therapist', 'schedules']);

        $this->enrollmentNotifications->afterUpdate($enrollment, $beforeSnapshot);

        return $enrollment;
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
        $this->enrollmentNotifications->afterApproved($enrollment->load(['child', 'therapist', 'service', 'branch']), $wasHighDiscountPending);

        return $enrollment;
    }

    public function reject(Enrollment $enrollment, User $rejectedBy, string $reason): Enrollment
    {
        $wasHighDiscountPending = $enrollment->status === 'pending_super_admin_approval';
        $beforeSnapshot = EnrollmentNotificationSnapshot::fromEnrollment(
            $enrollment->load(['schedules']),
        );

        $this->repository->update($enrollment, [
            'status'           => 'rejected',
            'rejected_by'      => $rejectedBy->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        $enrollment->refresh();
        $this->enrollmentNotifications->afterRejected(
            $enrollment->load(['child', 'therapist', 'service']),
            $reason,
            $wasHighDiscountPending,
            $beforeSnapshot,
        );

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
