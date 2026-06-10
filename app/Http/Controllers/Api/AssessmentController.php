<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAssessmentRequest;
use App\Http\Requests\CompleteAssessmentRequest;
use App\Http\Requests\StoreAssessmentNoteRequest;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Services\AssessmentService;
use App\Support\StaffBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(private readonly AssessmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('manage_assessments'), 403);

        $filters = $request->only(['status', 'branch_id', 'date_from', 'date_to']);
        if ($lockedBranch = StaffBranchScope::lockedBranchId($request->user())) {
            $filters['branch_id'] = $lockedBranch;
        }

        $assessments = $this->service->getAll($filters);

        return response()->json(['data' => AssessmentResource::collection($assessments)]);
    }

    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $assessment = $this->service->create($request->validated(), $request->user()->id);

        return response()->json(['message' => 'Assessment created.', 'data' => new AssessmentResource($assessment)], 201);
    }

    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('view', $assessment);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $loads = [
            'branch',
            'services',
            'children',
            'therapist',
            'completedBy',
            'cancelledBy',
        ];

        if ($request->user() && ! $request->user()->isChild()) {
            $loads[] = 'assessmentNotes';
            $loads[] = 'assessmentNotes.therapist';
            $loads[] = 'assessmentNotes.child';
            $loads[] = 'assessmentNotes.createdBy';
        }

        $assessment->load($loads);

        return response()->json(['data' => new AssessmentResource($assessment)]);
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        abort_if(in_array($assessment->status, ['completed', 'cancelled'], true), 403);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $updated = $this->service->update($assessment, $request->validated(), $request->user()->id);

        return response()->json(['message' => 'Assessment updated.', 'data' => new AssessmentResource($updated)]);
    }

    public function destroy(Request $request, Assessment $assessment): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('manage_assessments'), 403);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);

        $this->service->delete($assessment);

        return response()->json(['message' => 'Assessment deleted.']);
    }

    public function complete(CompleteAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('complete', $assessment);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);
        $updated = $this->service->complete($assessment, $request->user(), $request->validated());

        return response()->json(['message' => 'Assessment marked as completed.', 'data' => new AssessmentResource($updated)]);
    }

    public function cancel(CancelAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);
        $updated = $this->service->cancel($assessment, $request->user(), $request->validated()['cancellation_reason']);

        return response()->json(['message' => 'Assessment cancelled.', 'data' => new AssessmentResource($updated)]);
    }

    public function storeNote(StoreAssessmentNoteRequest $request, Assessment $assessment): JsonResponse
    {
        $this->authorize('view', $assessment);
        StaffBranchScope::enforceAssessmentBranch($request->user(), $assessment);
        $this->service->addAssessmentNote($assessment, $request->validated(), $request->user());

        $assessment->load(['assessmentNotes']);

        return response()->json(['message' => 'Note saved.', 'data' => new AssessmentResource($assessment)]);
    }

    public function myAssessments(Request $request): JsonResponse
    {
        $assessments = $this->service->getForChild($request->user()->id);

        return response()->json(['data' => AssessmentResource::collection($assessments)]);
    }

    public function therapistMyAssessments(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isTherapist(), 403);

        $buckets = $this->service->getTherapistAssessmentBuckets($request->user()->id);

        return response()->json([
            'data' => [
                'today'     => AssessmentResource::collection($buckets['today']),
                'upcoming'  => AssessmentResource::collection($buckets['upcoming']),
                'completed' => AssessmentResource::collection($buckets['completed']),
                'cancelled' => AssessmentResource::collection($buckets['cancelled']),
            ],
        ]);
    }

}
