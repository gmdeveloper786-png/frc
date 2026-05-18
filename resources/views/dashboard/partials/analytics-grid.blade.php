@php
    $a = $stats['chart_analytics'] ?? [];
@endphp

<div class="dashboard-analytics-section mb-4">
    <div class="dashboard-analytics-head">
        <h6 class="dashboard-analytics-title"><i class="fa-solid fa-chart-simple me-2" style="color:var(--teal);"></i>Insights & trends</h6>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card-frc dashboard-chart-card h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Growth trends</h6>
                    @include('dashboard.partials.chart-year-select', ['stats' => $stats, 'id' => 'chart_year_growth'])
                </div>
                <div class="dashboard-chart-card-legend">
                    <span><i class="dashboard-legend-dot" style="background:#16acac;"></i> Enrollments</span>
                    <span><i class="dashboard-legend-dot" style="background:#11517c;"></i> New children</span>
                </div>
                <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--tall">
                    <canvas id="growthTrendsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card-frc dashboard-chart-card dashboard-chart-card--donut-compact h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Payment channels</h6>
                    @include('dashboard.partials.chart-year-select', ['stats' => $stats, 'id' => 'chart_year_payment_channels'])
                </div>
                @php
                    $payCash = (float) ($a['payment_channels']['cash'] ?? 0);
                    $payOnline = (float) ($a['payment_channels']['online'] ?? 0);
                    $payHasData = $payCash > 0 || $payOnline > 0;
                @endphp
                <div class="dashboard-donut-card-body">
                    <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--donut-sm">
                        @if($payHasData)
                            <canvas id="paymentMethodsChart"></canvas>
                        @else
                            <p class="dashboard-chart-empty text-muted small mb-0">No payments for this year.</p>
                        @endif
                    </div>
                    @if($payHasData)
                        <ul class="dashboard-stat-rows list-unstyled mb-0">
                            <li class="dashboard-stat-row">
                                <span class="dashboard-stat-row-label">
                                    <span class="dashboard-stat-swatch" style="background:#16acac;"></span>Cash
                                </span>
                                <strong>PKR {{ number_format($payCash) }}</strong>
                            </li>
                            <li class="dashboard-stat-row">
                                <span class="dashboard-stat-row-label">
                                    <span class="dashboard-stat-swatch" style="background:#11517c;"></span>Online / Bank
                                </span>
                                <strong>PKR {{ number_format($payOnline) }}</strong>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card-frc dashboard-chart-card dashboard-chart-card--donut-compact h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Children by status</h6>
                    @include('dashboard.partials.chart-year-select', ['stats' => $stats, 'id' => 'chart_year_children_status'])
                </div>
                <div class="dashboard-donut-card-body">
                    <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--donut-sm dashboard-donut-interactive">
                        @if(empty($a['children_by_status']))
                            <p class="dashboard-chart-empty text-muted small mb-0">No children registered for this year.</p>
                        @else
                            <canvas id="childrenStatusChart"></canvas>
                            <div class="dashboard-donut-center-info" id="childrenStatusCenter" aria-live="polite">
                                <span class="dashboard-donut-center-label">Hover or click segment</span>
                                <strong class="dashboard-donut-center-value">—</strong>
                            </div>
                        @endif
                    </div>
                    @if(!empty($a['children_by_status']))
                        <ul class="dashboard-stat-rows list-unstyled mb-0">
                            @foreach($a['children_by_status'] as $slice)
                                <li class="dashboard-stat-row">
                                    <span class="dashboard-stat-row-label">
                                        <span class="dashboard-stat-swatch" style="background:{{ $slice['color'] }};"></span>{{ $slice['label'] }}
                                    </span>
                                    <strong>{{ number_format($slice['value']) }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-frc dashboard-chart-card h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Collected by branch</h6>
                    @include('dashboard.partials.chart-year-select', ['stats' => $stats, 'id' => 'chart_year_branch'])
                </div>
                <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--bar-v">
                    @if(empty($a['branch_collected']))
                        <p class="dashboard-chart-empty text-muted small mb-0">No branch collections for this year.</p>
                    @else
                        <canvas id="branchCollectedChart"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-frc dashboard-chart-card h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Programmes (enrollments)</h6>
                    @include('dashboard.partials.chart-year-select', ['stats' => $stats, 'id' => 'chart_year_programmes'])
                </div>
                <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--bar-v">
                    @if(empty($a['service_popularity']))
                        <p class="dashboard-chart-empty text-muted small mb-0">No enrollments for this year.</p>
                    @else
                        <canvas id="servicePopularityChart"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card-frc dashboard-chart-card h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Enrollments by status</h6>
                    @include('dashboard.partials.chart-year-select', ['stats' => $stats, 'id' => 'chart_year_enrollments_status'])
                </div>
                <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--donut-md dashboard-donut-interactive">
                    @if(empty($a['enrollments_by_status']))
                        <p class="dashboard-chart-empty text-muted small mb-0">No enrollments for this year.</p>
                    @else
                        <canvas id="enrollmentsStatusChart"></canvas>
                        <div class="dashboard-donut-center-info" id="enrollmentsStatusCenter" aria-live="polite">
                            <span class="dashboard-donut-center-label">Hover or click segment</span>
                            <strong class="dashboard-donut-center-value">—</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card-frc dashboard-chart-card h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Collection rate</h6>
                    <div class="dashboard-chart-card-head-meta">
                        @include('dashboard.partials.chart-year-select', ['stats' => $stats, 'id' => 'chart_year_collection'])
                        <span class="dashboard-chart-card-sub">· % of enrolled fees</span>
                    </div>
                </div>
                <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--line">
                    <canvas id="collectionRateChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card-frc dashboard-chart-card h-100">
                <div class="dashboard-chart-card-head">
                    <h6 class="dashboard-chart-card-title">Needs attention</h6>
                </div>
                <div class="dashboard-chart-canvas-wrap dashboard-chart-canvas-wrap--alerts">
                    <canvas id="operationalAlertsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
