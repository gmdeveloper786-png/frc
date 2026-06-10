{{-- Compact year filter for chart cards. Expects $stats with chart_year and chart_years. --}}
@php
    $selectId = $id ?? 'chart_year_' . uniqid();
@endphp
<form method="GET" class="dashboard-chart-filter dashboard-chart-filter--card">
    <div class="dashboard-year-picker">
        <i class="fa-regular fa-calendar-days dashboard-year-picker-icon" aria-hidden="true"></i>
        <select name="chart_year" id="{{ $selectId }}" class="dashboard-year-picker-select" data-auto-submit aria-label="Filter by year">
            @foreach($stats['chart_years'] as $y)
                <option value="{{ $y }}" @selected($stats['chart_year'] == $y)>{{ $y }}</option>
            @endforeach
        </select>
        <span class="dashboard-year-picker-chevron" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
    </div>
</form>
