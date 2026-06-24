<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveChildRequest;
use App\Http\Requests\ChildListFilterRequest;
use App\Http\Requests\RejectChildRequest;
use App\Http\Requests\StoreStaffChildRequest;
use App\Http\Requests\UpdateChildRequest;
use App\Models\Disability;
use App\Models\Role;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\ChildApprovalService;
use App\Services\ChildRegistrationService;
use App\Services\SecureFileStorageService;
use App\Support\StaffBranchScope;
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
        private readonly ChildRegistrationService $registrationService,
        private readonly SecureFileStorageService $secureFiles,
    ) {}

    public function index(ChildListFilterRequest $request): View
    {
        $children = $this->userRepository->getChildren($request->validated(), 15, $request->user());

        return view('children.index', compact('children'));
    }

    public function pendingApprovals(Request $request): View
    {
        $children = $this->userRepository->getPendingChildren(15, $request->user());

        return view('children.pending', compact('children'));
    }

    public function create(Request $request): View
    {
        $disabilities  = Disability::published()->orderedForPicker()->get();
        $branches      = StaffBranchScope::publishedBranchesFor($request->user());
        $lockedBranch  = StaffBranchScope::lockedBranchId($request->user());

        return view('children.create', compact('disabilities', 'branches', 'lockedBranch'));
    }

    public function store(StoreStaffChildRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['documents']);

        $child = $this->registrationService->registerByStaff(
            $data,
            $request->user(),
            $request->file('documents', []),
        );

        $message = "{$child->full_name} has been registered and approved.";
        if (filled($child->gr_number)) {
            $message .= " GR Number: {$child->gr_number}.";
        }

        return redirect()
            ->route('children.show', $child->id)
            ->with('success', $message);
    }

    public function show(Request $request, int $id): View
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('viewChild', $child);
        $enrollmentsCount = $child->enrollments()->count();
        $assessmentsCount = $child->assessments()->count();

        $child->load([
            'disabilities',
            'branch',
            'enrollments' => fn ($q) => $q
                ->with(['branch', 'service', 'therapist'])
                ->latest('id')
                ->limit(5),
            'assessments' => fn ($q) => $q
                ->with(['branch', 'services'])
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->limit(5),
        ]);

        return view('children.show', compact('child', 'enrollmentsCount', 'assessmentsCount'));
    }

    public function edit(Request $request, int $id): View
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('updateChild', $child);
        $child->load('disabilities');
        $disabilities = Disability::published()->orderedForPicker()->get();

        return view('children.edit', compact('child', 'disabilities'));
    }

    public function update(UpdateChildRequest $request, int $id): RedirectResponse
    {
        $child = $this->userRepository->findById($id);
        abort_if(! $child || ! $child->isChild(), 404);
        $this->authorize('updateChild', $child);

        $data           = $request->validated();
        $disabilityIds  = array_map('intval', $data['disability_ids'] ?? []);
        unset($data['disability_ids'], $data['other_disability'], $data['remove_documents']);

        $otherId = Disability::otherId();
        $hasOther = $otherId !== null && in_array((int) $otherId, $disabilityIds, true);
        $data['other_disability'] = $hasOther && filled($request->input('other_disability'))
            ? trim((string) $request->input('other_disability'))
            : null;

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->user()->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $documents = is_array($child->documents) ? $child->documents : [];

            $toRemove = array_values(array_filter(
                (array) $request->input('remove_documents', []),
                fn ($path) => is_string($path) && $path !== '',
            ));

            if ($toRemove !== []) {
                $validRemovals = array_values(array_intersect($documents, $toRemove));
                foreach ($validRemovals as $path) {
                    $this->secureFiles->delete($path);
                }
                $documents = array_values(array_diff($documents, $validRemovals));
            }

            $newDocumentPaths = $this->secureFiles->storeMany(
                $request->file('documents', []),
                'children/documents',
            );

            if ($newDocumentPaths !== []) {
                $documents = array_values(array_merge($documents, $newDocumentPaths));
            }

            if ($toRemove !== [] || $newDocumentPaths !== []) {
                $data['documents'] = $documents;
            }
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
        $this->registrationService->delete($child);

        return redirect()->route('children.index')->with('success', "{$name} has been permanently removed from the system.");
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
