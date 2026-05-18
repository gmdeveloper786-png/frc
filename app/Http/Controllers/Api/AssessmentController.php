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
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(private readonly AssessmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('manage_assessments'), 403);

        $assessments = $this->service->getAll($request->only(['status', 'branch_id', 'date_from', 'date_to']));

        return response()->json(['data' => AssessmentResource::collection($assessments)]);
    }

    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $assessment = $this->service->create($request->validated(), $request->user()->id);

        return response()->json(['message' => 'Assessment created.', 'data' => new AssessmentResource($assessment)], 201);
    }

    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeAssessmentView($request->user(), $assessment);

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

        $updated = $this->service->update($assessment, $request->validated(), $request->user()->id);

        return response()->json(['message' => 'Assessment updated.', 'data' => new AssessmentResource($updated)]);
    }

    public function destroy(Request $request, Assessment $assessment): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('manage_assessments'), 403);

        $this->service->delete($assessment);

        return response()->json(['message' => 'Assessment deleted.']);
    }

    public function complete(CompleteAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        $updated = $this->service->complete($assessment, $request->user(), $request->validated());

        return response()->json(['message' => 'Assessment marked as completed.', 'data' => new AssessmentResource($updated)]);
    }

    public function cancel(CancelAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        $updated = $this->service->cancel($assessment, $request->user(), $request->validated()['cancellation_reason']);

        return response()->json(['message' => 'Assessment cancelled.', 'data' => new AssessmentResource($updated)]);
    }

    public function storeNote(StoreAssessmentNoteRequest $request, Assessment $assessment): JsonResponse
    {
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

    private function authorizeAssessmentView(?User $user, Assessment $assessment): void
    {
        abort_unless($user instanceof User, 403);

        if ($user->hasPermission('manage_assessments')) {
            return;
        }

        if ($user->isTherapist() && (int) $assessment->therapist_id === (int) $user->id) {
            abort_if($assessment->status === 'draft', 403);
            abort_if($assessment->status === 'cancelled' && ! $assessment->isVisibleAsCancelledToAssignees(), 403);

            return;
        }

        if ($user->isChild() && $assessment->children()->where('users.id', $user->id)->exists()) {
            abort_if($assessment->status === 'draft', 403);
            abort_if($assessment->status === 'cancelled' && ! $assessment->isVisibleAsCancelledToAssignees(), 403);

            return;
        }

        abort(403);
    }
}
