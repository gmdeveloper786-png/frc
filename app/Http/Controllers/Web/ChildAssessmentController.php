<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\AssessmentService;
use Illuminate\View\View;

/** Authenticated child portal — read-only assessments (`/my-assessments/*`). Not staff {@see ChildController}. */
class ChildAssessmentController extends Controller
{
    public function __construct(private readonly AssessmentService $assessmentService) {}

    public function show(Assessment $assessment): View
    {
        $childId = auth()->id();
        abort_if($assessment->status === 'draft', 403);
        abort_if($assessment->status === 'cancelled' && ! $assessment->isVisibleAsCancelledToAssignees(), 403);
        abort_unless(
            $assessment->children()->where('users.id', $childId)->exists(),
            403
        );

        $assessment->load(['branch', 'services', 'therapist']);

        return view('child.assessment-show', compact('assessment'));
    }
}
