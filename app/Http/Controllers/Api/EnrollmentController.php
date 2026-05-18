<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $enrollments = $this->service->getAll($request->only(['status', 'branch_id', 'child_id', 'payment_status']));

        return response()->json(['data' => EnrollmentResource::collection($enrollments)]);
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $enrollment = $this->service->create(
            $request->validated(),
            $request->user()->id,
            $request->file('discount_file'),
        );

        return response()->json(['message' => 'Enrollment created.', 'data' => new EnrollmentResource($enrollment)], 201);
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        $enrollment->load(['child', 'branch', 'service', 'therapist', 'schedules', 'payments']);

        return response()->json(['data' => new EnrollmentResource($enrollment)]);
    }

    public function update(Request $request, Enrollment $enrollment): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('manage_enrollments'), 403);

        $updated = $this->service->update($enrollment, $request->validated(), $request->user()->id);

        return response()->json(['message' => 'Enrollment updated.', 'data' => new EnrollmentResource($updated)]);
    }

    public function approve(Request $request, Enrollment $enrollment): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('approve_high_discount') && $enrollment->status === 'pending_super_admin_approval', 403, 'Only Super Admin can approve high discount enrollments.');
        abort_if(! $request->user()->hasPermission('manage_enrollments'), 403);

        $updated = $this->service->approve($enrollment, $request->user());

        return response()->json(['message' => 'Enrollment approved.', 'data' => new EnrollmentResource($updated)]);
    }

    public function reject(Request $request, Enrollment $enrollment): JsonResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);
        abort_if(! $request->user()->hasPermission('manage_enrollments'), 403);

        $updated = $this->service->reject($enrollment, $request->user(), $request->rejection_reason);

        return response()->json(['message' => 'Enrollment rejected.', 'data' => new EnrollmentResource($updated)]);
    }

    public function myEnrollment(Request $request): JsonResponse
    {
        $enrollments = $this->service->getForChild($request->user()->id);

        return response()->json(['data' => EnrollmentResource::collection($enrollments)]);
    }

    public function feeSummary(Enrollment $enrollment, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isChild() && ($enrollment->child_id !== $user->id || ! $enrollment->isVisibleToChild())) {
            abort(403);
        }

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
