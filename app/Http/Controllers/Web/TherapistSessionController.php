<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentSchedule;
use App\Services\SessionOccurrenceDetailService;
use App\Services\TherapistPortalService;
use App\Services\TherapistSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapistSessionController extends Controller
{
    public function __construct(
        private readonly TherapistPortalService $portal,
        private readonly TherapistSessionService $sessionService,
        private readonly SessionOccurrenceDetailService $occurrenceDetailService,
    ) {}

    public function index(Request $request): View
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
        if (! in_array($status, ['all', 'scheduled', 'in_progress', 'completed', 'cancelled', 'no_show'], true)) {
            $status = 'all';
        }

        $filterChildId = null;
        if ($request->filled('child_id')) {
            $cid = (int) $request->query('child_id');
            $filterChildId = $cid > 0 ? $cid : null;
        }

        $perPage = 15;
        $currentPage = max(1, (int) $request->query('page', 1));
        $sessions = $this->portal->paginateTherapistSessionsFiltered(
            (int) auth()->id(),
            $startDate,
            $endDate,
            $status !== null ? (string) $status : null,
            $filterChildId,
            $perPage,
            $currentPage,
            route('therapist.sessions.index'),
        );
        $sessions->appends($request->query());

        $filterChildren = $this->portal->childrenForSessionFilter(auth()->id());

        $hasDateFilter = $startDate !== null || $endDate !== null;
        $hasStatusFilter = $status !== 'all';
        $hasChildFilter = $filterChildId !== null;
        $defaultRangeHint = ! $hasDateFilter
            ? sprintf(
                'Showing %d weeks before today through %d weeks ahead. Use Start/End date to see more.',
                TherapistPortalService::SESSIONS_DEFAULT_PAST_WEEKS,
                TherapistPortalService::SESSIONS_DEFAULT_FUTURE_WEEKS,
            )
            : null;

        return view('therapist.sessions.index', compact(
            'sessions',
            'startDate',
            'endDate',
            'status',
            'filterChildren',
            'filterChildId',
            'hasDateFilter',
            'hasStatusFilter',
            'hasChildFilter',
            'defaultRangeHint',
        ));
    }

    public function showOccurrence(Request $request, EnrollmentSchedule $schedule): View
    {
        $therapist = auth()->user();
        $this->occurrenceDetailService->authorizeAssignedTherapist($therapist, $schedule);

        $sessionDate = (string) $request->query('session_date', '');
        abort_if($sessionDate === '', 404);

        abort_unless(
            $this->portal->therapistOwnsScheduleOccurrence((int) $therapist->id, $schedule, $sessionDate),
            404,
        );

        $occurrenceDetail = $this->occurrenceDetailService->buildTherapistOccurrenceDetail($schedule, $sessionDate);
        abort_if($occurrenceDetail === [], 404);

        $detailStatus = (string) ($occurrenceDetail['status'] ?? $schedule->status);
        $statusBadge = match ($detailStatus) {
            'scheduled' => 'badge-session-scheduled',
            'in_progress' => 'badge-session-in-progress',
            'completed' => 'badge-session-completed',
            'cancelled' => 'badge-session-cancelled',
            'no_show' => 'badge-session-no-show',
            default => 'badge-draft',
        };

        return view('therapist.sessions.show', compact('schedule', 'occurrenceDetail', 'statusBadge'));
    }

    public function occurrenceDetail(Request $request, EnrollmentSchedule $schedule): JsonResponse
    {
        $this->occurrenceDetailService->authorizeAssignedTherapist(auth()->user(), $schedule);

        $sessionDate = (string) $request->query('session_date', '');
        if ($sessionDate === '') {
            return response()->json(['message' => 'session_date is required.'], 422);
        }

        $payload = $this->occurrenceDetailService->buildTherapistOccurrenceDetail($schedule, $sessionDate);
        if ($payload === []) {
            return response()->json(['message' => 'Invalid session date.'], 422);
        }

        return response()->json(['data' => $payload]);
    }

    public function start(Request $request, EnrollmentSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'session_date' => ['required', 'date'],
        ]);
        $iso = Carbon::parse($data['session_date'])->toDateString();

        $this->sessionService->startSession(auth()->user(), $schedule, $iso);

        return redirect()->back()->with('success', 'Session started successfully.');
    }

    public function complete(Request $request, EnrollmentSchedule $schedule): RedirectResponse
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
        $this->sessionService->completeSession(auth()->user(), $schedule, $iso, $trimmed !== '' ? $trimmed : null);

        return redirect()->back()->with('success', 'Session marked completed.');
    }

    public function cancel(Request $request, EnrollmentSchedule $schedule): RedirectResponse
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

        $this->sessionService->cancelSession(auth()->user(), $schedule, trim($data['cancellation_reason']));

        return redirect()->back()->with('success', 'Session cancelled.');
    }

    public function noShow(Request $request, EnrollmentSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'session_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->sessionService->markNoShow(auth()->user(), $schedule, $data['session_notes'] ?? null);

        return redirect()->back()->with('success', 'Marked as no-show.');
    }

    public function updateNotes(Request $request, EnrollmentSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'session_notes' => ['required', 'string', 'max:5000'],
        ]);
        $this->sessionService->updateSessionNotes(auth()->user(), $schedule, $data['session_notes']);

        return redirect()->back()->with('success', 'Session notes saved.');
    }
}
