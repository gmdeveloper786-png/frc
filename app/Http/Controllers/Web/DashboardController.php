<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\TherapistPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly TherapistPortalService $therapistPortalService,
    ) {}

    public function superAdmin(Request $request): View
    {
        $chartYear = $this->chartYear($request);
        $stats = $this->dashboardService->getSuperAdminStats($chartYear);

        return view('dashboard.super-admin', compact('stats'));
    }

    public function admin(Request $request): View
    {
        $chartYear = $this->chartYear($request);
        $stats = $this->dashboardService->getAdminStats($chartYear, $request->user());

        return view('dashboard.admin', compact('stats'));
    }

    public function therapist(Request $request): View
    {
        $portal = $this->therapistPortalService->buildDashboardPage($request->user());

        return view('dashboard.therapist', compact('portal'));
    }

    public function finance(Request $request): View
    {
        $chartYear = $this->chartYear($request);
        $stats = $this->dashboardService->getFinanceStats($chartYear);

        return view('dashboard.finance', compact('stats'));
    }

    public function child(Request $request): View
    {
        $stats = $this->dashboardService->getChildStats($request->user());

        return view('dashboard.child', compact('stats'));
    }

    private function chartYear(Request $request): ?int
    {
        if (! $request->filled('chart_year')) {
            return null;
        }

        $year = (int) $request->validate([
            'chart_year' => ['integer', 'min:2000', 'max:2100'],
        ])['chart_year'];

        return $year;
    }
}
