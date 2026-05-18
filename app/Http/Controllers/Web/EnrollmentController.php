<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\Branch;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Service;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\ChildScheduleService;
use App\Services\EnrollmentService;
use App\Services\SessionOccurrenceDetailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $service,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ChildScheduleService $scheduleService,
        private readonly SessionOccurrenceDetailService $occurrenceDetailService,
    ) {}

    public function index(Request $request): View
    {
        $enrollments = $this->service->getAll($request->only([
            'status',
            'branch_id',
            'service_id',
            'child_id',
            'payment_status',
            'search',
            'date_from',
            'date_to',
        ]));
        $branches = Branch::published()->get();
        $services = Service::published()->orderBy('name')->get();

        return view('enrollments.index', compact('enrollments', 'branches', 'services'));
    }

    public function pendingHighDiscount(): View
    {
        $enrollments = $this->service->getPendingHighDiscount(15);

        return view('enrollments.high-discount', compact('enrollments'));
    }

    public function create(): View
    {
        $branches = Branch::published()->orderBy('name')->get();
        $services = Service::published()->orderBy('name')->get();
        $initialChildren = $this->userRepository->getApprovedChildrenByIds(array_filter([
            (int) old('child_id', 0),
            (int) request()->query('child_id', 0),
        ]));

        return view('enrollments.create', compact('branches', 'services', 'initialChildren'));
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $enrollment = $this->service->create(
            $request->validated(),
            $request->user()->id,
            $request->file('discount_file'),
        );

        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Enrollment created successfully.');
    }

    public function edit(int $id): View
    {
        $enrollment = $this->service->findById($id);
        $branches   = Branch::published()->orderBy('name')->get();
        $services   = Service::published()->orderBy('name')->get();

        return view('enrollments.edit', compact('enrollment', 'branches', 'services'));
    }

    public function update(UpdateEnrollmentRequest $request, int $id): RedirectResponse
    {
        $enrollment = $this->service->findById($id);
        $this->service->update(
            $enrollment,
            $request->validated(),
            $request->user()->id,
            $request->file('discount_file'),
        );

        return redirect()->route('enrollments.show', $enrollment->id)
            ->with('success', 'Enrollment updated successfully.');
    }

    public function show(int $id): View
    {
        $enrollment = $this->service->findById($id);

        return view('enrollments.show', compact('enrollment'));
    }

    /**
     * Expanded dated sessions for one enrolment (staff / assigned therapist / owning child).
     */
    public function fullSchedule(Request $request, Enrollment $enrollment): View
    {
        $this->authorize('viewFullSchedule', $enrollment);

        $enrollment->loadMissing(['child', 'branch', 'service', 'therapist']);

        $schedulePage = $this->scheduleService->getEnrollmentFullSchedulePageData($enrollment, $request);

        return view('enrollments.schedule', [
            'enrollment'    => $enrollment,
            'paginator'     => $schedulePage['paginator'],
            'filterOptions' => $schedulePage['filterOptions'],
            'stats'         => $schedulePage['stats'],
        ]);
    }

    /**
     * Single occurrence detail for {@see fullSchedule} (matches child portal fields).
     */
    public function fullScheduleOccurrence(Request $request, Enrollment $enrollment, EnrollmentSchedule $schedule): View
    {
        $this->authorize('viewFullSchedule', $enrollment);

        abort_unless((int) $schedule->enrollment_id === (int) $enrollment->id, 404);

        $sessionDate = (string) $request->query('session_date', '');
        abort_if($sessionDate === '', 404);

        $detail = $this->scheduleService->getOccurrenceDetailForEnrollment($enrollment, $schedule->id, $sessionDate);
        abort_if($detail === null, 404);

        $enrollment->loadMissing('child');

        $schedule->loadMissing(['startedBy:id,full_name', 'completedBy:id,full_name', 'cancelledBy:id,full_name']);

        $occurrenceDetail = $this->occurrenceDetailService->buildTherapistOccurrenceDetail($schedule, $sessionDate);

        return view('enrollments.schedule-show', compact('enrollment', 'detail', 'occurrenceDetail'));
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $enrollment = $this->service->findById($id);

        if ($enrollment->status === 'pending_super_admin_approval' && ! $request->user()->hasPermission('approve_high_discount')) {
            return back()->withErrors(['error' => 'You do not have permission to approve high discount enrollments.']);
        }

        $this->service->approve($enrollment, $request->user());

        return redirect()->back()->with('success', 'Enrollment approved successfully.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        $enrollment = $this->service->findById($id);
        $this->service->reject($enrollment, $request->user(), $request->rejection_reason);

        return redirect()->back()->with('success', 'Enrollment rejected.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $enrollment = $this->service->findById($id);
        $this->service->delete($enrollment);

        return redirect()->route('enrollments.index')->with('success', 'Enrollment deleted.');
    }
}
