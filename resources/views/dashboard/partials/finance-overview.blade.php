{{-- Finance overview: fee donut + monthly bars. Expects $stats with fee_* and chart_year keys. --}}
<div class="card-frc h-100 dashboard-finance-charts">
    <div class="card-header-frc card-header-frc--stack-sm dashboard-finance-charts-header">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-chart-pie me-2" style="color:var(--teal);"></i>Finance Overview</h6>
        @if(!empty($showYearFilter))
            <form method="GET" class="dashboard-chart-filter" id="chartYearForm">
                <label for="chart_year" class="dashboard-chart-filter-label">Year</label>
                <div class="dashboard-year-picker dashboard-year-picker--header">
                    <i class="fa-regular fa-calendar-days dashboard-year-picker-icon" aria-hidden="true"></i>
                    <select name="chart_year" id="chart_year" class="dashboard-year-picker-select" data-auto-submit aria-label="Filter by year">
                        @foreach($stats['chart_years'] as $y)
                            <option value="{{ $y }}" @selected($stats['chart_year'] == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                    <span class="dashboard-year-picker-chevron" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
                </div>
            </form>
        @endif
    </div>
    <div class="dashboard-finance-charts-body">
        <div class="dashboard-fee-donut-panel">
            <p class="dashboard-fee-panel-title">Fee status</p>
            <div class="dashboard-fee-donut-wrap">
                <canvas id="feeOverviewChart" aria-label="Fee overview: paid vs pending"></canvas>
                <div class="dashboard-fee-donut-center">
                    <span class="dashboard-fee-donut-label">Total Expected</span>
                    <strong class="dashboard-fee-donut-value">{{ frc_pkr($stats['fee_total_expected']) }}</strong>
                </div>
            </div>
            <ul class="dashboard-fee-donut-legend list-unstyled mb-0">
                <li class="dashboard-fee-donut-legend-item">
                    <span class="dashboard-fee-donut-legend-dot dashboard-fee-donut-legend-dot--paid" aria-hidden="true"></span>
                    <span class="dashboard-fee-donut-legend-label">Total Paid</span>
                    <strong class="dashboard-fee-donut-legend-amount">{{ frc_pkr($stats['fee_total_paid']) }}</strong>
                </li>
                <li class="dashboard-fee-donut-legend-item">
                    <span class="dashboard-fee-donut-legend-dot dashboard-fee-donut-legend-dot--pending" aria-hidden="true"></span>
                    <span class="dashboard-fee-donut-legend-label">Pending</span>
                    <strong class="dashboard-fee-donut-legend-amount dashboard-fee-donut-legend-amount--pending">{{ frc_pkr($stats['fee_pending_overdue']) }}</strong>
                </li>
            </ul>
        </div>
        <div class="dashboard-fee-bar-wrap">
            <p class="dashboard-fee-bar-title">Monthly breakdown ({{ $stats['chart_year'] }})</p>
            <div class="dashboard-fee-bar-legend">
                <span><i class="dashboard-legend-dot dashboard-legend-dot--paid"></i> Collected</span>
                <span><i class="dashboard-legend-dot dashboard-legend-dot--expected"></i> Enrolled fees</span>
            </div>
            <canvas id="monthlyFinanceChart" aria-label="Monthly paid vs expected fees"></canvas>
        </div>
    </div>
</div>