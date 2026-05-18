<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\ChildPortalService;
use Illuminate\View\View;

/** Authenticated child portal — enrollment summary (`/my-enrollment`). Not staff {@see ChildController}. */
class ChildEnrollmentController extends Controller
{
    public function __construct(private readonly ChildPortalService $childPortalService) {}

    public function index(): View
    {
        return view('child.enrollment', [
            'enrollmentRows' => $this->childPortalService->presentEnrollmentsForPortal(auth()->id()),
        ]);
    }

    public function show(Enrollment $enrollment): View
    {
        abort_unless((int) $enrollment->child_id === (int) auth()->id(), 403);
        abort_unless($enrollment->isVisibleToChild(), 403);

        return view('child.enrollment-show', [
            'row' => $this->childPortalService->presentEnrollmentDetailForPortal($enrollment),
        ]);
    }
}
