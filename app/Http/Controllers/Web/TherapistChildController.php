<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\User;
use App\Services\TherapistPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapistChildController extends Controller
{
    public function __construct(private readonly TherapistPortalService $portal) {}

    public function index(Request $request): View
    {
        $perPage = 15;
        $currentPage = max(1, (int) $request->query('page', 1));
        $rows = $this->portal->paginateAssignedChildren(
            (int) auth()->id(),
            $perPage,
            $currentPage,
            route('therapist.children.index'),
        );
        $rows->appends($request->query());

        return view('therapist.children.index', compact('rows'));
    }

    public function show(User $child): View
    {
        abort_unless($child->isChild(), 404);

        $tid = auth()->id();
        abort_unless($this->portal->therapistHasAccessToChild((int) $tid, (int) $child->id), 403);

        $child->load(['disabilities', 'enrollments' => fn($q) => $q->where('therapist_id', $tid)->with(['branch', 'service'])]);

        $assessments = Assessment::query()
            ->where('therapist_id', $tid)
            ->whereHas('children', fn($q) => $q->where('users.id', $child->id))
            ->with(['branch', 'services'])
            ->orderByDesc('date')
            ->limit(15)
            ->get();

        $sessions = $this->portal->sessionsBaseQuery((int) $tid)
            ->whereHas('enrollment', fn($q) => $q->where('child_id', $child->id))
            ->with(['enrollment.service', 'branch'])
            ->orderByDesc('session_date')
            ->limit(15)
            ->get();

        return view('therapist.children.show', compact('child', 'assessments', 'sessions'));
    }
}
