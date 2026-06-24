<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Models\Service;
use App\Services\ServiceManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceManagementService $service) {}

    public function index(Request $request): View
    {
        $services = $this->service->getAll($request->only(['status', 'search']));

        return view('services.index', compact('services'));
    }

    public function create(): View
    {
        return view('services.create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user()->id);

        return redirect()->route('services.index')->with('success', 'Service created successfully.');
    }

    public function edit(Service $service): View
    {
        $service->load(['feedbackQuestions' => fn ($q) => $q->active()->ordered()]);

        return view('services.edit', compact('service'));
    }

    public function update(StoreServiceRequest $request, Service $service): RedirectResponse
    {
        $this->service->update($service, $request->validated(), $request->user()->id);

        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->service->delete($service);

        return redirect()->route('services.index')->with('success', 'Service permanently deleted.');
    }
}
