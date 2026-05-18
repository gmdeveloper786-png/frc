<?php

namespace App\Repositories\Interfaces;

use App\Models\Assessment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssessmentRepositoryInterface
{
    public function findById(int $id): ?Assessment;

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getForChild(int $childId): \Illuminate\Database\Eloquent\Collection;

    /** @return array{today:\Illuminate\Database\Eloquent\Collection, upcoming:\Illuminate\Database\Eloquent\Collection, completed:\Illuminate\Database\Eloquent\Collection, cancelled:\Illuminate\Database\Eloquent\Collection} */
    public function getTherapistAssessmentBuckets(int $therapistId): array;

    /** Published, completed, and therapist-visible cancelled assessments scheduled for today. */
    public function getTherapistTodayAssessments(int $therapistId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Lightweight counts for therapist dashboard stat cards (no full bucket loads).
     *
     * @return array{upcoming_week: int, completed: int, cancelled: int}
     */
    public function getTherapistDashboardAssessmentCounts(int $therapistId): array;

    public function getTherapistAssessmentsPaginated(int $therapistId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /** Published assessments with date after today; optional inclusive upper bound (Y-m-d). */
    public function getTherapistUpcomingPublishedAssessments(int $therapistId, ?string $dateTo = null): \Illuminate\Database\Eloquent\Collection;

    public function create(array $data, array $serviceIds, array $childIds): Assessment;

    public function update(Assessment $assessment, array $data, array $serviceIds, array $childIds): Assessment;

    public function delete(Assessment $assessment): bool;
}
