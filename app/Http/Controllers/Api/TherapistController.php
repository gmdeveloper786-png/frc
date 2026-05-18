<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTherapistRequest;
use App\Http\Requests\UpdateTherapistRequest;
use App\Http\Resources\TherapistResource;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use App\Services\TherapistService;
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
        $therapists = $this->service->getAll($request->only(['branch_id', 'status', 'search']));

        return response()->json(['data' => TherapistResource::collection($therapists)]);
    }

    public function store(StoreTherapistRequest $request): JsonResponse
    {
        $therapist = $this->service->create($request->validated());

        return response()->json(['message' => 'Therapist created.', 'data' => new TherapistResource($therapist)], 201);
    }

    public function show(int $id): JsonResponse
    {
        $therapist = $this->service->findById($id);

        return response()->json(['data' => new TherapistResource($therapist)]);
    }

    public function update(UpdateTherapistRequest $request, int $id): JsonResponse
    {
        $therapist = $this->service->findById($id);
        $updated   = $this->service->update($therapist, $request->validated());

        return response()->json(['message' => 'Therapist updated.', 'data' => new TherapistResource($updated)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $therapist = $this->service->findById($id);
        $this->service->delete($therapist);

        return response()->json(['message' => 'Therapist deleted.']);
    }

    public function byBranch(Request $request, int $branch): JsonResponse
    {
        $serviceIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->query('service_ids', [])
        )));

        $match = $request->query('service_match', 'all');
        $serviceMatch = $match === 'any' ? 'any' : 'all';

        $therapists = $this->service->getByBranch($branch, $serviceIds, $serviceMatch);

        return response()->json(['data' => TherapistResource::collection($therapists)]);
    }

    public function availableDays(int $id): JsonResponse
    {
        $therapist = $this->service->findById($id);
        $days      = $this->service->getAvailableDays($therapist);

        return response()->json(['data' => $days]);
    }

    public function availableSlots(int $id): JsonResponse
    {
        $therapist = $this->service->findById($id);
        $slots     = $this->service->getAvailableSlots($therapist);

        return response()->json(['data' => $slots]);
    }

    /** Day + time_slot pairs already tied to pending / approved / active enrollments (blocks double-booking). */
    public function occupiedSlots(Request $request, int $id): JsonResponse
    {
        $this->service->findById($id);
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
}
