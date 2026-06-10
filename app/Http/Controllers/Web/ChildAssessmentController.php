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
        $this->authorize('view', $assessment);

        $assessment->load(['branch', 'services', 'therapist']);

        return view('child.assessment-show', compact('assessment'));
    }
}
