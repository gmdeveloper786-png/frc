@extends('layouts.app')
@section('title', 'Assessment details')
@section('page-title', 'Assessment details')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-frc mb-3" style="border-radius:14px;">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:56px;height:56px;background:var(--teal-light);border-radius:16px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-calendar-check" style="color:var(--teal);font-size:22px;"></i>
                    </div>
                    <div>
                        <div style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--navy);font-size:20px;">{{ $assessment->day }}, {{ $assessment->date?->format('d M Y') }}</div>
                        <div style="font-size:14px;color:var(--text-muted);">{{ \Carbon\Carbon::parse($assessment->time)->format('h:i A') }}</div>
                    </div>
                </div>
                <span class="badge-status badge-{{ $assessment->status === 'cancelled' ? 'cancelled' : $assessment->status }}">{{ $assessment->statusLabel() }}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="text-muted small mb-1">Branch</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $assessment->branch?->displayLabel() ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small mb-1">Service</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $assessment->services->pluck('name')->join(', ') ?: '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small mb-1">Therapist</div>
                    <div style="font-weight:600;color:var(--navy);">{{ $assessment->therapist?->full_name ?? '—' }}</div>
                </div>
            </div>

            @if($assessment->status === 'cancelled')
                <div class="mb-4 p-4 rounded-3" style="background:#fff5f5;border:1px solid #fecaca;">
                    <div class="fw-semibold mb-2" style="color:var(--navy);"><i class="fa-solid fa-circle-info me-2" style="color:var(--danger);"></i>Assessment cancelled</div>
                    <p class="mb-0" style="font-size:14px;color:var(--text-dark);">Your assessment has been cancelled. Please contact the centre for rescheduling.</p>
                </div>
            @else
            <div class="mb-4" style="background:#f8fafc;border-radius:12px;padding:16px;">
                <div class="fw-semibold mb-2" style="color:var(--navy);"><i class="fa-solid fa-circle-info me-2" style="color:var(--teal);"></i>For parents / guardians</div>
                <ul class="mb-0 ps-3" style="font-size:13px;color:var(--text-muted);">
                    <li class="mb-1">Arrive a few minutes early for your child’s assessment.</li>
                    <li class="mb-1">Bring any recent reports or documents if your therapist has requested them.</li>
                    <li class="mb-1">If you need to reschedule, please contact <strong>{{ $assessment->branch?->displayLabel() ?? 'the centre' }}</strong>@if($assessment->branch?->phone) at <strong>{{ $assessment->branch->phone }}</strong>@endif.</li>
                </ul>
            </div>
            @endif

            <a href="{{ route('child.assessments') }}" class="btn-outline-teal"><i class="fa-solid fa-arrow-left"></i> Back to assessments</a>
        </div>
    </div>
</div>
@endsection
