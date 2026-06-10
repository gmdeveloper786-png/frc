<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiUpdateChildRequest;
use App\Http\Requests\ApproveChildRequest;
use App\Http\Requests\ChildListFilterRequest;
use App\Http\Requests\RejectChildRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\ChildApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ChildApprovalService $approvalService,
    ) {}

    public function index(ChildListFilterRequest $request): JsonResponse
    {
        $children = $this->userRepository->getChildren($request->validated(), 15, $request->user());

        return response()->json(['data' => UserResource::collection($children)]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorize_permission($request, 'manage_children');

        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404, 'Child not found.');
        $this->authorize('viewChild', $child);

        return response()->json(['data' => new UserResource($child->load('disabilities'))]);
    }

    public function approve(ApproveChildRequest $request, int $id): JsonResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404, 'Child not found.');

        $outcome = $this->approvalService->approve($child, $request->user());

        return response()->json([
            'message' => $outcome->successMessage(),
            'data'    => new UserResource($outcome->child),
        ]);
    }

    public function reject(RejectChildRequest $request, int $id): JsonResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404, 'Child not found.');

        $updated = $this->approvalService->reject($child, $request->user(), $request->rejection_reason);

        return response()->json(['message' => 'Child rejected.', 'data' => new UserResource($updated)]);
    }

    public function update(ApiUpdateChildRequest $request, int $id): JsonResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404, 'Child not found.');
        $this->authorize('updateChild', $child);

        $updated = $this->userRepository->update($child, $request->validated());

        return response()->json(['message' => 'Updated successfully.', 'data' => new UserResource($updated)]);
    }

    private function authorize_permission(Request $request, string $permission): void
    {
        if (! $request->user()?->hasPermission($permission)) {
            abort(403, 'Unauthorized.');
        }
    }
}
