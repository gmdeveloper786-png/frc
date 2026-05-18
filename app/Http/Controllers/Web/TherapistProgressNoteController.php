<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTherapistProgressNoteRequest;
use App\Http\Requests\UpdateTherapistProgressNoteRequest;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\ProgressNote;
use App\Models\Service;
use App\Models\User;
use App\Services\TherapistPortalService;
use App\Services\TherapistProgressNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapistProgressNoteController extends Controller
{
    public function __construct(
        private readonly TherapistProgressNoteService $notes,
        private readonly TherapistPortalService $portal,
    ) {}

    public function index(Request $request): View
    {
        $tid = (int) auth()->id();

        $status = $request->filled('status') ? (string) $request->query('status') : null;
        if ($status !== null && ! in_array($status, ProgressNote::STATUSES, true)) {
            $status = null;
        }

        $childId = $request->filled('child_id') ? (int) $request->query('child_id') : null;
        if ($childId !== null && ! $this->portal->therapistHasAccessToChild($tid, $childId)) {
            $childId = null;
        }

        $filters = array_filter([
            'child_id'   => $childId,
            'service_id' => $request->filled('service_id') ? (int) $request->query('service_id') : null,
            'status'     => $status,
        ], fn($value) => $value !== null && $value !== '');

        $notes = $this->notes->paginateForTherapist($tid, $filters, 15);
        $filterChildren = $this->portal->childrenForSessionFilter($tid);
        $filterServices = $this->notes->servicesForProgressNoteFilter($tid);
        $hasActiveFilters = $request->hasAny(['child_id', 'service_id', 'status']);

        return view('therapist.progress-notes.index', compact(
            'notes',
            'filterChildren',
            'filterServices',
            'hasActiveFilters',
        ));
    }

    public function pending(Request $request): View
    {
        $tid = (int) auth()->id();
        $rows = $this->portal->paginatePendingDocumentationOccurrences(
            $tid,
            15,
            max(1, (int) $request->query('page', 1)),
        );
        $rows->appends($request->query());

        return view('therapist.progress-notes.pending', [
            'rows' => $rows,
            'pendingLookbackDays' => TherapistPortalService::PENDING_DOCUMENTATION_LOOKBACK_DAYS,
        ]);
    }

    public function create(Request $request): View
    {
        $tid = (int) auth()->id();
        $pendingRows = $this->portal->pendingDocumentationOccurrences($tid);

        $children = User::query()
            ->children()
            ->whereIn('id', $this->portal->getAssignedChildIds($tid))
            ->orderBy('full_name')
            ->get();

        $selectedChildId = $request->query('child_id');
        $prefillSessionDate = $request->query('session_date');
        $prefillEnrollmentId = $request->filled('enrollment_id') ? (int) $request->query('enrollment_id') : null;
        $prefillEnrollmentScheduleId = $request->filled('enrollment_schedule_id')
            ? (int) $request->query('enrollment_schedule_id')
            : null;

        $prefillServiceId = $request->filled('service_id') ? (int) $request->query('service_id') : null;
        if ($prefillServiceId === null && $prefillEnrollmentScheduleId !== null) {
            $schedule = EnrollmentSchedule::query()
                ->with('enrollment')
                ->find($prefillEnrollmentScheduleId);
            $prefillServiceId = $schedule?->enrollment?->service_id
                ? (int) $schedule->enrollment->service_id
                : null;
        }
        if ($prefillServiceId === null && $prefillEnrollmentId !== null) {
            $enrollment = Enrollment::query()->find($prefillEnrollmentId);
            $prefillServiceId = $enrollment?->service_id ? (int) $enrollment->service_id : null;
        }

        $prefillService = $prefillServiceId !== null
            ? Service::query()->find($prefillServiceId)
            : null;

        $therapist = auth()->user();
        $therapist?->load('therapistServices');
        $serviceOptions = $this->notes->serviceOptionsForProgressNoteForm(
            $therapist,
            $prefillServiceId,
            $pendingRows,
        );
        $occurrencePickOptions = $this->portal->mapPendingRowsToOccurrencePickOptions($pendingRows);

        return view('therapist.progress-notes.create', compact(
            'children',
            'selectedChildId',
            'prefillSessionDate',
            'prefillServiceId',
            'prefillService',
            'prefillEnrollmentId',
            'prefillEnrollmentScheduleId',
            'serviceOptions',
            'occurrencePickOptions',
        ));
    }

    public function store(StoreTherapistProgressNoteRequest $request): RedirectResponse
    {
        $note = $this->notes->create(auth()->user(), $request->validated());

        return redirect()->route('therapist.progress-notes.show', $note)->with('success', 'Progress note saved.');
    }

    public function show(ProgressNote $progressNote): View
    {
        abort_unless((int) $progressNote->therapist_id === (int) auth()->id(), 403);

        $progressNote->load(['child', 'service', 'createdBy', 'updatedBy']);

        return view('therapist.progress-notes.show', compact('progressNote'));
    }

    public function edit(ProgressNote $progressNote): View
    {
        abort_unless((int) $progressNote->therapist_id === (int) auth()->id(), 403);

        $progressNote->load(['child', 'service']);

        return view('therapist.progress-notes.edit', compact('progressNote'));
    }

    public function update(UpdateTherapistProgressNoteRequest $request, ProgressNote $progressNote): RedirectResponse
    {
        $this->notes->update(auth()->user(), $progressNote, $request->validated());

        return redirect()->route('therapist.progress-notes.show', $progressNote)->with('success', 'Progress note updated.');
    }

    public function destroy(ProgressNote $progressNote): RedirectResponse
    {
        $this->notes->delete(auth()->user(), $progressNote);

        return redirect()->route('therapist.progress-notes.index')->with('success', 'Progress note removed.');
    }
}
