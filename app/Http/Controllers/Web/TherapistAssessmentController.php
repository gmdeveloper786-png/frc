<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteAssessmentRequest;
use App\Http\Requests\StoreAssessmentNoteRequest;
use App\Http\Requests\UpdateAssessmentNoteRequest;
use App\Models\Assessment;
use App\Models\AssessmentNote;
use App\Models\Branch;
use App\Models\User;
use App\Services\AssessmentNoteVisibilityService;
use App\Services\AssessmentService;
use App\Services\TherapistPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapistAssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly TherapistPortalService $portal,
        private readonly AssessmentNoteVisibilityService $noteVisibility,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);
        $allowedStatuses = ['publish', 'completed', 'cancelled'];

        $status = $request->filled('status') ? (string) $request->query('status') : null;
        if ($status !== null && ! in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $filters = array_filter([
            'status'      => $status,
            'branch_id'   => $request->filled('branch_id') ? (int) $request->query('branch_id') : null,
            'start_date'  => $request->filled('start_date') ? (string) $request->query('start_date') : null,
            'end_date'    => $request->filled('end_date') ? (string) $request->query('end_date') : null,
            'child_id'    => $request->filled('child_id') ? (int) $request->query('child_id') : null,
        ], fn($value) => $value !== null && $value !== '');

        $assessments = $this->assessmentService->getTherapistAssessmentsPaginated((int) $user->id, $filters, 15);
        $branches = Branch::published()->forDropdown()->orderedForDropdown()->get();
        $filterChildren = $this->portal->childrenForSessionFilter((int) $user->id);

        $hasActiveFilters = $request->hasAny(['status', 'branch_id', 'start_date', 'end_date', 'child_id']);

        return view('therapist.assessments.index', compact(
            'assessments',
            'branches',
            'filterChildren',
            'status',
            'hasActiveFilters',
        ));
    }

    public function show(Assessment $assessment): View
    {
        $this->authorizeTherapist($assessment);

        $assessment->load([
            'branch',
            'services',
            'children.disabilities',
            'completedBy',
            'cancelledBy',
            'assessmentNotes.therapist',
            'assessmentNotes.child',
            'assessmentNotes.createdBy',
        ]);

        $structuredNotesVisible = $this->noteVisibility->visibleNotes($assessment, auth()->user());

        return view('therapist.assessments.show', compact('assessment', 'structuredNotesVisible'));
    }

    public function storeNote(StoreAssessmentNoteRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeTherapist($assessment);

        $this->assessmentService->addAssessmentNote($assessment, $request->validated(), auth()->user());

        return redirect()->back()->with('success', 'Assessment note saved.');
    }

    public function updateNote(UpdateAssessmentNoteRequest $request, Assessment $assessment, AssessmentNote $note): RedirectResponse
    {
        $this->authorizeTherapist($assessment);
        abort_unless((int) $note->assessment_id === (int) $assessment->id, 404);

        $this->assessmentService->updateAssessmentNote($note, $request->validated(), auth()->user());

        return redirect()->back()->with('success', 'Structured note updated.');
    }

    public function destroyNote(Assessment $assessment, AssessmentNote $note): RedirectResponse
    {
        $this->authorizeTherapist($assessment);
        abort_unless((int) $note->assessment_id === (int) $assessment->id, 404);
        abort_unless($this->noteVisibility->canManageNote(auth()->user(), $assessment, $note), 403);

        $this->assessmentService->deleteAssessmentNote($note, auth()->user());

        return redirect()->back()->with('success', 'Structured note removed.');
    }

    public function complete(CompleteAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeTherapist($assessment);

        $this->assessmentService->complete($assessment, auth()->user(), $request->validated());

        return redirect()->route('therapist.assessments.show', $assessment)->with('success', 'Assessment marked as completed.');
    }

    private function authorizeTherapist(Assessment $assessment): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->isTherapist(), 403);
        abort_unless((int) $assessment->therapist_id === $user->id, 403);
        abort_if($assessment->status === 'draft', 403);
        abort_if($assessment->status === 'cancelled' && ! $assessment->isVisibleAsCancelledToAssignees(), 403);
    }
}
