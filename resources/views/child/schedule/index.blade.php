@extends('layouts.app')
@section('title', 'My Schedule')
@section('page-title', 'My Schedule')

@push('styles')
<style>
/* Match app pagination: full-width nav so â€œShowing â€¦â€ and controls are spaced (Bootstrap-5 default view). */
.child-schedule-pagination {
    margin-top: 0.5rem;
    padding-top: 1rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.child-schedule-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.child-schedule-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.child-schedule-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.child-schedule-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.child-schedule-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.child-schedule-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.child-schedule-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.child-schedule-pagination .page-link {
    border-radius: 8px;
    color: var(--navy, #11517c);
}
.child-schedule-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
@php
    $statusFilter = request('status', 'all');
    if ($statusFilter === 'no_show') {
        $statusFilter = 'all';
    }
@endphp

<div class="card-frc card-frc--list-page mb-4">
    <div class="card-header-frc flex-wrap gap-2">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-calendar-week me-2" style="color:var(--teal);"></i>My Session Schedule</h6>
    </div>
    <div>
        <div class="mb-4 p-3" style="background:var(--bg-light);border-radius:12px;border:1px solid var(--border-soft);">
            <div style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--navy);margin-bottom:12px;">
                <i class="fa-solid fa-calendar-check me-2" style="color:var(--teal);"></i>Next session
            </div>
            @if($nextSession ?? null)
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-4">
                        <div class="text-muted small mb-1">Session date</div>
                        <div style="font-weight:600;color:var(--navy);">{{ $nextSession['session_date']->format('d M Y') }}</div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <div class="text-muted small mb-1">Day</div>
                        <div style="font-weight:600;color:var(--navy);">{{ $nextSession['day_label'] }}</div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <div class="text-muted small mb-1">Time</div>
                        <div style="font-weight:600;color:var(--navy);font-size:14px;">{{ $nextSession['time_slot'] }}</div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="text-muted small mb-1">Therapist</div>
                        <div style="font-weight:600;color:var(--navy);font-size:14px;">{{ $nextSession['therapist_name'] }}</div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="text-muted small mb-1">Service</div>
                        <div style="font-weight:600;color:var(--navy);font-size:14px;">{{ $nextSession['service_name'] }}</div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="text-muted small mb-1">Status</div>
                        <span class="badge-status {{ $nextSession['badge_class'] }}" style="font-size:11px;">{{ $nextSession['status_label'] }}</span>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('child.schedule.show', ['schedule' => $nextSession['schedule_id'], 'session_date' => $nextSession['date_iso']]) }}" class="btn-outline-teal mt-1" style="font-size:12px;padding:6px 12px;display:inline-flex;">View details</a>
                    </div>
                </div>
            @else
                <p class="text-muted small mb-0">No upcoming scheduled sessions right now.</p>
            @endif
        </div>

        <form method="get" action="{{ route('child.schedule.index') }}" class="p-3 border-bottom list-filters">
            <div class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-lg">
                <label class="small text-muted mb-1">Date from</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-sm-6 col-lg">
                <label class="small text-muted mb-1">Date to</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-sm-6 col-lg">
                <label class="small text-muted mb-1">Service</label>
                <select name="service_id" class="form-control form-control-sm">
                    <option value="">All services</option>
                    @foreach($filterOptions['services'] as $svc)
                        <option value="{{ $svc['id'] }}" {{ (string) request('service_id') === (string) $svc['id'] ? 'selected' : '' }}>{{ $svc['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg">
                <label class="small text-muted mb-1">Therapist</label>
                <select name="therapist_id" class="form-control form-control-sm">
                    <option value="">All Therapists</option>
                    @foreach($filterOptions['therapists'] as $th)
                        <option value="{{ $th['id'] }}" {{ (string) request('therapist_id') === (string) $th['id'] ? 'selected' : '' }}>{{ $th['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg">
                            <label class="small text-muted mb-1">Status</label>
                            <select name="status" class="form-control form-control-sm">
                                <option value="all" {{ $statusFilter==='all' ? 'selected' : '' }}>All</option>
                                <option value="scheduled" {{ $statusFilter==='scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="completed" {{ $statusFilter==='completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $statusFilter==='cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
            <div class="col-12 filter-actions d-flex flex-wrap gap-2">
                <button type="submit" class="btn-teal flex-grow-1 flex-sm-grow-0">Filter</button>
                <a href="{{ route('child.schedule.index') }}" class="btn-outline-teal flex-grow-1 flex-sm-grow-0">Reset</a>
            </div>
            </div>
        </form>

        @if($paginator->isEmpty())
            <div class="empty-state py-5">
                <i class="fa-regular fa-calendar-xmark empty-icon"></i>
                <h5>No schedule found.</h5>
                <p class="text-muted mb-0 small">When your enrollment includes sessions, they will appear here with dates.</p>
            </div>
        @else
            <div class="p-3 pt-2">
            <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
                <table class="table-frc mb-0">
                    <thead>
                        <tr>
                            <th>Session date</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Branch</th>
                            <th>Therapist</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paginator as $row)
                            <tr>
                                <td style="font-weight:600;color:var(--navy);white-space:nowrap;">{{ $row['session_date']->format('d M Y') }}</td>
                                <td style="white-space:nowrap;">{{ $row['day_label'] }}</td>
                                <td style="white-space:normal;">{{ $row['time_slot'] }}</td>
                                <td style="white-space:normal;word-break:break-word;">{{ $row['branch_name'] }}</td>
                                <td style="white-space:normal;word-break:break-word;">{{ $row['therapist_name'] }}</td>
                                <td style="white-space:normal;word-break:break-word;">{{ $row['service_name'] }}</td>
                                <td style="white-space:nowrap;">
                                    <span class="badge-status {{ $row['badge_class'] }}" style="font-size:11px;">{{ $row['status_label'] }}</span>
                                </td>
                                <td style="white-space:nowrap;">
                                    <a href="{{ route('child.schedule.show', ['schedule' => $row['schedule_id'], 'session_date' => $row['date_iso']]) }}" class="btn-outline-teal" style="font-size:11px;padding:4px 10px;">View details</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($paginator->hasPages())
                <div class="child-schedule-pagination px-1" aria-label="Schedule pages">
                    {{ $paginator->withQueryString()->onEachSide(1)->links() }}
                </div>
            @endif
            </div>
        @endif
    </div>
</div>
@endsection
