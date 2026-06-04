<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\EnrollmentScheduleOccurrence;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /** Cache TTL for chart/analytics payloads only (headline stat cards are always live). */
    private const CHART_CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly ChildPortalService $childPortalService,
    ) {}

    public function getSuperAdminStats(?int $chartYear = null): array
    {
        [$chartYear, $chartYears] = $this->resolveChartYear($chartYear);

        return array_merge(
            $this->superAdminHeadlines(),
            $this->completedSessionsTotal(),
            $this->feeTotals(),
            $this->cachedChartPayload($chartYear),
            [
                'chart_year'         => $chartYear,
                'chart_years'        => $chartYears,
                'recent_children'    => User::children()->with('disabilities')->latest()->limit(3)->get(),
                'recent_enrollments' => Enrollment::with(['child', 'service'])->latest()->limit(3)->get(),
                'recent_payments'    => Payment::with(['child', 'enrollment'])->latest()->limit(3)->get(),
            ],
        );
    }

    public function getAdminStats(?int $chartYear = null, ?User $admin = null): array
    {
        [$chartYear, $chartYears] = $this->resolveChartYear($chartYear);

        $branchScope = $this->branchScopeId($admin);

        return array_merge(
            $this->adminHeadlines($admin),
            $this->completedSessionsTotal($branchScope),
            $this->feeTotals($branchScope),
            $this->cachedChartPayload($chartYear, $branchScope),
            [
                'chart_year'      => $chartYear,
                'chart_years'     => $chartYears,
                'recent_children' => User::children()
                    ->when($admin, fn ($q) => $q->visibleToStaff($admin))
                    ->with('disabilities')
                    ->latest()
                    ->limit(3)
                    ->get(),
            ],
        );
    }

    public function getTherapistStats(User $therapist): array
    {
        $therapistId = $therapist->id;

        return [
            'assigned_children' => User::children()
                ->whereHas('enrollments', fn($q) => $q->where('therapist_id', $therapistId)->whereIn('status', ['approved', 'active']))
                ->count(),
            'today_schedules'   => \App\Models\EnrollmentSchedule::where('therapist_id', $therapistId)
                ->whereDate('session_date', today())
                ->with('enrollment.child')
                ->get(),
            'upcoming_sessions' => \App\Models\EnrollmentSchedule::where('therapist_id', $therapistId)
                ->upcoming()
                ->with('enrollment.child')
                ->limit(15)
                ->get(),
            'assessments_today'    => \App\Models\Assessment::where('therapist_id', $therapistId)->where('status', 'publish')->whereDate('date', today())->count(),
            'assessments_upcoming' => \App\Models\Assessment::where('therapist_id', $therapistId)->where('status', 'publish')->where('date', '>', today()->toDateString())->count(),
            'assessment_buckets'   => $this->assessmentService->getTherapistAssessmentBuckets($therapistId),
        ];
    }

    public function getFinanceStats(?int $chartYear = null): array
    {
        [$chartYear, $chartYears] = $this->resolveChartYear($chartYear);
        $feeTotals = $this->feeTotals();
        $financeHeadlines = $this->financeHeadlines();

        return array_merge($feeTotals, $financeHeadlines, $this->cachedChartPayload($chartYear), [
            'total_expected'              => $feeTotals['fee_total_expected'],
            'total_paid'                  => $feeTotals['fee_total_paid'],
            'total_pending'               => $feeTotals['fee_pending_overdue'],
            'cash_received'               => $feeTotals['fee_cash_received'],
            'online_received'             => $feeTotals['fee_online_bank'],
            'chart_year'                  => $chartYear,
            'chart_years'                 => $chartYears,
            'recent_payments'             => Payment::with(['child', 'enrollment'])->latest()->limit(3)->get(),
        ]);
    }

    public function getChildStats(User $child): array
    {
        return $this->childPortalService->getDashboardPayload($child);
    }

    /**
     * @return array<string, mixed>
     */
    private function superAdminHeadlines(): array
    {
        return [
            'total_children'                => User::children()->count(),
            'approved_children'           => User::children()->approved()->count(),
            'pending_approvals'           => User::children()->pending()->count(),
            'total_therapists'            => User::byRole(Role::THERAPIST)->count(),
            'total_admins'                => User::byRole(Role::ADMIN)->count(),
            'total_finance_users'         => User::byRole(Role::FINANCE)->count(),
            'total_branches'              => \App\Models\Branch::count(),
            'total_services'              => \App\Models\Service::count(),
            'total_assessments'           => \App\Models\Assessment::where('status', 'completed')->count(),
            'total_enrollments'           => Enrollment::whereIn('status', ['active', 'completed'])->count(),
            'pending_high_discount'       => Enrollment::where('status', 'pending_super_admin_approval')->count(),
            'pending_payment_verifications' => Payment::where('status', 'pending_verification')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminHeadlines(?User $admin = null): array
    {
        $branchScope   = $this->branchScopeId($admin);
        $childrenQuery = User::children()->when($admin, fn ($q) => $q->visibleToStaff($admin));

        return [
            'total_children'                => (clone $childrenQuery)->count(),
            'pending_approvals'             => (clone $childrenQuery)->pending()->count(),
            'approved_children'             => (clone $childrenQuery)->approved()->count(),
            'total_assessments'             => $this->applyAssessmentBranchScope(\App\Models\Assessment::query()->where('status', 'completed'), $branchScope)->count(),
            'assessments_today'             => $this->applyAssessmentBranchScope(\App\Models\Assessment::query()->whereDate('date', today())->where('status', 'publish'), $branchScope)->count(),
            'upcoming_assessments'          => $this->applyAssessmentBranchScope(\App\Models\Assessment::upcoming(), $branchScope)->count(),
            'cancelled_assessments'         => $this->applyAssessmentBranchScope(\App\Models\Assessment::query()->where('status', 'cancelled'), $branchScope)->count(),
            'total_therapists'              => $this->therapistsInBranchScope($branchScope)->count(),
            'total_enrollments'             => $this->applyEnrollmentBranchScope(Enrollment::query()->whereIn('status', ['active', 'completed']), $branchScope)->count(),
            'high_discount_requests'        => $this->applyEnrollmentBranchScope(Enrollment::query()->where('status', 'pending_super_admin_approval'), $branchScope)->count(),
            'pending_payment_verifications' => $this->applyPaymentBranchScope(Payment::query()->where('status', 'pending_verification'), $branchScope)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeHeadlines(): array
    {
        return [
            'pending_verifications'       => Payment::where('status', 'pending_verification')->count(),
            'pending_verification_amount' => (float) Payment::where('status', 'pending_verification')->sum('amount'),
        ];
    }

    /**
     * All-time completed session occurrences (one-off rows + recurring occurrence records).
     *
     * @return array{total_completed_sessions: int}
     */
    private function completedSessionsTotal(?int $branchScope = null): array
    {
        $fixedQuery = EnrollmentSchedule::query()
            ->whereNotNull('session_date')
            ->where('status', 'completed');

        $recurringQuery = EnrollmentScheduleOccurrence::query()
            ->where('status', 'completed');

        if ($branchScope !== null) {
            $fixedQuery->whereHas('enrollment', fn ($eq) => $branchScope === 0
                ? $eq->whereRaw('0 = 1')
                : $eq->where('branch_id', $branchScope));
            $recurringQuery->whereHas('schedule.enrollment', fn ($eq) => $branchScope === 0
                ? $eq->whereRaw('0 = 1')
                : $eq->where('branch_id', $branchScope));
        }

        return ['total_completed_sessions' => $fixedQuery->count() + $recurringQuery->count()];
    }

    /**
     * @return array<string, float>
     */
    private function feeTotals(?int $branchScope = null): array
    {
        $enrollmentBase = $this->applyEnrollmentBranchScope(Enrollment::query(), $branchScope);
        $paymentBase    = $this->applyPaymentBranchScope(Payment::query(), $branchScope);

        return [
            'fee_total_expected'  => (float) (clone $enrollmentBase)->whereIn('status', ['approved', 'active', 'completed'])->sum('final_total'),
            'fee_total_paid'      => (float) (clone $paymentBase)->where('status', 'paid')->sum('amount'),
            'fee_pending_overdue' => (float) (clone $enrollmentBase)->whereIn('status', ['approved', 'active'])->sum('remaining_amount'),
            'fee_cash_received'   => (float) (clone $paymentBase)->where('status', 'paid')->where('payment_method', 'cash')->sum('amount'),
            'fee_online_bank'     => (float) (clone $paymentBase)->where('status', 'paid')->where('payment_method', '!=', 'cash')->sum('amount'),
            'pending_verification_amount' => (float) (clone $paymentBase)->where('status', 'pending_verification')->sum('amount'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cachedChartPayload(int $chartYear, ?int $branchScope = null): array
    {
        $scopeKey = $this->chartCacheScopeKey($branchScope);

        return $this->rememberDashboardCharts("charts.{$chartYear}.{$scopeKey}", function () use ($chartYear, $branchScope): array {
            return [
                'monthly_revenue'     => $this->getMonthlyRevenue($chartYear, $branchScope),
                'monthly_expected'    => $this->getMonthlyExpected($chartYear, $branchScope),
                'monthly_enrollments' => $this->getMonthlyEnrollments($chartYear, $branchScope),
                'chart_analytics'     => $this->buildChartAnalytics($chartYear, $branchScope),
            ];
        });
    }

    private function rememberDashboardCharts(string $key, callable $callback): mixed
    {
        return Cache::remember('frc.dashboard.' . $key, self::CHART_CACHE_TTL_SECONDS, $callback);
    }

    private function getMonthlyRevenue(int $year, ?int $branchScope = null): array
    {
        return $this->applyPaymentBranchScope(Payment::query(), $branchScope)
            ->where('status', 'paid')
            ->whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
    }

    /** Sum of final_total for enrollments created each month (PKR enrolled that month, not payment count). */
    private function getMonthlyExpected(int $year, ?int $branchScope = null): array
    {
        return $this->applyEnrollmentBranchScope(Enrollment::query(), $branchScope)
            ->whereIn('status', ['approved', 'active', 'completed'])
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(final_total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
    }

    private function getMonthlyEnrollments(int $year, ?int $branchScope = null): array
    {
        return $this->applyEnrollmentBranchScope(Enrollment::query(), $branchScope)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    private function buildChartAnalytics(int $year, ?int $branchScope = null): array
    {
        $childrenByStatus = $this->applyChildBranchScope(User::children(), $branchScope)
            ->whereYear('users.created_at', $year)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $paymentChannels = $this->applyPaymentBranchScope(Payment::query(), $branchScope)
            ->where('status', 'paid')
            ->whereYear('payment_date', $year)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END), 0) as cash_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_method != 'cash' THEN amount ELSE 0 END), 0) as online_total")
            ->first();

        $enrollmentsByStatus = $this->applyEnrollmentBranchScope(Enrollment::query(), $branchScope)
            ->whereYear('enrollments.created_at', $year)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $branchCollectedQuery = Payment::query()
            ->where('payments.status', 'paid')
            ->whereYear('payments.payment_date', $year)
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('branches', 'enrollments.branch_id', '=', 'branches.id');

        if ($branchScope !== null && $branchScope > 0) {
            $branchCollectedQuery->where('enrollments.branch_id', $branchScope);
        } elseif ($branchScope === 0) {
            $branchCollectedQuery->whereRaw('0 = 1');
        }

        $branchCollected = $branchCollectedQuery
            ->select('branches.name', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->name, 'value' => (float) $row->total])
            ->values()
            ->all();

        $servicePopularity = $this->applyEnrollmentBranchScope(Enrollment::query(), $branchScope)
            ->whereYear('enrollments.created_at', $year)
            ->join('services', 'enrollments.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('COUNT(*) as total'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->name, 'value' => (int) $row->total])
            ->values()
            ->all();

        return [
            'children_by_status' => $this->formatStatusSlices($childrenByStatus, [
                'pending'  => ['label' => 'Pending', 'color' => '#e08000'],
                'approved' => ['label' => 'Approved', 'color' => '#28a745'],
                'active'   => ['label' => 'Active', 'color' => '#16acac'],
                'rejected' => ['label' => 'Rejected', 'color' => '#dc3545'],
                'inactive' => ['label' => 'Inactive', 'color' => '#94a3b8'],
            ]),
            'enrollments_by_status' => $this->formatStatusSlices($enrollmentsByStatus, [
                'active'                        => ['label' => 'Active', 'color' => '#16acac'],
                'approved'                      => ['label' => 'Approved', 'color' => '#28a745'],
                'completed'                     => ['label' => 'Completed', 'color' => '#11517c'],
                'pending_super_admin_approval'  => ['label' => 'Discount pending', 'color' => '#e08000'],
                'draft'                         => ['label' => 'Draft', 'color' => '#94a3b8'],
                'cancelled'                     => ['label' => 'Cancelled', 'color' => '#dc3545'],
            ]),
            'payment_channels' => [
                'cash'   => (float) ($paymentChannels->cash_total ?? 0),
                'online' => (float) ($paymentChannels->online_total ?? 0),
            ],
            'branch_collected'    => $branchCollected,
            'service_popularity'  => $servicePopularity,
            'monthly_child_registrations' => $this->getMonthlyChildRegistrations($year, $branchScope),
            'operational_alerts' => $this->operationalAlertSlices($branchScope),
        ];
    }

    /**
     * @return list<array{label: string, value: int, color: string}>
     */
    private function operationalAlertSlices(?int $branchScope = null): array
    {
        return [
            ['label' => 'Child approvals', 'value' => $this->applyChildBranchScope(User::children()->pending(), $branchScope)->count(), 'color' => '#e08000'],
            ['label' => 'Payment verify', 'value' => $this->applyPaymentBranchScope(Payment::query()->where('status', 'pending_verification'), $branchScope)->count(), 'color' => '#7c3aed'],
            ['label' => 'High discount', 'value' => $this->applyEnrollmentBranchScope(Enrollment::query()->where('status', 'pending_super_admin_approval'), $branchScope)->count(), 'color' => '#11517c'],
        ];
    }

    private function formatStatusSlices(array $counts, array $map): array
    {
        $slices = [];
        foreach ($map as $key => $meta) {
            $value = (int) ($counts[$key] ?? 0);
            if ($value > 0) {
                $slices[] = [
                    'label' => $meta['label'],
                    'value' => $value,
                    'color' => $meta['color'],
                ];
            }
        }
        foreach ($counts as $key => $value) {
            if (isset($map[$key]) || (int) $value <= 0) {
                continue;
            }
            $slices[] = [
                'label' => ucfirst(str_replace('_', ' ', (string) $key)),
                'value' => (int) $value,
                'color' => '#64748b',
            ];
        }

        return $slices;
    }

    private function getMonthlyChildRegistrations(int $year, ?int $branchScope = null): array
    {
        return $this->applyChildBranchScope(User::children(), $branchScope)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    /**
     * null = all branches (super admin); positive int = branch id; 0 = no branch assigned (empty).
     */
    private function branchScopeId(?User $staff): ?int
    {
        if ($staff === null || $staff->isSuperAdmin()) {
            return null;
        }

        if ($staff->isAdmin()) {
            return $staff->branch_id ? (int) $staff->branch_id : 0;
        }

        return null;
    }

    private function chartCacheScopeKey(?int $branchScope): string
    {
        if ($branchScope === null) {
            return 'global';
        }

        return $branchScope === 0 ? 'branch.none' : 'branch.' . $branchScope;
    }

    private function therapistsInBranchScope(?int $branchScope)
    {
        $query = User::byRole(Role::THERAPIST);

        if ($branchScope === null) {
            return $query;
        }

        if ($branchScope === 0) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('therapistProfile', fn ($q) => $q->where('branch_id', $branchScope));
    }

    private function applyChildBranchScope($query, ?int $branchScope)
    {
        if ($branchScope === null) {
            return $query;
        }

        if ($branchScope === 0) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('users.branch_id', $branchScope);
    }

    private function applyEnrollmentBranchScope($query, ?int $branchScope)
    {
        if ($branchScope === null) {
            return $query;
        }

        if ($branchScope === 0) {
            return $query->whereRaw('0 = 1');
        }

        $table = $query->getModel()->getTable();

        return $query->where("{$table}.branch_id", $branchScope);
    }

    private function applyAssessmentBranchScope($query, ?int $branchScope)
    {
        if ($branchScope === null) {
            return $query;
        }

        if ($branchScope === 0) {
            return $query->whereRaw('0 = 1');
        }

        $table = $query->getModel()->getTable();

        return $query->where("{$table}.branch_id", $branchScope);
    }

    private function applyPaymentBranchScope($query, ?int $branchScope)
    {
        if ($branchScope === null) {
            return $query;
        }

        if ($branchScope === 0) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('enrollment', fn ($q) => $q->where('branch_id', $branchScope));
    }

    /** @return array{0: int, 1: int[]} */
    private function resolveChartYear(?int $chartYear): array
    {
        $chartYears = $this->getAvailableChartYears();
        $chartYear = $chartYear ?? (int) now()->year;
        if (! in_array($chartYear, $chartYears, true)) {
            $chartYear = $chartYears[0] ?? (int) now()->year;
        }

        return [$chartYear, $chartYears];
    }

    /** @return int[] Years (newest first) that have payment or enrollment data. */
    private function getAvailableChartYears(): array
    {
        $paymentYear = Payment::where('status', 'paid')
            ->whereNotNull('payment_date')
            ->min(DB::raw('YEAR(payment_date)'));
        $enrollmentYear = Enrollment::min(DB::raw('YEAR(created_at)'));
        $start = (int) min(array_filter([(int) $paymentYear, (int) $enrollmentYear], fn($y) => $y > 0) ?: [(int) now()->year]);
        $end = (int) now()->year;

        return range($end, $start);
    }
}
