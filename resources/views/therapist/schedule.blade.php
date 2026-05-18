@extends('layouts.app')
@section('title', 'My Schedule')
@section('page-title', 'My Schedule')

@section('content')
<div class="row g-3">
    {{-- Today's Sessions --}}
    <div class="col-12">
        <div class="card-frc">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-calendar-day me-2" style="color:var(--teal);"></i>Today's Sessions — {{ now()->format('l, d M Y') }}</h6>
            </div>
            @if(empty($stats['today_schedules']) || count($stats['today_schedules']) === 0)
                <div class="empty-state" style="padding:24px;">
                    <i class="fa-solid fa-mug-hot empty-icon" style="font-size:28px;"></i>
                    <p>No sessions scheduled for today.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table-frc">
                        <thead><tr><th>Time</th><th>Child</th><th>Branch</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($stats['today_schedules'] as $s)
                                <tr>
                                    <td style="font-weight:600;color:var(--navy);">{{ $s->time_slot }}</td>
                                    <td>{{ $s->enrollment?->child?->full_name }}</td>
                                    <td style="font-size:13px;">{{ $s->branch?->name }}</td>
                                    <td><span class="badge-status badge-{{ $s->status == 'scheduled' ? 'active' : $s->status }}">{{ ucfirst($s->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Upcoming Sessions --}}
    <div class="col-12">
        <div class="card-frc">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-calendar-week me-2" style="color:var(--teal);"></i>Upcoming Sessions</h6>
            </div>
            @if(empty($stats['upcoming_sessions']) || count($stats['upcoming_sessions']) === 0)
                <div class="empty-state" style="padding:24px;"><p>No upcoming sessions.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table-frc">
                        <thead><tr><th>Date</th><th>Day</th><th>Time</th><th>Child</th><th>Branch</th></tr></thead>
                        <tbody>
                            @foreach($stats['upcoming_sessions'] as $s)
                                <tr>
                                    <td style="font-weight:600;">{{ $s->session_date?->format('d M Y') ?? $s->day }}</td>
                                    <td style="font-size:13px;color:var(--text-muted);">{{ $s->day }}</td>
                                    <td>{{ $s->time_slot }}</td>
                                    <td>{{ $s->enrollment?->child?->full_name }}</td>
                                    <td style="font-size:13px;">{{ $s->branch?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
