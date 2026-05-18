<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentSchedule;
use App\Services\ChildScheduleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Authenticated child portal — therapy/session calendar (`/my-schedule`, `child.schedule.*`).
 *
 * Keep portal endpoints separate from {@see ChildController} (admin/staff child management).
 */
class ChildScheduleController extends Controller
{
    public function __construct(private readonly ChildScheduleService $scheduleService) {}

    public function index(Request $request): View
    {
        $childId = (int) auth()->id();

        return view('child.schedule.index', [
            'paginator'     => $this->scheduleService->getPaginatedSchedules($childId, $request),
            'filterOptions' => $this->scheduleService->getFilterOptions($childId),
            'nextSession'   => $this->scheduleService->getNextUpcomingOccurrence($childId),
        ]);
    }

    public function show(Request $request, EnrollmentSchedule $schedule): View
    {
        $childId = (int) auth()->id();

        $schedule->loadMissing('enrollment');

        abort_unless($schedule->enrollment && (int) $schedule->enrollment->child_id === $childId, 403);
        abort_unless($schedule->enrollment->isVisibleToChild(), 403);

        $sessionDate = (string) $request->query('session_date', '');
        abort_if($sessionDate === '', 404);

        $detail = $this->scheduleService->getOccurrenceDetail($childId, $schedule->id, $sessionDate);
        abort_if($detail === null, 404);

        return view('child.schedule.show', compact('detail'));
    }
}
