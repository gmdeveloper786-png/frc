<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTherapistRequest;
use App\Http\Requests\UpdateTherapistRequest;
use App\Models\Service;
use App\Support\StaffBranchScope;
use App\Services\TherapistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapistController extends Controller
{
    public function __construct(private readonly TherapistService $service) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['branch_id', 'status', 'search']);
        if ($lockedBranch = StaffBranchScope::lockedBranchId($request->user())) {
            $filters['branch_id'] = $lockedBranch;
        }

        $therapists = $this->service->getAll($filters);
        $branches   = StaffBranchScope::publishedBranchesFor($request->user());

        return view('therapists.index', compact('therapists', 'branches'));
    }

    public function create(Request $request): View
    {
        $branches = StaffBranchScope::publishedBranchesFor($request->user());
        $services = Service::published()->orderBy('name')->get();

        return view('therapists.create', compact('branches', 'services'));
    }

    public function store(StoreTherapistRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['documents']);
        $this->service->create($data, $request->file('documents', []));

        return redirect()->route('therapists.index')->with('success', 'Therapist created successfully.');
    }

    public function show(Request $request, int $id): View
    {
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);

        return view('therapists.show', compact('therapist'));
    }

    public function edit(Request $request, int $id): View
    {
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $branches  = StaffBranchScope::publishedBranchesFor($request->user());
        $services  = Service::published()->orderBy('name')->get();

        return view('therapists.edit', compact('therapist', 'branches', 'services'));
    }

    public function update(UpdateTherapistRequest $request, int $id): RedirectResponse
    {
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $data = $request->safe()->except(['documents']);
        $this->service->update($therapist, $data, $request->file('documents', []));

        return redirect()->route('therapists.index')->with('success', 'Therapist updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $therapist = $this->service->findById($id);
        StaffBranchScope::enforceTherapistBranch($request->user(), $therapist);
        $this->service->delete($therapist);

        return redirect()->route('therapists.index')->with('success', 'Therapist deleted.');
    }
}
