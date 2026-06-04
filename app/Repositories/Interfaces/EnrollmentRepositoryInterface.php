<?php

namespace App\Repositories\Interfaces;

use App\Models\Enrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface EnrollmentRepositoryInterface
{
    public function findById(int $id): ?Enrollment;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getForChild(int $childId): Collection;

    /** Active enrollments with remaining balance (uses synced columns; no per-row payment queries). */
    public function getEligibleForManualPayment(int $limit = 500, ?int $branchId = null): Collection;
    public function getPendingHighDiscount(int $perPage = 15, ?int $branchId = null): LengthAwarePaginator;
    public function create(array $data, array $schedules): Enrollment;
    public function update(Enrollment $enrollment, array $data): Enrollment;

    /** Replace fee columns and all schedule rows atomically. */
    public function updateWithSchedules(Enrollment $enrollment, array $data, array $schedules): Enrollment;

    public function delete(Enrollment $enrollment): bool;
    public function recalculatePaidAmount(Enrollment $enrollment): void;

    /** Distinct day + time_slot pairs reserved by live enrollments for this therapist. */
    public function occupiedSlotPairsForTherapist(int $therapistId, ?int $excludeEnrollmentId = null): array;

    /** Distinct day + time_slot pairs for this child across their other live enrollments (same wall-clock = cannot attend two therapies). */
    public function occupiedSlotPairsForChild(int $childId, ?int $excludeEnrollmentId = null): array;

    public function therapistSlotOccupied(int $therapistId, string $day, string $timeSlot, ?int $excludeEnrollmentId = null): bool;

    public function childSlotOccupied(int $childId, string $day, string $timeSlot, ?int $excludeEnrollmentId = null): bool;
}
