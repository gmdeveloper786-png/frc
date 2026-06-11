<?php

namespace App\Repositories\Eloquent;

use App\Models\Enrollment;
use App\Support\Money;
use App\Models\EnrollmentSchedule;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;
use Illuminate\Support\Facades\DB;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function findById(int $id): ?Enrollment
    {
        return Enrollment::with([
            'child',
            'branch',
            'service',
            'therapist.therapistProfile',
            'createdBy',
            'approvedBy',
            'rejectedBy',
            'updatedBy',
            'schedules' => fn($q) => $q->with('therapist')->orderByRaw('session_date IS NULL')->orderBy('session_date')->orderBy('day')->orderBy('time_slot'),
            'payments' => fn($q) => $q->latest('payment_date')->latest('id'),
        ])->find($id);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = Enrollment::with(['child', 'branch', 'service', 'therapist', 'paidPayments'])
            ->when(!empty($filters['status']),         fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['branch_id']),      fn($q) => $q->where('branch_id', $filters['branch_id']))
            ->when(!empty($filters['service_id']),     fn($q) => $q->where('service_id', $filters['service_id']))
            ->when(!empty($filters['child_id']),       fn($q) => $q->where('child_id', $filters['child_id']))
            ->when(!empty($filters['payment_status']), fn($q) => $q->where('payment_status', $filters['payment_status']))
            ->when(! empty($filters['search']), function ($q) use ($filters): void {
                $term = trim((string) $filters['search']);
                $like = frc_like_pattern($term);
                $q->whereHas('child', function ($c) use ($like, $term): void {
                    $c->where(function ($inner) use ($like, $term): void {
                        $inner->where('full_name', 'like', $like)
                            ->orWhere('gr_number', 'like', $like);
                        if (ctype_digit($term)) {
                            $inner->orWhere('users.id', (int) $term);
                        }
                    });
                });
            })
            ->when(!empty($filters['date_from']),       fn($q) => $q->whereRaw('DATE(COALESCE(schedule_start_date, created_at)) >= ?', [$filters['date_from']]))
            ->when(!empty($filters['date_to']),         fn($q) => $q->whereRaw('DATE(COALESCE(schedule_start_date, created_at)) <= ?', [$filters['date_to']]))
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getEligibleForManualPayment(int $limit = 500, ?int $branchId = null): Collection
    {
        return Enrollment::query()
            ->select([
                'id',
                'child_id',
                'branch_id',
                'final_total',
                'paid_amount',
                'remaining_amount',
            ])
            ->with(['child:id,full_name'])
            ->withSum(
                ['payments as pending_verification_amount' => fn ($q) => $q->where('status', 'pending_verification')],
                'amount'
            )
            ->active()
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->where('remaining_amount', '>', 0)
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get()
            ->filter(function (Enrollment $enrollment): bool {
                $pending = (float) ($enrollment->pending_verification_amount ?? 0);
                $remaining = (float) $enrollment->getRawOriginal('remaining_amount');

                return max(0, $remaining - $pending) > 0;
            })
            ->values();
    }

    public function getForChild(int $childId): Collection
    {
        return Enrollment::with([
            'branch',
            'service',
            'therapist',
            'schedules' => fn($q) => $q->with('therapist')->orderBy('session_date')->orderBy('time_slot'),
        ])
            ->where('child_id', $childId)
            ->visibleToChild()
            ->latest()
            ->get();
    }

    public function getPendingHighDiscount(int $perPage = 15, ?int $branchId = null): LengthAwarePaginator
    {
        return Enrollment::with(['child', 'branch', 'therapist', 'createdBy'])
            ->pendingHighDiscount()
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, array $schedules): Enrollment
    {
        return DB::transaction(function () use ($data, $schedules) {
            $enrollment = Enrollment::create($data);
            foreach ($schedules as $schedule) {
                EnrollmentSchedule::create(array_merge($schedule, ['enrollment_id' => $enrollment->id]));
            }
            return $enrollment->load(['child', 'branch', 'service', 'therapist', 'schedules']);
        });
    }

    public function update(Enrollment $enrollment, array $data): Enrollment
    {
        $enrollment->update($data);
        return $enrollment->fresh(['child', 'branch', 'service', 'therapist', 'schedules']);
    }

    public function updateWithSchedules(Enrollment $enrollment, array $data, array $schedules): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $data, $schedules) {
            $enrollment->update($data);
            EnrollmentSchedule::query()->where('enrollment_id', $enrollment->id)->delete();
            foreach ($schedules as $schedule) {
                EnrollmentSchedule::create(array_merge($schedule, ['enrollment_id' => $enrollment->id]));
            }

            return $enrollment->fresh(['child', 'branch', 'service', 'therapist', 'schedules']);
        });
    }

    public function delete(Enrollment $enrollment): bool
    {
        return $enrollment->delete();
    }

    public function recalculatePaidAmount(Enrollment $enrollment): void
    {
        $paidAmount = $enrollment->sumPaidFromPayments();
        $finalTotal = Money::round($enrollment->getAttributes()['final_total'] ?? 0);
        $remaining  = Money::sub($finalTotal, $paidAmount);

        $paymentStatus = 'unpaid';
        if ($paidAmount > 0 && $remaining > 0) {
            $paymentStatus = 'partial_paid';
        } elseif ($remaining <= 0 && $paidAmount > 0) {
            $paymentStatus = 'fully_paid';
        }

        $enrollment->update([
            'paid_amount'      => $paidAmount,
            'remaining_amount' => $remaining,
            'payment_status'   => $paymentStatus,
        ]);
    }

    public function occupiedSlotPairsForTherapist(int $therapistId, ?int $excludeEnrollmentId = null): array
    {
        return EnrollmentSchedule::query()
            ->where('therapist_id', $therapistId)
            ->when($excludeEnrollmentId !== null, fn($q) => $q->where('enrollment_id', '!=', $excludeEnrollmentId))
            ->where('status', '!=', 'cancelled')
            ->whereHas('enrollment', function ($q) {
                $q->whereIn('status', [
                    'pending_super_admin_approval',
                    'approved',
                    'active',
                ]);
            })
            ->get(['day', 'time_slot'])
            ->unique(fn($row) => strtolower(trim((string) $row->day)) . '|' . trim((string) $row->time_slot))
            ->map(fn($row) => [
                'day'       => $row->day,
                'time_slot' => $row->time_slot,
            ])
            ->values()
            ->all();
    }

    public function therapistSlotOccupied(int $therapistId, string $day, string $timeSlot, ?int $excludeEnrollmentId = null): bool
    {
        $dayNorm  = strtolower(trim($day));
        $slotNorm = trim($timeSlot);

        return EnrollmentSchedule::query()
            ->where('therapist_id', $therapistId)
            ->when($excludeEnrollmentId !== null, fn($q) => $q->where('enrollment_id', '!=', $excludeEnrollmentId))
            ->where('status', '!=', 'cancelled')
            ->whereRaw('LOWER(TRIM(day)) = ?', [$dayNorm])
            ->where('time_slot', $slotNorm)
            ->whereHas('enrollment', fn($q) => $q->whereIn('status', [
                'pending_super_admin_approval',
                'approved',
                'active',
            ]))
            ->exists();
    }

    public function occupiedSlotPairsForChild(int $childId, ?int $excludeEnrollmentId = null): array
    {
        return EnrollmentSchedule::query()
            ->where('status', '!=', 'cancelled')
            ->whereHas('enrollment', function ($q) use ($childId, $excludeEnrollmentId) {
                $q->where('child_id', $childId)
                    ->whereIn('status', [
                        'pending_super_admin_approval',
                        'approved',
                        'active',
                    ]);
                if ($excludeEnrollmentId !== null) {
                    $q->where('enrollments.id', '!=', $excludeEnrollmentId);
                }
            })
            ->get(['day', 'time_slot'])
            ->unique(fn($row) => strtolower(trim((string) $row->day)) . '|' . trim((string) $row->time_slot))
            ->map(fn($row) => [
                'day'       => $row->day,
                'time_slot' => $row->time_slot,
            ])
            ->values()
            ->all();
    }

    public function childSlotOccupied(int $childId, string $day, string $timeSlot, ?int $excludeEnrollmentId = null): bool
    {
        $dayNorm  = strtolower(trim($day));
        $slotNorm = trim($timeSlot);

        return EnrollmentSchedule::query()
            ->where('status', '!=', 'cancelled')
            ->whereRaw('LOWER(TRIM(day)) = ?', [$dayNorm])
            ->where('time_slot', $slotNorm)
            ->whereHas('enrollment', function ($q) use ($childId, $excludeEnrollmentId) {
                $q->where('child_id', $childId)
                    ->whereIn('status', [
                        'pending_super_admin_approval',
                        'approved',
                        'active',
                    ]);
                if ($excludeEnrollmentId !== null) {
                    $q->where('enrollments.id', '!=', $excludeEnrollmentId);
                }
            })
            ->exists();
    }
}
