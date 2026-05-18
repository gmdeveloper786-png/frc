@extends('layouts.app')
@section('title', 'Session details')
@section('page-title', 'Session details')

@section('content')
<div class="card-frc mb-4" style="border-radius:14px;overflow:hidden;">
    <div class="card-header-frc flex-wrap gap-2">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-circle-info me-2" style="color:var(--teal);"></i>Session details</h6>
        <span class="badge-status {{ $detail['badge_class'] }}" style="font-size:12px;">{{ $detail['status_label'] }}</span>
    </div>
    <div class="p-3 p-md-4">
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

            <dt class="col-sm-4 text-muted mb-2">Child</dt>
            <dd class="col-sm-8 mb-2 fw-semibold" style="color:var(--navy);">{{ $enrollment->child?->full_name ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Enrollment reference</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['enrollment_label'] }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Status</dt>
            <dd class="col-sm-8 mb-2">{{ $detail['status_label'] }}</dd>

            @if(! empty($detail['notes']))
                <dt class="col-sm-4 text-muted mb-2">Notes / instructions</dt>
                <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;">{{ $detail['notes'] }}</dd>
            @endif
        </dl>

        @if(! empty($occurrenceDetail))
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
                <dt class="col-sm-4 text-muted mb-2">Progress note status</dt>
                <dd class="col-sm-8 mb-2">
                    @php $ps = $occurrenceDetail['progress_note_status'] ?? 'none'; @endphp
                    @if($ps === 'none')
                        No progress note
                    @elseif($ps === 'draft')
                        Draft progress note
                    @else
                        Completed progress note
                    @endif
                </dd>
                @if(! empty($occurrenceDetail['progress_note_preview']))
                    <dt class="col-sm-4 text-muted mb-2">Progress documentation preview</dt>
                    <dd class="col-sm-8 mb-2">
                        <div class="small border rounded p-2" style="border-color:var(--border-soft)!important;background:var(--bg-light);">
                            <div class="mb-1"><span class="text-muted">Progress level</span><br><strong>{{ $occurrenceDetail['progress_note_preview']['progress_level'] ?? '—' }}</strong></div>
                            <div class="mb-1"><span class="text-muted">Therapy goal</span><br>{{ $occurrenceDetail['progress_note_preview']['therapy_goal'] ?? '—' }}</div>
                            <div class="mb-0"><span class="text-muted">Notes preview</span><br>{{ $occurrenceDetail['progress_note_preview']['notes_excerpt'] ?? '—' }}</div>
                        </div>
                    </dd>
                @endif
            </dl>
        @endif

        <div class="mt-4 d-flex flex-wrap gap-2">
            <a href="{{ route('enrollments.schedule', $enrollment) }}" class="btn-outline-teal d-inline-flex align-items-center gap-1"><i class="fa-solid fa-arrow-left"></i> Back to enrollment schedule</a>
            @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                <a href="{{ route('enrollments.show', $enrollment->id) }}" class="btn-teal d-inline-flex align-items-center gap-1"><i class="fa-solid fa-file-contract"></i> Enrollment detail</a>
            @endif
        </div>
    </div>
</div>
@endsection
