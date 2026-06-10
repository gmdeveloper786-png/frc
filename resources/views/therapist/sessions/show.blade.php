@extends('layouts.app')
@section('title', 'Session details')
@section('page-title', 'Session details')

@section('content')
<div class="card-frc mb-4" style="border-radius:14px;overflow:hidden;">
    <div class="card-header-frc flex-wrap gap-2">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-circle-info me-2" style="color:var(--teal);"></i>Session details</h6>
        <span class="badge-status {{ $statusBadge }}" style="font-size:12px;">{{ $occurrenceDetail['status_label'] ?? ucfirst(str_replace('_', ' ', $schedule->status)) }}</span>
    </div>
    <div class="p-3 p-md-4">
        <dl class="row mb-0 small">
            <dt class="col-sm-4 text-muted mb-2">Session date</dt>
            <dd class="col-sm-8 mb-2 fw-semibold" style="color:var(--navy);">{{ $occurrenceDetail['session_date_label'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Day</dt>
            <dd class="col-sm-8 mb-2">{{ $occurrenceDetail['day_label'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Time</dt>
            <dd class="col-sm-8 mb-2">{{ $occurrenceDetail['time_slot'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Branch</dt>
            <dd class="col-sm-8 mb-2">{{ $occurrenceDetail['branch_name'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Therapist</dt>
            <dd class="col-sm-8 mb-2">{{ $occurrenceDetail['therapist_name'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Service</dt>
            <dd class="col-sm-8 mb-2">{{ $occurrenceDetail['service_name'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Child</dt>
            <dd class="col-sm-8 mb-2 fw-semibold" style="color:var(--navy);">
                @if(! empty($occurrenceDetail['child_id']))
                    <a href="{{ route('therapist.children.show', $occurrenceDetail['child_id']) }}" style="color:var(--navy);">{{ $occurrenceDetail['child_name'] }}</a>
                @else
                    {{ $occurrenceDetail['child_name'] ?? '—' }}
                @endif
            </dd>

            <dt class="col-sm-4 text-muted mb-2">Status</dt>
            <dd class="col-sm-8 mb-2">{{ $occurrenceDetail['status_label'] ?? '—' }}</dd>
        </dl>

        <hr class="my-4" style="opacity:.15;">
        <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--teal);letter-spacing:.04em;">Session lifecycle</h6>
        <dl class="row mb-0 small">
            <dt class="col-sm-4 text-muted mb-2">Started</dt>
            <dd class="col-sm-8 mb-2">
                @if(! empty($occurrenceDetail['started_at']))
                    {{ \Illuminate\Support\Carbon::parse($occurrenceDetail['started_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                    @if(! empty($occurrenceDetail['started_by_name']))
                        <span class="text-muted"> — {{ $occurrenceDetail['started_by_name'] }}</span>
                    @endif
                @else
                    —
                @endif
            </dd>
            <dt class="col-sm-4 text-muted mb-2">Completed</dt>
            <dd class="col-sm-8 mb-2">
                @if(! empty($occurrenceDetail['completed_at']))
                    {{ \Illuminate\Support\Carbon::parse($occurrenceDetail['completed_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                    @if(! empty($occurrenceDetail['completed_by_name']))
                        <span class="text-muted"> — {{ $occurrenceDetail['completed_by_name'] }}</span>
                    @endif
                @else
                    —
                @endif
            </dd>
            <dt class="col-sm-4 text-muted mb-2">Completion note</dt>
            <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;">{{ ! empty($occurrenceDetail['completion_note']) ? $occurrenceDetail['completion_note'] : '—' }}</dd>
            <dt class="col-sm-4 text-muted mb-2">Cancelled</dt>
            <dd class="col-sm-8 mb-2">
                @if(! empty($occurrenceDetail['cancelled_at']))
                    {{ \Illuminate\Support\Carbon::parse($occurrenceDetail['cancelled_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                    @if(! empty($occurrenceDetail['cancelled_by_name']))
                        <span class="text-muted"> — {{ $occurrenceDetail['cancelled_by_name'] }}</span>
                    @endif
                @else
                    —
                @endif
            </dd>
            <dt class="col-sm-4 text-muted mb-2">Cancellation reason</dt>
            <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;">{{ ! empty($occurrenceDetail['cancellation_reason']) ? $occurrenceDetail['cancellation_reason'] : '—' }}</dd>
        </dl>

        @include('sessions.partials.feedback-summary', ['sessionFeedback' => $sessionFeedback ?? ['items' => [], 'overall' => null]])

        <div class="mt-4 d-flex flex-wrap gap-2">
            <a href="{{ route('therapist.sessions.index') }}" class="btn-outline-teal d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i> Back to sessions
            </a>
        </div>
    </div>
</div>
@endsection
