@extends('layouts.app')
@section('title', 'Enrollment Schedule')
@section('page-title', 'Enrollment Schedule')

@section('content')
@php
    $statusFilter = request('status', 'all');
@endphp

<div class="card-frc mb-4" style="border-radius:14px;overflow:hidden;">
    <div class="card-header-frc flex-wrap gap-2">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-calendar-week me-2" style="color:var(--teal);"></i>Enrollment Schedule</h6>
        @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
            <a href="{{ route('enrollments.show', $enrollment->id) }}" class="btn-outline-teal d-inline-flex align-items-center gap-1" style="font-size:13px;padding:6px 14px;">
                <i class="fa-solid fa-arrow-left"></i> Enrollment detail
            </a>
        @elseif(auth()->user()->role?->name === 'child')
            <a href="{{ route('child.enrollment') }}" class="btn-outline-teal d-inline-flex align-items-center gap-1" style="font-size:13px;padding:6px 14px;">
                <i class="fa-solid fa-arrow-left"></i> My enrollment
            </a>
        @elseif(auth()->user()->role?->name === 'therapist')
            <a href="{{ route('therapist.sessions.index') }}" class="btn-outline-teal d-inline-flex align-items-center gap-1" style="font-size:13px;padding:6px 14px;">
                <i class="fa-solid fa-arrow-left"></i> My Session Scheduled
            </a>
        @elseif(auth()->user()->role?->name === 'finance')
            <a href="{{ route('dashboard.finance') }}" class="btn-outline-teal d-inline-flex align-items-center gap-1" style="font-size:13px;padding:6px 14px;">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        @endif
    </div>
    <div class="p-3 p-md-4">
        <div class="mb-4 p-3" style="background:var(--bg-light);border-radius:12px;border:1px solid var(--border-soft);">
            <div class="row g-3 small">
                <div class="col-sm-6 col-lg-4">
                    <div class="text-muted mb-1">Child</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $enrollment->child?->full_name ?? '—' }}</div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="text-muted mb-1">Enrollment ID</div>
                    <div style="font-weight:600;color:var(--navy);">#{{ $enrollment->id }}</div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted mb-1">Branch</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $enrollment->branch?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted mb-1">Therapist</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $enrollment->therapist?->full_name ?? '—' }}</div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="text-muted mb-1">Service</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $enrollment->service?->name ?? '—' }}</div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="text-muted mb-1">Total sessions</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $stats['total_sessions'] }}</div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="text-muted mb-1">Completed</div>
                    <div style="font-weight:600;color:var(--success);">{{ $stats['completed_sessions'] }}</div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted mb-1">Upcoming</div>
                    <div style="font-weight:600;color:var(--teal-dark);">{{ $stats['upcoming_sessions'] }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted mb-1">Payment status</div>
                    <span class="badge-status badge-{{ $enrollment->payment_status }}" style="font-size:11px;">{{ $stats['payment_status_label'] }}</span>
                </div>
            </div>
        </div>

        <form method="get" action="{{ route('enrollments.schedule', $enrollment) }}" class="row g-3 align-items-end mb-4">
            <div class="col-6 col-lg">
                <label class="small text-muted mb-1">Date from</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-lg">
                <label class="small text-muted mb-1">Date to</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-lg">
                            <label class="small text-muted mb-1">Status</label>
                            <select name="status" class="form-control form-control-sm">
                                <option value="all" {{ $statusFilter==='all' ? 'selected' : '' }}>All</option>
                                <option value="scheduled" {{ $statusFilter==='scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="in_progress" {{ $statusFilter==='in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ $statusFilter==='completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $statusFilter==='cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
            </div>
            <div class="col-6 col-lg">
                <label class="small text-muted mb-1">Service</label>
                <select name="service_id" class="form-control form-control-sm">
                    <option value="">All services</option>
                    @foreach($filterOptions['services'] as $svc)
                        <option value="{{ $svc['id'] }}" {{ (string) request('service_id') === (string) $svc['id'] ? 'selected' : '' }}>{{ $svc['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg">
                <label class="small text-muted mb-1">Therapist</label>
                <select name="therapist_id" class="form-control form-control-sm">
                    <option value="">All Therapists</option>
                    @foreach($filterOptions['therapists'] as $th)
                        <option value="{{ $th['id'] }}" {{ (string) request('therapist_id') === (string) $th['id'] ? 'selected' : '' }}>{{ $th['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn-teal" style="font-size:13px;padding:8px 16px;">Apply</button>
                <a href="{{ route('enrollments.schedule', $enrollment) }}" class="btn-outline-teal" style="font-size:13px;padding:8px 16px;">Reset</a>
            </div>
        </form>

        @if($paginator->isEmpty())
            <div class="empty-state py-5">
                <i class="fa-regular fa-calendar-xmark empty-icon"></i>
                <h5>No dated sessions found.</h5>
                <p class="text-muted mb-0 small">Adjust filters or ensure this enrolment has schedule slots configured.</p>
            </div>
        @else
            <div class="d-none d-md-block table-responsive" style="max-width:100%;overflow-x:visible;">
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
                                    <a href="{{ route('enrollments.schedule.show', ['enrollment' => $enrollment, 'schedule' => $row['schedule_id'], 'session_date' => $row['date_iso']]) }}" class="btn-outline-teal d-inline-flex align-items-center gap-1" style="font-size:11px;padding:4px 10px;">
                                        <i class="fa-solid fa-eye" style="font-size:10px;"></i> View details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-md-none">
                @foreach($paginator as $row)
                    <div class="card-frc mb-3 p-3" style="border-radius:12px;">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div style="font-weight:700;color:var(--navy);">{{ $row['session_date']->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $row['day_label'] }} · {{ $row['time_slot'] }}</div>
                            </div>
                            <span class="badge-status {{ $row['badge_class'] }}" style="font-size:11px;">{{ $row['status_label'] }}</span>
                        </div>
                        <div class="small mb-1"><span class="text-muted">Branch</span><br>{{ $row['branch_name'] }}</div>
                        <div class="small mb-1"><span class="text-muted">Therapist</span><br>{{ $row['therapist_name'] }}</div>
                        <div class="small mb-3"><span class="text-muted">Service</span><br>{{ $row['service_name'] }}</div>
                        <a href="{{ route('enrollments.schedule.show', ['enrollment' => $enrollment, 'schedule' => $row['schedule_id'], 'session_date' => $row['date_iso']]) }}" class="btn-outline-teal w-100 justify-content-center d-inline-flex align-items-center gap-1" style="font-size:12px;border-radius:10px;">
                            <i class="fa-solid fa-eye"></i> View details
                        </a>
                    </div>
                @endforeach
            </div>

            @if($paginator->hasPages())
                <div class="pt-3">{{ $paginator->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
