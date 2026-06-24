<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDisabilityRequest;
use App\Http\Resources\DisabilityResource;
use App\Models\Disability;
use App\Services\DisabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisabilityController extends Controller
{
    public function __construct(private readonly DisabilityService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        if (! $request->user()->hasPermission('manage_disabilities')) {
            $filters['status'] = 'publish';
        }

        $disabilities = $this->service->getAll($filters);

        return response()->json(['data' => DisabilityResource::collection($disabilities)]);
    }

    public function store(StoreDisabilityRequest $request): JsonResponse
    {
        $disability = $this->service->create($request->validated(), $request->user()->id);

        return response()->json(['message' => 'Present complaint created.', 'data' => new DisabilityResource($disability)], 201);
    }

    public function show(Request $request, Disability $disability): JsonResponse
    {
        if (! $request->user()->hasPermission('manage_disabilities')) {
            abort_unless($disability->status === 'publish', 404);
        }

        return response()->json(['data' => new DisabilityResource($disability)]);
    }

    public function update(StoreDisabilityRequest $request, Disability $disability): JsonResponse
    {
        $updated = $this->service->update($disability, $request->validated(), $request->user()->id);

        return response()->json(['message' => 'Present complaint updated.', 'data' => new DisabilityResource($updated)]);
    }

    public function destroy(StoreDisabilityRequest $request, Disability $disability): JsonResponse
    {
        $this->service->delete($disability);

        return response()->json(['message' => 'Present complaint permanently deleted.']);
    }
}
