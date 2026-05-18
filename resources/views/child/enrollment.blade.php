@extends('layouts.app')
@section('title', 'My Enrollment')
@section('page-title', 'My Enrollment')

@section('content')
@if($enrollmentRows->isEmpty())
    <div class="empty-state">
        <i class="fa-solid fa-file-contract empty-icon"></i>
        <h5>No enrollment yet</h5>
        <p>You have not been enrolled in any programme yet. Please contact the administration.</p>
    </div>
@else
    <p class="text-muted small mb-3">Each programme is listed below. Open <strong>View details</strong> for sessions, fees, and next appointment.</p>
    @foreach($enrollmentRows as $row)
        @php
            $e = $row['enrollment'];
            $nextSession = $row['next_session'];
            $out = $e->outstandingAmount();
            $final = (float) $e->final_total;
            $payEff = $e->effectivePaymentStatus();
        @endphp
        <div class="card-frc card-frc--list-page mb-3">
            <div>
                <div class="d-flex flex-column flex-md-row gap-3 align-items-start justify-content-between">
                    <div class="flex-grow-1 min-w-0">
                        <div style="font-weight:700;font-size:17px;color:var(--navy);line-height:1.25;">{{ $e->service?->name ?? 'Enrollment' }}</div>
                        <div class="text-muted small mt-1">{{ $e->branch?->name ?? '—' }} · {{ $e->therapist?->full_name ?? '—' }}</div>
                        @if($nextSession)
                            <div class="small mt-2" style="color:var(--teal-dark);">
                                <i class="fa-regular fa-calendar me-1"></i>{{ $nextSession['day_label'] }}, {{ $nextSession['date_label'] }} · {{ $nextSession['time_slot'] }}
                            </div>
                        @endif
                        <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge-status badge-{{ $e->status }}">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $e->status)) }}</span>
                            <span class="badge-status badge-{{ $payEff }}">{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($payEff) }}</span>
                        </div>
                    </div>
                    <div class="text-md-end flex-shrink-0 pt-1" style="min-width:132px;">
                        @if($out > 0)
                            <div class="text-muted small mb-0" style="font-size:11px;">Remaining</div>
                            <div style="font-weight:700;font-size:18px;color:var(--danger);">PKR {{ number_format($out, 2) }}</div>
                            <span class="badge-status badge-{{ $payEff }} text-danger">{{
                                \App\Models\Payment::labelForEnrollmentPaymentStatus($payEff) }}</span>
                        @else
                            <div class="text-muted small mb-0" style="font-size:11px;">Programme fee</div>
                            <div style="font-weight:700;font-size:18px;color:var(--success);">PKR {{ number_format($final, 2) }}</div>
                            <span class="badge-status badge-{{ $payEff }} text-success">{{
                                \App\Models\Payment::labelForEnrollmentPaymentStatus($payEff) }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3 pt-3" style="border-top:1px solid var(--border-soft);">
                    <a href="{{ route('child.enrollment.show', $e) }}" class="btn-teal" style="border-radius:10px;font-size:13px;padding:8px 14px;">
                        <i class="fa-solid fa-circle-info me-1"></i> View details
                    </a>
                    <a href="{{ route('child.schedule.index') }}" class="btn-outline-teal" style="border-radius:10px;font-size:13px;padding:8px 14px;">
                        <i class="fa-solid fa-calendar-week me-1"></i> View Full Schedule
                    </a>
                    @if($row['show_upload_slip_button'])
                        <a href="{{ route('child.upload-slip', ['enrollment_id' => $e->id]) }}" class="btn-outline-teal" style="border-radius:10px;font-size:13px;padding:8px 14px;">
                            <i class="fa-solid fa-upload me-1"></i> Upload slip
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
