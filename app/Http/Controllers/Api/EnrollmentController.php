<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollmentListFilterRequest;
use App\Http\Requests\RejectEnrollmentRequest;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use App\Support\StaffBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $service) {}

    public function index(EnrollmentListFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        if ($lockedBranch = StaffBranchScope::lockedBranchId($request->user())) {
            $filters['branch_id'] = $lockedBranch;
        }

        $enrollments = $this->service->getAll($filters);

        return response()->json(['data' => EnrollmentResource::collection($enrollments)]);
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $enrollments = $this->service->createEnrollments(
            $request->validated(),
            $request->user()->id,
            $request->file('discount_file'),
        );

        $groupId = $enrollments[0]->enrollment_group_id;

        return response()->json([
            'message' => count($enrollments) > 1
                ? 'Group enrollment created for ' . count($enrollments) . ' children.'
                : 'Enrollment created.',
            'enrollment_group_id' => $groupId,
            'data' => EnrollmentResource::collection(collect($enrollments)),
        ], 201);
    }

    public function show(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorize('view', $enrollment);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $enrollment->load(['child', 'branch', 'service', 'therapist', 'schedules', 'payments']);

        return response()->json(['data' => new EnrollmentResource($enrollment)]);
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): JsonResponse
    {
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $this->authorize('update', $enrollment);
        $updated = $this->service->update($enrollment, $request->validated(), $request->user()->id);

        return response()->json(['message' => 'Enrollment updated.', 'data' => new EnrollmentResource($updated)]);
    }

    public function approve(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorize('approve', $enrollment);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);

        $updated = $this->service->approve($enrollment, $request->user());

        return response()->json(['message' => 'Enrollment approved.', 'data' => new EnrollmentResource($updated)]);
    }

    public function reject(RejectEnrollmentRequest $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorize('update', $enrollment);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $updated = $this->service->reject($enrollment, $request->user(), $request->validated('rejection_reason'));

        return response()->json(['message' => 'Enrollment rejected.', 'data' => new EnrollmentResource($updated)]);
    }

    public function myEnrollment(Request $request): JsonResponse
    {
        $enrollments = $this->service->getForChild($request->user()->id);

        return response()->json(['data' => EnrollmentResource::collection($enrollments)]);
    }

    public function feeSummary(Enrollment $enrollment, Request $request): JsonResponse
    {
        $this->authorize('view', $enrollment);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);

        return response()->json([
            'data' => [
                'final_total'      => $enrollment->final_total,
                'paid_amount'      => $enrollment->paid_amount,
                'remaining_amount' => $enrollment->remaining_amount,
                'payment_status'   => $enrollment->payment_status,
            ],
        ]);
    }
}
