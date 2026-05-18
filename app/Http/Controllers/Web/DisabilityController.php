<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDisabilityRequest;
use App\Models\Disability;
use App\Services\DisabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisabilityController extends Controller
{
    public function __construct(private readonly DisabilityService $service) {}

    public function index(Request $request): View
    {
        $disabilities = $this->service->getAll($request->only(['status', 'search']));

        return view('disabilities.index', compact('disabilities'));
    }

    public function create(): View
    {
        return view('disabilities.create');
    }

    public function store(StoreDisabilityRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user()->id);

        return redirect()->route('disabilities.index')->with('success', 'Disability created successfully.');
    }

    public function edit(Disability $disability): View
    {
        return view('disabilities.edit', compact('disability'));
    }

    public function update(StoreDisabilityRequest $request, Disability $disability): RedirectResponse
    {
        $this->service->update($disability, $request->validated(), $request->user()->id);

        return redirect()->route('disabilities.index')->with('success', 'Disability updated successfully.');
    }

    public function destroy(Disability $disability): RedirectResponse
    {
        $this->service->delete($disability);

        return redirect()->route('disabilities.index')->with('success', 'Disability deleted.');
    }
}
