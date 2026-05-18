<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\TherapistResource;
use App\Repositories\Interfaces\EnrollmentRepositoryInterface;
use App\Services\TherapistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AjaxTherapistController extends Controller
{
    public function __construct(
        private readonly TherapistService $therapistService,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
    ) {}

    public function therapistsByBranch(Request $request, int $branch): JsonResponse
    {
        $serviceIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->query('service_ids', [])
        )));

        $match = $request->query('service_match', 'any');
        $serviceMatch = $match === 'all' ? 'all' : 'any';

        $therapists = $this->therapistService->getByBranch($branch, $serviceIds, $serviceMatch);

        return response()->json(['data' => TherapistResource::collection($therapists)]);
    }

    public function availableDays(int $therapist): JsonResponse
    {
        $user = $this->therapistService->findById($therapist);

        return response()->json(['data' => $this->therapistService->getAvailableDays($user)]);
    }

    public function availableSlots(int $therapist): JsonResponse
    {
        $user = $this->therapistService->findById($therapist);

        return response()->json(['data' => $this->therapistService->getAvailableSlots($user)]);
    }

    public function occupiedSlots(Request $request, int $therapist): JsonResponse
    {
        $this->therapistService->findById($therapist);

        $raw  = $request->query('exclude_enrollment');
        $exId = ($raw !== null && $raw !== '') ? (int) $raw : null;
        $exId = $exId > 0 ? $exId : null;

        $therapistPairs = $this->enrollmentRepository->occupiedSlotPairsForTherapist($therapist, $exId);
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
