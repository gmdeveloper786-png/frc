<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveChildRequest;
use App\Http\Requests\RejectChildRequest;
use App\Http\Requests\UpdateChildRequest;
use App\Models\Disability;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\ChildApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff-facing CRUD & approvals for child accounts (`/children/...`, routes `children.*`).
 *
 * Do not mix authenticated child portal features here — use dedicated controllers
 * (e.g. ChildEnrollmentController, ChildScheduleController, ChildPaymentController)
 * under `/my-*` routes so admin tooling stays isolated from the child's own login UX.
 */
class ChildController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ChildApprovalService $approvalService,
    ) {}

    public function index(Request $request): View
    {
        $children = $this->userRepository->getChildren($request->only(['status', 'search']), 15, $request->user());

        return view('children.index', compact('children'));
    }

    public function pendingApprovals(Request $request): View
    {
        $children = $this->userRepository->getPendingChildren(15, $request->user());

        return view('children.pending', compact('children'));
    }

    public function show(Request $request, int $id): View
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('viewChild', $child);
        $child->load(['disabilities', 'branch', 'enrollments.branch', 'assessments.branch']);

        return view('children.show', compact('child'));
    }

    public function edit(Request $request, int $id): View
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('updateChild', $child);
        $child->load('disabilities');
        $disabilities = Disability::published()->orderBy('name')->get();

        return view('children.edit', compact('child', 'disabilities'));
    }

    public function update(UpdateChildRequest $request, int $id): RedirectResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('updateChild', $child);

        $data           = $request->validated();
        $disabilityIds  = array_map('intval', $data['disability_ids'] ?? []);
        unset($data['disability_ids'], $data['other_disability']);

        $otherId = Disability::otherId();
        $hasOther = $otherId !== null && in_array((int) $otherId, $disabilityIds, true);
        $data['other_disability'] = $hasOther && filled($request->input('other_disability'))
            ? trim((string) $request->input('other_disability'))
            : null;

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $this->userRepository->update($child, $data);
        $child->disabilities()->sync($disabilityIds);

        return redirect()->route('children.show', $child->id)->with('success', 'Child profile updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('deleteChild', $child);

        $name = $child->full_name;
        $this->userRepository->delete($child);

        return redirect()->route('children.index')->with('success', "{$name} has been removed from the system.");
    }

    public function approve(ApproveChildRequest $request, int $id): RedirectResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('approveChild', $child);

        $outcome = $this->approvalService->approve($child, $request->user());

        return redirect()->back()->with('success', $outcome->successMessage());
    }

    public function reject(RejectChildRequest $request, int $id): RedirectResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('approveChild', $child);

        $this->approvalService->reject($child, $request->user(), $request->rejection_reason);

        return redirect()->back()->with('success', "{$child->full_name}'s registration has been rejected.");
    }
}
