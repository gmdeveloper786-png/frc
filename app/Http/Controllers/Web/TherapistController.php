<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTherapistRequest;
use App\Http\Requests\UpdateTherapistRequest;
use App\Models\Branch;
use App\Models\Service;
use App\Services\TherapistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapistController extends Controller
{
    public function __construct(private readonly TherapistService $service) {}

    public function index(Request $request): View
    {
        $therapists = $this->service->getAll($request->only(['branch_id', 'status', 'search']));
        $branches   = Branch::published()->orderBy('name')->get();

        return view('therapists.index', compact('therapists', 'branches'));
    }

    public function create(): View
    {
        $branches = Branch::published()->orderBy('name')->get();
        $services = Service::published()->orderBy('name')->get();

        return view('therapists.create', compact('branches', 'services'));
    }

    public function store(StoreTherapistRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('therapists.index')->with('success', 'Therapist created successfully.');
    }

    public function show(int $id): View
    {
        $therapist = $this->service->findById($id);

        return view('therapists.show', compact('therapist'));
    }

    public function edit(int $id): View
    {
        $therapist = $this->service->findById($id);
        $branches  = Branch::published()->orderBy('name')->get();
        $services  = Service::published()->orderBy('name')->get();

        return view('therapists.edit', compact('therapist', 'branches', 'services'));
    }

    public function update(UpdateTherapistRequest $request, int $id): RedirectResponse
    {
        $therapist = $this->service->findById($id);
        $this->service->update($therapist, $request->validated());

        return redirect()->route('therapists.index')->with('success', 'Therapist updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $therapist = $this->service->findById($id);
        $this->service->delete($therapist);

        return redirect()->route('therapists.index')->with('success', 'Therapist deleted.');
    }
}
