<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTherapistRequest;
use App\Http\Requests\UpdateTherapistRequest;
use App\Http\Resources\TherapistResource;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use App\Services\TherapistService;
use App\Support\StaffBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TherapistController extends Controller
{
    public function __construct(
        private readonly TherapistService $service,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeTherapistCatalogAccess($request);
        $filters = $request->only(['branch_id', 'status', 'search']);
        if ($locked = StaffBranchScope::lockedBranchId($request->user())) {
            $filters['branch_id'] = $locked;
        }

        $therapists = $this->service->getAll($filters);

        return response()->json(['data' => TherapistResource::collection($therapists)]);
    }

    public function store(StoreTherapistRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['documents']);
        $therapist = $this->service->create($data, $request->file('documents', []));

        return response()->json(['message' => 'Therapist created.', 'data' => new TherapistResource($therapist)], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeTherapistCatalogAccess($request);
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);

        return response()->json(['data' => new TherapistResource($therapist)]);
    }

    public function update(UpdateTherapistRequest $request, int $id): JsonResponse
    {
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $data = $request->safe()->except(['documents']);
        $updated   = $this->service->update($therapist, $data, $request->file('documents', []));

        return response()->json(['message' => 'Therapist updated.', 'data' => new TherapistResource($updated)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->hasPermission('manage_therapists'), 403);
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $this->service->delete($therapist);

        return response()->json(['message' => 'Therapist deleted.']);
    }

    public function byBranch(Request $request, int $branch): JsonResponse
    {
        $this->authorizeTherapistCatalogAccess($request);
        StaffBranchScope::enforceBranchCatalogAccess($request->user(), $branch);

        $serviceIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->query('service_ids', [])
        )));

        $match = $request->query('service_match', 'all');
        $serviceMatch = $match === 'any' ? 'any' : 'all';

        $therapists = $this->service->getByBranch($branch, $serviceIds, $serviceMatch);

        return response()->json(['data' => TherapistResource::collection($therapists)]);
    }

    public function availableDays(Request $request, int $id): JsonResponse
    {
        $this->authorizeTherapistCatalogAccess($request);
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $days      = $this->service->getAvailableDays($therapist);

        return response()->json(['data' => $days]);
    }

    public function availableSlots(Request $request, int $id): JsonResponse
    {
        $this->authorizeTherapistCatalogAccess($request);
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $slots     = $this->service->getAvailableSlots($therapist);

        return response()->json(['data' => $slots]);
    }

    /** Day + time_slot pairs already tied to pending / approved / active enrollments (blocks double-booking). */
    public function occupiedSlots(Request $request, int $id): JsonResponse
    {
        $this->authorizeTherapistCatalogAccess($request);
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $raw  = $request->query('exclude_enrollment');
        $exId = ($raw !== null && $raw !== '') ? (int) $raw : null;
        $exId = $exId > 0 ? $exId : null;

        $therapistPairs = $this->enrollmentRepository->occupiedSlotPairsForTherapist($id, $exId);
        $merged         = Collection::make($therapistPairs);

        $childRaw = $request->query('child_id');
        $childId  = ($childRaw !== null && $childRaw !== '') ? (int) $childRaw : 0;
        if ($childId > 0) {
            $merged = $merged->concat(
                $this->enrollmentRepository->occupiedSlotPairsForChild($childId, $exId)
            );
        }

        $unique = $merged
            ->unique(fn (array $row): string => strtolower(trim((string) ($row['day'] ?? ''))) . '|' . trim((string) ($row['time_slot'] ?? '')))
            ->values()
            ->all();

        return response()->json(['data' => $unique]);
    }

    private function authorizeTherapistCatalogAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user->hasPermission('manage_therapists')
            || $user->hasPermission('manage_enrollments')
            || $user->hasPermission('manage_assessments'),
            403,
        );
    }
}
