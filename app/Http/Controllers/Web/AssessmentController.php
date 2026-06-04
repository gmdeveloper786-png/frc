<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAssessmentRequest;
use App\Http\Requests\CompleteAssessmentRequest;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Support\StaffBranchScope;
use App\Services\AssessmentNoteVisibilityService;
use App\Services\AssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentService $service,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AssessmentNoteVisibilityService $assessmentNoteVisibility,
    ) {}

    public function index(\Illuminate\Http\Request $request): View
    {
        $filters = $request->only(['status', 'branch_id', 'date_from', 'date_to']);
        if ($lockedBranch = StaffBranchScope::lockedBranchId($request->user())) {
            $filters['branch_id'] = $lockedBranch;
        }

        $assessments = $this->service->getAll($filters);
        $branches    = StaffBranchScope::publishedBranchesFor($request->user());

        return view('assessments.index', compact('assessments', 'branches'));
    }

    public function create(Request $request): View
    {
        $branches = StaffBranchScope::publishedBranchesFor($request->user());
        $initialChildren = $this->userRepository->getApprovedChildrenByIds(
            array_map('intval', (array) old('child_ids', [])),
            $request->user(),
        );

        return view('assessments.create', compact('branches', 'initialChildren'));
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user()->id);

        return redirect()->route('assessments.index')->with('success', 'Assessment created successfully.');
    }

    public function show(Request $request, Assessment $assessment): View
    {
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $assessment->load([
            'branch',
            'services',
            'children.disabilities',
            'therapist',
            'assessmentNotes.therapist',
            'assessmentNotes.child',
            'assessmentNotes.createdBy',
            'completedBy',
            'cancelledBy',
        ]);

        $structuredNotesVisible = $this->assessmentNoteVisibility->visibleNotes($assessment, $request->user());

        return view('assessments.show', compact('assessment', 'structuredNotesVisible'));
    }

    public function edit(Request $request, Assessment $assessment): View
    {
        abort_if(in_array($assessment->status, ['completed', 'cancelled'], true), 403);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $branches = StaffBranchScope::publishedBranchesFor($request->user());
        $assessment->load(['branch', 'services', 'children', 'therapist']);
        $initialChildren = $this->userRepository->getApprovedChildrenByIds(
            array_map('intval', (array) old('child_ids', $assessment->children->pluck('id')->all())),
            $request->user(),
        );

        return view('assessments.edit', compact('assessment', 'branches', 'initialChildren'));
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        abort_if(in_array($assessment->status, ['completed', 'cancelled'], true), 403);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $this->service->update($assessment, $request->validated(), $request->user()->id);

        return redirect()->route('assessments.index')->with('success', 'Assessment updated successfully.');
    }

    public function destroy(Request $request, Assessment $assessment): RedirectResponse
    {
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);
        $this->service->delete($assessment);

        return redirect()->route('assessments.index')->with('success', 'Assessment deleted.');
    }

    public function complete(CompleteAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('manage_assessments'), 403);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $this->service->complete($assessment, $request->user(), $request->validated());

        return redirect()->back()->with('success', 'Assessment marked as completed.');
    }

    public function cancel(CancelAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $this->service->cancel($assessment, $request->user(), $request->validated()['cancellation_reason']);

        return redirect()->route('assessments.show', $assessment)->with('success', 'Assessment cancelled.');
    }

    public function storeNote(Request $request, Assessment $assessment): RedirectResponse
    {
        abort(403, 'Only the assigned therapist can add structured assessment notes.');
    }
}
