<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTherapistProgressNoteRequest;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Models\EnrollmentSchedule;
use App\Services\AssessmentService;
use App\Services\TherapistPortalService;
use App\Services\TherapistProgressNoteService;
use App\Services\TherapistSessionService;
use App\Services\UserNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TherapistPortalApiController extends Controller
{
    public function __construct(
        private readonly TherapistPortalService $portal,
        private readonly AssessmentService $assessmentService,
        private readonly TherapistSessionService $sessionService,
        private readonly TherapistProgressNoteService $progressNoteService,
        private readonly UserNotificationService $userNotifications,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $tid = (int) $user->id;

        return response()->json([
            'stats' => $this->portal->dashboardStats($tid),
            'notifications_preview' => $this->userNotifications->getLatestNotifications((int) $user->id, 8)->map(fn ($n) => [
                'id'      => $n->id,
                'read_at' => $n->read_at?->toDateTimeString(),
                'is_read' => $n->is_read,
                'title'   => $n->title,
                'message' => $n->message,
                'data'    => ['title' => $n->title, 'message' => $n->message],
            ]),
        ]);
    }

    public function myAssessments(Request $request): AnonymousResourceCollection
    {
        $tid = (int) $request->user()->id;
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        return AssessmentResource::collection(
            $this->assessmentService->getTherapistAssessmentsPaginated(
                $tid,
                $this->assessmentFiltersFromRequest($request),
                $perPage,
            ),
        );
    }

    public function assessmentsToday(Request $request): AnonymousResourceCollection
    {
        $list = $this->assessmentService->getTherapistTodayAssessments((int) $request->user()->id);

        return AssessmentResource::collection($list->loadMissing(['branch', 'services', 'children']));
    }

    public function assessmentsUpcoming(Request $request): AnonymousResourceCollection
    {
        $filterKey = match ((string) $request->query('filter', 'all')) {
            'today' => 'today',
            'week' => 'week',
            'month' => 'month',
            default => 'all',
        };
        $list = $this->portal->upcomingAssessmentBucketsFiltered((int) $request->user()->id, $filterKey);

        return AssessmentResource::collection($list->loadMissing(['branch', 'services', 'children']));
    }

    public function assessmentsCompleted(Request $request): AnonymousResourceCollection
    {
        $tid = (int) $request->user()->id;
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $filters = array_merge($this->assessmentFiltersFromRequest($request), ['status' => 'completed']);

        return AssessmentResource::collection(
            $this->assessmentService->getTherapistAssessmentsPaginated($tid, $filters, $perPage),
        );
    }

    public function myChildren(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));
        $page = max(1, (int) $request->query('page', 1));

        return response()->json(
            $this->portal->assignedChildrenApiResponse((int) $request->user()->id, $perPage, $page),
        );
    }

    public function mySchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'status'     => ['nullable', 'string'],
            'child_id'   => ['nullable', 'integer', 'min:1'],
        ]);

        $startDate = $request->filled('start_date') ? (string) $request->query('start_date') : null;
        $endDate = $request->filled('end_date') ? (string) $request->query('end_date') : null;
        $status = $validated['status'] ?? $request->query('status', 'all');
        $filterChildId = null;
        if ($request->filled('child_id')) {
            $cid = (int) $request->query('child_id');
            $filterChildId = $cid > 0 ? $cid : null;
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $page = max(1, (int) $request->query('page', 1));
        $statusFilter = $status !== 'all' ? (string) $status : null;

        return response()->json(
            $this->portal->therapistScheduleApiResponse(
                (int) $request->user()->id,
                $startDate,
                $endDate,
                $statusFilter,
                $filterChildId,
                $perPage,
                $page,
                $request->url(),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessmentFiltersFromRequest(Request $request): array
    {
        return array_filter([
            'status'     => $request->filled('status') ? (string) $request->query('status') : null,
            'start_date' => $request->filled('start_date') ? (string) $request->query('start_date') : null,
            'end_date'   => $request->filled('end_date') ? (string) $request->query('end_date') : null,
            'child_id'   => $request->filled('child_id') ? (int) $request->query('child_id') : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function mySessions(Request $request): JsonResponse
    {
        return $this->mySchedule($request);
    }

    public function storeAssessmentNote(Request $request, Assessment $assessment): JsonResponse
    {
        abort_unless((int) $assessment->therapist_id === (int) $request->user()->id, 403);

        $note = $this->assessmentService->addAssessmentNote($assessment, $request->validate([
            'child_id'               => ['nullable', 'exists:users,id'],
            'observation'            => ['nullable', 'string'],
            'recommended_services'   => ['nullable', 'array'],
            'child_response'         => ['nullable', 'string'],
            'initial_recommendation' => ['nullable', 'string'],
            'additional_notes'       => ['nullable', 'string'],
            'status'                 => ['nullable', 'in:draft,completed'],
        ]), $request->user());

        return response()->json(['data' => ['id' => $note->id]], 201);
    }

    public function sessionStart(Request $request, EnrollmentSchedule $schedule): JsonResponse
    {
        $data = $request->validate([
            'session_date' => ['required', 'date'],
        ]);
        $iso = Carbon::parse($data['session_date'])->toDateString();
        $this->sessionService->startSession($request->user(), $schedule, $iso);

        return response()->json(['message' => 'Session started successfully.']);
    }

    public function sessionComplete(Request $request, EnrollmentSchedule $schedule): JsonResponse
    {
        $data = $request->validate([
            'session_date'    => ['required', 'date'],
            'completion_note' => ['nullable', 'string', 'max:5000'],
            'session_notes'   => ['nullable', 'string', 'max:5000'],
        ]);
        $trimmed = trim((string) ($data['completion_note'] ?? ''));
        if ($trimmed === '') {
            $trimmed = trim((string) ($data['session_notes'] ?? ''));
        }
        $iso = Carbon::parse($data['session_date'])->toDateString();
        $this->sessionService->completeSession($request->user(), $schedule, $iso, $trimmed !== '' ? $trimmed : null);

        return response()->json(['message' => 'Completed']);
    }

    public function sessionCancel(Request $request, EnrollmentSchedule $schedule): JsonResponse
    {
        $merged = trim((string) $request->input('cancellation_reason', ''));
        if ($merged === '') {
            $merged = trim((string) $request->input('session_notes', ''));
        }
        $request->merge(['cancellation_reason' => $merged]);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:5000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (trim((string) $value) === '') {
                    $fail('Cancellation reason is required.');
                }
            }],
        ]);
        $this->sessionService->cancelSession($request->user(), $schedule, trim($data['cancellation_reason']));

        return response()->json(['message' => 'Cancelled']);
    }

    public function sessionNoShow(Request $request, EnrollmentSchedule $schedule): JsonResponse
    {
        $data = $request->validate(['session_notes' => ['nullable', 'string']]);
        $this->sessionService->markNoShow($request->user(), $schedule, $data['session_notes'] ?? null);

        return response()->json(['message' => 'No-show recorded']);
    }

    public function storeProgressNote(StoreTherapistProgressNoteRequest $request): JsonResponse
    {
        $note = $this->progressNoteService->create($request->user(), $request->validated());

        return response()->json(['data' => ['id' => $note->id]], 201);
    }

    public function indexProgressNotes(Request $request): JsonResponse
    {
        $tid = (int) $request->user()->id;
        $status = $request->filled('status') ? (string) $request->query('status') : null;
        if ($status !== null && ! in_array($status, \App\Models\ProgressNote::STATUSES, true)) {
            $status = null;
        }

        $filters = array_filter([
            'child_id'   => $request->filled('child_id') ? (int) $request->query('child_id') : null,
            'service_id' => $request->filled('service_id') ? (int) $request->query('service_id') : null,
            'status'     => $status,
        ], fn ($value) => $value !== null && $value !== '');

        return response()->json([
            'data' => $this->progressNoteService->paginateForTherapist($tid, $filters),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['therapistProfile.branch', 'therapistServices']);

        return response()->json([
            'full_name'      => $user->full_name,
            'email'          => $user->email,
            'phone_number'   => $user->phone_number,
            'whatsapp_number'=> $user->whatsapp_number,
            'branch'         => $user->therapistProfile?->branch?->name,
            'profile'        => $user->therapistProfile,
            'services'       => $user->therapistServices->pluck('name'),
        ]);
    }
}
