<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $service) {}

    public function index(Request $request): JsonResponse
    {
        $branches = $this->service->getAll($request->only(['status', 'search']));

        return response()->json(['data' => BranchResource::collection($branches)]);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->service->create($request->validated(), $request->user()->id);

        return response()->json(['message' => 'Branch created.', 'data' => new BranchResource($branch)], 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        return response()->json(['data' => new BranchResource($branch)]);
    }

    public function update(StoreBranchRequest $request, Branch $branch): JsonResponse
    {
        $updated = $this->service->update($branch, $request->validated(), $request->user()->id);

        return response()->json(['message' => 'Branch updated.', 'data' => new BranchResource($updated)]);
    }

    public function destroy(StoreBranchRequest $request, Branch $branch): JsonResponse
    {
        $this->service->delete($branch);

        return response()->json(['message' => 'Branch deleted.']);
    }
}
