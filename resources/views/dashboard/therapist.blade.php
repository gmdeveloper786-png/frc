@extends('layouts.app')
@section('title', 'Therapist Dashboard')
@section('page-title', 'Therapist Dashboard')

@section('content')
@php
    $stats = $portal['stats'];
    $todayAssessmentsPreview = $portal['today_assessments_preview'];
    $todayAssessmentsTotal = $portal['today_assessments_total'];
    $todaySessionsPreview = $portal['today_sessions_preview'];
    $todaySessionsTotal = $portal['today_sessions_total'];
    $dashboardPreviewLimit = \App\Services\TherapistPortalService::DASHBOARD_PREVIEW_LIMIT;
    $todayDate = now()->toDateString();
    $todayAssessmentsUrl = route('therapist.assessments.index', [
        'start_date' => $todayDate,
        'end_date' => $todayDate,
    ]);
    $todaySessionsUrl = route('therapist.sessions.index', [
        'start_date' => $todayDate,
        'end_date' => $todayDate,
    ]);
    $upcomingWeekStart = now()->copy()->addDay()->toDateString();
    $upcomingWeekEnd = now()->copy()->addDays(7)->toDateString();
    $upcomingAssessmentsUrl = route('therapist.assessments.index', [
        'start_date' => $upcomingWeekStart,
        'end_date' => $upcomingWeekEnd,
        'status' => 'publish',
    ]);
    $upcomingSessionsUrl = route('therapist.sessions.index', [
        'start_date' => $upcomingWeekStart,
        'end_date' => $upcomingWeekEnd,
    ]);
@endphp

<div class="therapist-dashboard-page">
    <div class="row g-3 mb-4 therapist-dashboard-stats">
        @foreach([
            ['label' => "Today's Assessments", 'value' => $stats['today_assessments'], 'icon' => 'fa-calendar-day', 'tone' => 'navy', 'href' => $todayAssessmentsUrl],
            ['label' => 'Upcoming Assessments', 'value' => $stats['upcoming_assessments'], 'icon' => 'fa-calendar-plus', 'tone' => 'teal', 'hint' => 'Next 7 days', 'href' => $upcomingAssessmentsUrl],
            ['label' => 'Completed Assessments', 'value' => $stats['completed_assessments'], 'icon' => 'fa-circle-check', 'tone' => 'navy', 'href' => route('therapist.assessments.index', ['status' => 'completed'])],
            ['label' => 'Cancelled Assessments', 'value' => $stats['cancelled_assessments'], 'icon' => 'fa-calendar-xmark', 'tone' => 'teal', 'href' => route('therapist.assessments.index', ['status' => 'cancelled'])],
            ['label' => "Today's Sessions", 'value' => $stats['today_sessions'], 'icon' => 'fa-clock', 'tone' => 'navy', 'href' => $todaySessionsUrl],
            ['label' => 'Upcoming Sessions', 'value' => $stats['upcoming_sessions'], 'icon' => 'fa-calendar-week', 'tone' => 'teal', 'hint' => 'Next 7 days', 'href' => $upcomingSessionsUrl],
            ['label' => 'Completed Sessions', 'value' => $stats['completed_sessions'], 'icon' => 'fa-flag-checkered', 'tone' => 'navy', 'href' => route('therapist.sessions.index', ['status' => 'completed'])],
            ['label' => 'Cancelled Sessions', 'value' => $stats['cancelled_sessions'], 'icon' => 'fa-ban', 'tone' => 'teal', 'href' => route('therapist.sessions.index', ['status' => 'cancelled'])],
            ['label' => 'Assigned Children', 'value' => $stats['assigned_children'], 'icon' => 'fa-children', 'tone' => 'navy', 'href' => route('therapist.children.index')],
        ] as $card)
            <div class="col-12 col-sm-6 col-xl-4">
                @if(! empty($card['href'] ?? null))
                    <a href="{{ $card['href'] }}" class="stat-card-link d-block h-100 text-reset text-decoration-none rounded-3">
                @endif
                <div class="stat-card h-100 {{ ! empty($card['href'] ?? null) ? 'stat-card--clickable' : '' }}">
                    <div class="stat-icon {{ $card['tone'] }}"><i class="fa-solid {{ $card['icon'] }}"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{{ $card['value'] }}</div>
                        <div class="stat-label">{{ $card['label'] }}</div>
                        @if(! empty($card['hint'] ?? null))
                            <div class="stat-hint">{{ $card['hint'] }}</div>
                        @endif
                    </div>
                </div>
                @if(! empty($card['href'] ?? null))
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4 therapist-dashboard-panels">
        <div class="col-12 col-lg-6">
            <div class="card-frc h-100 therapist-dashboard-panel">
                <div class="card-header-frc therapist-dashboard-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title-frc mb-0"><i class="fa-solid fa-sun me-2" style="color:var(--teal);"></i>Today's Assessments</h6>
                    <a href="{{ $todayAssessmentsUrl }}" class="btn-outline-teal therapist-dashboard-panel-btn">View all today</a>
                </div>
                <div class="therapist-dashboard-panel-body">
                    @if($todayAssessmentsPreview->isEmpty())
                        <div class="empty-state py-4"><p class="text-muted mb-0 small">No assessments assigned for today.</p></div>
                    @else
                        @foreach($todayAssessmentsPreview as $a)
                            @php
                                $childrenList = $a->children->filter(fn ($c) => filled($c->full_name));
                                $firstChild = $childrenList->first();
                                $extraChildCount = max(0, $childrenList->count() - 1);
                                $childNames = $childrenList->pluck('full_name')->join(', ');
                            @endphp
                            <div class="therapist-dashboard-session-item">
                                <div class="therapist-dashboard-session-main">
                                    <div class="therapist-dashboard-session-name" @if($childNames !== '') title="{{ $childNames }}" @endif>
                                        @if($firstChild)
                                            <a href="{{ route('therapist.children.show', $firstChild) }}" style="color:var(--navy);font-weight:500;text-decoration:underline;">{{ $firstChild->full_name }}</a>
                                            @if($extraChildCount > 0)
                                                <span class="text-muted"> +{{ $extraChildCount }} more</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                    <div class="therapist-dashboard-session-meta">{{ \Carbon\Carbon::parse($a->time)->format('h:i A') }} · {{ $a->branch?->name ?? '—' }}</div>
                                </div>
                                <div class="therapist-dashboard-session-actions">
                                    <span class="badge-status badge-{{ $a->status === 'cancelled' ? 'cancelled' : $a->status }}">{{ $a->status === 'cancelled' ? 'Cancelled' : ucfirst($a->status) }}</span>
                                    <a href="{{ route('therapist.assessments.show', $a) }}" class="btn-outline-teal btn-sm-frc">View</a>
                                </div>
                            </div>
                        @endforeach
                        @if($todayAssessmentsTotal > $dashboardPreviewLimit)
                            <p class="therapist-dashboard-more-note">
                                Showing {{ $dashboardPreviewLimit }} of {{ $todayAssessmentsTotal }}.
                                <a href="{{ $todayAssessmentsUrl }}" class="therapist-dashboard-more-link">View all</a>
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card-frc h-100 therapist-dashboard-panel">
                <div class="card-header-frc therapist-dashboard-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title-frc mb-0"><i class="fa-solid fa-calendar-day me-2" style="color:var(--teal);"></i>Today's Sessions</h6>
                    <a href="{{ $todaySessionsUrl }}" class="btn-outline-teal therapist-dashboard-panel-btn">View all today</a>
                </div>
                <div class="therapist-dashboard-panel-body">
                    @if($todaySessionsPreview->isEmpty())
                        <div class="empty-state py-4"><p class="text-muted mb-0 small">No sessions on your roster today.</p></div>
                    @else
                        @foreach($todaySessionsPreview as $row)
                            @php
                                $sch = $row['schedule'];
                                $occStatus = (string) ($row['status'] ?? $sch->status);
                                $cid = $sch->enrollment?->child_id;
                                $sessionMembers = ! empty($row['is_group']) && ! empty($row['group_members'])
                                    ? collect($row['group_members'])->filter(fn ($m) => filled($m['child_name'] ?? null))
                                    : collect($cid ? [['child_id' => $cid, 'child_name' => $row['child_name'] ?? '—']] : []);
                                $firstSessionChild = $sessionMembers->first();
                                $extraSessionChildCount = max(0, $sessionMembers->count() - 1);
                                $sessionChildNames = $sessionMembers->pluck('child_name')->filter()->join(', ');
                                $firstSessionChildId = (int) ($firstSessionChild['child_id'] ?? 0);
                                $sb = match ($occStatus) {
                                    'scheduled' => 'badge-session-scheduled',
                                    'in_progress' => 'badge-session-in-progress',
                                    'completed' => 'badge-session-completed',
                                    'cancelled' => 'badge-session-cancelled',
                                    'no_show' => 'badge-session-no-show',
                                    default => 'badge-draft',
                                };
                            @endphp
                            <div class="therapist-dashboard-session-item">
                                <div class="therapist-dashboard-session-main">
                                    <div class="therapist-dashboard-session-name" @if($sessionChildNames !== '') title="{{ $sessionChildNames }}" @endif>
                                        @if($firstSessionChild)
                                            @if($firstSessionChildId > 0)
                                                <a href="{{ route('therapist.children.show', $firstSessionChildId) }}" style="color:var(--navy);font-weight:500;text-decoration:underline;">{{ $firstSessionChild['child_name'] }}</a>
                                            @else
                                                {{ $firstSessionChild['child_name'] }}
                                            @endif
                                            @if($extraSessionChildCount > 0)
                                                <span class="text-muted"> +{{ $extraSessionChildCount }} more</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                    <div class="therapist-dashboard-session-meta">{{ $row['time_slot'] }} · {{ $row['service_name'] }}</div>
                                </div>
                                <div class="therapist-dashboard-session-actions">
                                    <span class="badge-status {{ $sb }}">{{ ucfirst(str_replace('_', ' ', $occStatus)) }}</span>
                                    @if($firstSessionChildId > 0)
                                        <a href="{{ route('therapist.children.show', $firstSessionChildId) }}" class="btn-outline-teal btn-sm-frc">Child</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if($todaySessionsTotal > $dashboardPreviewLimit)
                            <p class="therapist-dashboard-more-note">
                                Showing {{ $dashboardPreviewLimit }} of {{ $todaySessionsTotal }}.
                                <a href="{{ $todaySessionsUrl }}" class="therapist-dashboard-more-link">View all</a>
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
