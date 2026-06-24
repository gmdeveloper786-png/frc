<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectEnrollmentRequest;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\Enrollment;
use App\Services\SettingService;
use App\Support\CitySessionPricing;
use App\Support\StaffBranchScope;
use App\Models\EnrollmentSchedule;
use App\Models\Service;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\ChildScheduleService;
use App\Services\EnrollmentService;
use App\Services\SessionFeedbackService;
use App\Services\SessionOccurrenceDetailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $service,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ChildScheduleService $scheduleService,
        private readonly SessionOccurrenceDetailService $occurrenceDetailService,
        private readonly SessionFeedbackService $sessionFeedback,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'status',
            'branch_id',
            'service_id',
            'child_id',
            'payment_status',
            'search',
            'date_from',
            'date_to',
        ]);
        if ($lockedBranch = StaffBranchScope::lockedBranchId($request->user())) {
            $filters['branch_id'] = $lockedBranch;
        }

        $enrollments = $this->service->getAll($filters);
        $branches    = StaffBranchScope::publishedBranchesFor($request->user());
        $services    = Service::published()->orderBy('name')->get();

        return view('enrollments.index', compact('enrollments', 'branches', 'services'));
    }

    public function pendingHighDiscount(Request $request): View
    {
        $branchId    = StaffBranchScope::lockedBranchId($request->user());
        $enrollments = $this->service->getPendingHighDiscount(15, $branchId);

        return view('enrollments.high-discount', compact('enrollments'));
    }

    public function create(Request $request): View
    {
        $branches = StaffBranchScope::publishedBranchesFor($request->user());
        $services = Service::published()->orderBy('name')->get();
        $childIds = array_map('intval', (array) old('child_ids', []));
        if ($childIds === [] && request()->filled('child_id')) {
            $childIds = [(int) request()->query('child_id')];
        }
        $initialChildren = $this->userRepository->getApprovedChildrenByIds(
            array_filter($childIds),
            $request->user(),
        );

        $enrollmentPricing = $this->enrollmentPricingContext($branches);

        return view('enrollments.create', compact('branches', 'services', 'initialChildren', 'enrollmentPricing'));
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $childIds = $validated['child_ids'];

        $created = DB::transaction(function () use ($request, $validated, $childIds): array {
            $created = $this->service->createEnrollments(
                $this->primaryEnrollmentPayload($validated),
                $request->user()->id,
                $request->file('discount_file'),
            );

            foreach ($validated['extra_enrollments'] ?? [] as $extraKey => $extra) {
                $created = array_merge(
                    $created,
                    $this->service->createEnrollments(
                        array_merge($extra, ['child_ids' => $childIds]),
                        $request->user()->id,
                        $request->file("extra_enrollments.{$extraKey}.discount_file"),
                    ),
                );
            }

            return $created;
        });

        $blockCount = 1 + count($validated['extra_enrollments'] ?? []);

        if (count($created) === 1) {
            return redirect()->route('enrollments.show', $created[0])
                ->with('success', 'Enrollment created successfully.');
        }

        if ($blockCount > 1) {
            return redirect()->route('enrollments.index')
                ->with('success', count($created) . ' enrollments created successfully.');
        }

        return redirect()->route('enrollments.index')
            ->with('success', 'Group enrollment created for ' . count($created) . ' children.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function primaryEnrollmentPayload(array $validated): array
    {
        unset($validated['extra_enrollments']);

        return $validated;
    }

    public function edit(Request $request, int $id): View
    {
        $enrollment = $this->service->findById($id);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $this->authorize('update', $enrollment);
        $branches   = StaffBranchScope::publishedBranchesFor($request->user());
        $services   = Service::published()->orderBy('name')->get();

        $enrollmentPricing = $this->enrollmentPricingContext($branches);

        return view('enrollments.edit', compact('enrollment', 'branches', 'services', 'enrollmentPricing'));
    }

    public function update(UpdateEnrollmentRequest $request, int $id): RedirectResponse
    {
        $enrollment = $this->service->findById($id);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $this->authorize('update', $enrollment);
        $this->service->update(
            $enrollment,
            $request->validated(),
            $request->user()->id,
            $request->file('discount_file'),
        );

        return redirect()->route('enrollments.show', $enrollment->id)
            ->with('success', 'Enrollment updated successfully.');
    }

    public function show(Request $request, int $id): View
    {
        $enrollment = $this->service->findById($id);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $this->authorize('view', $enrollment);

        return view('enrollments.show', compact('enrollment'));
    }

    /**
     * Expanded dated sessions for one enrolment (staff / assigned therapist / owning child).
     */
    public function fullSchedule(Request $request, Enrollment $enrollment): View
    {
        $this->authorize('viewFullSchedule', $enrollment);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);

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
        $sessionFeedback = $this->sessionFeedback->summaryForSchedule($schedule, $sessionDate);

        return view('enrollments.schedule-show', compact('enrollment', 'detail', 'occurrenceDetail', 'sessionFeedback'));
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $enrollment = $this->service->findById($id);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);

        $this->authorize('approve', $enrollment);

        $this->service->approve($enrollment, $request->user());

        return redirect()->back()->with('success', 'Enrollment approved successfully.');
    }

    public function reject(RejectEnrollmentRequest $request, int $id): RedirectResponse
    {
        $enrollment = $this->service->findById($id);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $this->authorize('reject', $enrollment);
        $this->service->reject($enrollment, $request->user(), $request->validated('rejection_reason'));

        return redirect()->back()->with('success', 'Enrollment rejected.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $enrollment = $this->service->findById($id);
        StaffBranchScope::enforceEnrollmentBranch($request->user(), $enrollment);
        $this->authorize('delete', $enrollment);
        $this->service->delete($enrollment);

        return redirect()->route('enrollments.index')->with('success', 'Enrollment permanently deleted.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Branch>|\Illuminate\Database\Eloquent\Collection  $branches
     * @return array{branch_city_map: array<int, string>, city_session_prices: array<string, float>}
     */
    private function enrollmentPricingContext($branches): array
    {
        $pricing = app(CitySessionPricing::class);

        return [
            'branch_city_map'     => $pricing->branchCityMap($branches),
            'city_session_prices' => app(SettingService::class)->citySessionPrices(),
        ];
    }
}
