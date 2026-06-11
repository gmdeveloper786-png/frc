@extends('layouts.app')
@section('title', 'Session details')
@section('page-title', 'Session details')

@section('content')
<div class="card-frc mb-4 session-details-page" style="border-radius:14px;overflow:hidden;">
    <div class="card-header-frc flex-wrap gap-2">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-circle-info me-2" style="color:var(--teal);"></i>Session details</h6>
        <span class="badge-status {{ $detail['badge_class'] }}" style="font-size:12px;">{{ $detail['status_label'] }}</span>
    </div>
    <div class="p-3 p-md-4 session-details-body">
        @if(($detail['status'] ?? '') === 'cancelled')
            <div class="alert border-0 mb-4" role="alert" style="border-radius:12px;background:rgba(220,53,69,0.08);color:var(--navy);">
                <i class="fa-solid fa-circle-info me-2" style="color:var(--danger);"></i>
                Session cancelled. Please contact the centre for rescheduling.
            </div>
        @endif
        <dl class="row mb-0 small">
            <dt class="col-sm-4 text-muted mb-2">Session date</dt>
            <dd class="col-sm-8 mb-2 fw-semibold" style="color:var(--navy);">{{ $detail['session_date']->format('l, d M Y') }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Day</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['day_label'] }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Time</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['time_slot'] }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Branch</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['branch_name'] }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Therapist</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['therapist_name'] }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Service</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['service_name'] }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Enrollment reference</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['enrollment_label'] }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Status</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['status_label'] }}</dd>

            @if(! empty($detail['notes']) && ($detail['status'] ?? '') !== 'cancelled')
                <dt class="col-sm-4 text-muted mb-2">Notes / instructions</dt>
                <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;">{{ $detail['notes'] }}</dd>
            @endif
        </dl>

        @include('sessions.partials.feedback-summary', ['sessionFeedback' => $sessionFeedback ?? ['items' => [], 'overall' => null]])

        <div class="mt-4">
            <a href="{{ route('child.schedule.index') }}" class="btn-outline-teal"><i class="fa-solid fa-arrow-left"></i> Back to schedule</a>
        </div>
    </div>
</div>
@endsection
