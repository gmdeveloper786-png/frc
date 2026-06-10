<?php

namespace App\Repositories\Eloquent;

use App\Models\Assessment;
use App\Repositories\Interfaces\AssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;
use Illuminate\Support\Facades\DB;

class AssessmentRepository implements AssessmentRepositoryInterface
{
    public function findById(int $id): ?Assessment
    {
        return Assessment::with([
            'branch',
            'services',
            'children',
            'createdBy',
            'updatedBy',
            'therapist',
            'completedBy',
            'cancelledBy',
            'assessmentNotes.therapist',
            'assessmentNotes.child',
        ])->find($id);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = Assessment::with([
            'branch',
            'services',
            'children',
            'therapist',
        ])
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['branch_id']), fn($q) => $q->where('branch_id', $filters['branch_id']))
            ->when(! empty($filters['child_id']), function ($q) use ($filters): void {
                $q->whereHas('children', fn($c) => $c->where('users.id', (int) $filters['child_id']));
            })
            ->when(! empty($filters['search']), function ($q) use ($filters): void {
                $term = trim((string) $filters['search']);
                $like = frc_like_pattern($term);
                $q->whereHas('children', function ($c) use ($like, $term): void {
                    $c->where(function ($inner) use ($like, $term): void {
                        $inner->where('full_name', 'like', $like)
                            ->orWhere('gr_number', 'like', $like);
                        if (ctype_digit($term)) {
                            $inner->orWhere('users.id', (int) $term);
                        }
                    });
                });
            })
            ->when(isset($filters['date_from']), fn($q) => $q->where('date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->where('date', '<=', $filters['date_to']))
            ->latest('date')
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getForChild(int $childId): Collection
    {
        return Assessment::with(['branch', 'services', 'therapist'])
            ->where('status', '!=', 'draft')
            ->where(function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'cancelled')
                            ->where(function ($q3) {
                                $q3->whereNull('cancelled_previous_status')
                                    ->orWhere('cancelled_previous_status', 'publish');
                            });
                    });
            })
            ->whereHas('children', fn($q) => $q->where('users.id', $childId))
            ->latest('date')
            ->get();
    }

    public function getTherapistAssessmentsPaginated(int $therapistId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = Assessment::query()
            ->with(['branch', 'services', 'children', 'therapist'])
            ->where('therapist_id', $therapistId)
            ->where('status', '!=', 'draft')
            ->where(function ($q): void {
                $q->where('status', '!=', 'cancelled')
                    ->orWhere(function ($q2): void {
                        $q2->where('status', 'cancelled')
                            ->where(function ($q3): void {
                                $q3->whereNull('cancelled_previous_status')
                                    ->orWhere('cancelled_previous_status', 'publish');
                            });
                    });
            })
            ->when(! empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['branch_id']), fn($q) => $q->where('branch_id', (int) $filters['branch_id']))
            ->when(! empty($filters['start_date']), fn($q) => $q->where('date', '>=', $filters['start_date']))
            ->when(! empty($filters['end_date']), fn($q) => $q->where('date', '<=', $filters['end_date']))
            ->when(! empty($filters['child_id']), function ($q) use ($filters): void {
                $q->whereHas('children', fn($c) => $c->where('users.id', (int) $filters['child_id']));
            })
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getTherapistTodayAssessments(int $therapistId): \Illuminate\Database\Eloquent\Collection
    {
        $with = ['branch', 'services', 'children', 'cancelledBy'];
        $today = now()->toDateString();

        return Assessment::with($with)
            ->where('therapist_id', $therapistId)
            ->whereDate('date', $today)
            ->where('status', '!=', 'draft')
            ->where(function ($q): void {
                $q->where('status', '!=', 'cancelled')
                    ->orWhere(function ($q2): void {
                        $q2->where('status', 'cancelled')
                            ->where(function ($q3): void {
                                $q3->whereNull('cancelled_previous_status')
                                    ->orWhere('cancelled_previous_status', 'publish');
                            });
                    });
            })
            ->orderBy('time')
            ->get();
    }

    public function getTherapistDashboardAssessmentCounts(int $therapistId): array
    {
        $today = now()->toDateString();
        $weekEnd = now()->copy()->addDays(7)->toDateString();
        $base = Assessment::query()->where('therapist_id', $therapistId);

        return [
            'upcoming_week' => (clone $base)
                ->where('status', 'publish')
                ->where('date', '>', $today)
                ->where('date', '<=', $weekEnd)
                ->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)
                ->where('status', 'cancelled')
                ->where(function ($q): void {
                    $q->whereNull('cancelled_previous_status')
                        ->orWhere('cancelled_previous_status', 'publish');
                })
                ->count(),
        ];
    }

    public function getTherapistUpcomingPublishedAssessments(int $therapistId, ?string $dateTo = null): Collection
    {
        $with = ['branch', 'services', 'children'];
        $today = now()->toDateString();

        return Assessment::with($with)
            ->where('therapist_id', $therapistId)
            ->where('status', 'publish')
            ->where('date', '>', $today)
            ->when($dateTo !== null, fn($q) => $q->where('date', '<=', $dateTo))
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    public function getTherapistAssessmentBuckets(int $therapistId): array
    {
        $with = ['branch', 'services', 'children', 'cancelledBy'];
        $today = now()->toDateString();

        return [
            'today' => Assessment::with($with)
                ->where('therapist_id', $therapistId)
                ->where('status', 'publish')
                ->whereDate('date', $today)
                ->orderBy('time')
                ->get(),
            'upcoming' => Assessment::with($with)
                ->where('therapist_id', $therapistId)
                ->where('status', 'publish')
                ->where('date', '>', $today)
                ->orderBy('date')
                ->orderBy('time')
                ->get(),
            'completed' => Assessment::with($with)
                ->where('therapist_id', $therapistId)
                ->where('status', 'completed')
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->limit(50)
                ->get(),
            'cancelled' => Assessment::with($with)
                ->where('therapist_id', $therapistId)
                ->where('status', 'cancelled')
                ->where(function ($q) {
                    $q->whereNull('cancelled_previous_status')
                        ->orWhere('cancelled_previous_status', 'publish');
                })
                ->orderByDesc('date')
                ->limit(50)
                ->get(),
        ];
    }

    public function create(array $data, array $serviceIds, array $childIds): Assessment
    {
        return DB::transaction(function () use ($data, $serviceIds, $childIds) {
            $assessment = Assessment::create($data);
            $assessment->services()->sync($serviceIds);
            $assessment->children()->sync($childIds);

            return $assessment->load(['branch', 'services', 'children', 'therapist']);
        });
    }

    public function update(Assessment $assessment, array $data, array $serviceIds, array $childIds): Assessment
    {
        return DB::transaction(function () use ($assessment, $data, $serviceIds, $childIds) {
            $assessment->update($data);
            $assessment->services()->sync($serviceIds);
            $assessment->children()->sync($childIds);

            return $assessment->fresh(['branch', 'services', 'children', 'therapist', 'assessmentNotes']);
        });
    }

    public function delete(Assessment $assessment): bool
    {
        return $assessment->delete();
    }
}
