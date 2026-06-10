<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceManagementService $service) {}

    /** Published services only (for dropdowns / integrations). */
    public function published(): JsonResponse
    {
        $services = Service::published()->orderBy('name')->get();

        return response()->json(['data' => ServiceResource::collection($services)]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        if (! $request->user()->hasPermission('manage_services')) {
            $filters['status'] = 'publish';
        }

        $services = $this->service->getAll($filters);

        return response()->json(['data' => ServiceResource::collection($services)]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->service->create($request->validated(), $request->user()->id);

        return response()->json(['message' => 'Service created.', 'data' => new ServiceResource($service)], 201);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        if (! $request->user()->hasPermission('manage_services')) {
            abort_unless($service->status === 'publish', 404);
        }

        return response()->json(['data' => new ServiceResource($service)]);
    }

    public function update(StoreServiceRequest $request, Service $service): JsonResponse
    {
        $updated = $this->service->update($service, $request->validated(), $request->user()->id);

        return response()->json(['message' => 'Service updated.', 'data' => new ServiceResource($updated)]);
    }

    public function destroy(StoreServiceRequest $request, Service $service): JsonResponse
    {
        $this->service->delete($service);

        return response()->json(['message' => 'Service deleted.']);
    }
}
