<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateStaffUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\StaffUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffUserController extends Controller
{
    public function __construct(private readonly StaffUserService $staffUsers) {}

    public function index(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        return UserResource::collection(
            $this->staffUsers->getStaffUsers($request->only(['search', 'role', 'status']))
        );
    }

    public function store(StoreStaffUserRequest $request): JsonResponse
    {
        $user = $this->staffUsers->createStaffUser($request->validated(), $request->user());

        return response()->json(['data' => new UserResource($user->load('role'))], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $this->staffUsers->ensureStaffUser($user);

        return response()->json(['data' => new UserResource($user->load('role'))]);
    }

    public function update(UpdateStaffUserRequest $request, User $user): JsonResponse
    {
        $this->staffUsers->ensureStaffUser($user);
        $updated = $this->staffUsers->updateStaffUser($user, $request->validated(), $request->user());

        return response()->json(['data' => new UserResource($updated->load('role'))]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $this->staffUsers->ensureStaffUser($user);
        $this->staffUsers->deleteStaffUser($user, $request->user());

        return response()->json(['message' => 'Staff user permanently deleted.']);
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $this->staffUsers->ensureStaffUser($user);
        $updated = $this->staffUsers->toggleUserStatus($user, $request->user());

        return response()->json(['data' => new UserResource($updated->load('role'))]);
    }
}
