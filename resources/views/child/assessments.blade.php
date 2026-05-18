@extends('layouts.app')
@section('title', 'My Assessments')
@section('page-title', 'My Assessments')

@section('content')
@if($assessments->isEmpty())
    <div class="empty-state">
        <i class="fa-solid fa-calendar-days empty-icon"></i>
        <h5>No assessments yet</h5>
        <p class="text-muted">When your assessments are scheduled and published, they will appear here.</p>
    </div>
@else
    <div class="row g-3">
        @foreach($assessments as $a)
            <div class="col-md-6 col-xl-4">
                <div class="card-frc h-100" style="border-radius:14px;display:flex;flex-direction:column;">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:48px;height:48px;background:var(--teal-light);border-radius:14px;display:flex;align-items:center;justify-content:center;">
                                <i class="fa-solid fa-calendar-check" style="color:var(--teal);font-size:18px;"></i>
                            </div>
                            <div>
                                <div style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--navy);">{{ $a->date?->format('d M Y') }}</div>
                                <div style="font-size:13px;color:var(--text-muted);">{{ $a->day }} · {{ \Carbon\Carbon::parse($a->time)->format('h:i A') }}</div>
                            </div>
                        </div>
                        <span class="badge-status badge-{{ $a->status === 'cancelled' ? 'cancelled' : $a->status }}" style="flex-shrink:0;">{{ $a->status === 'cancelled' ? 'Cancelled' : ucfirst($a->status) }}</span>
                    </div>
                    <table class="w-100 mb-3" style="font-size:13px;">
                        <tr><td class="text-muted py-1" style="width:38%;">Branch</td><td class="fw-medium">{{ $a->branch?->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted py-1">Therapist</td><td>{{ $a->therapist?->full_name ?? '—' }}</td></tr>
                    </table>
                    @if($a->status === 'cancelled')
                        <div class="mb-3 p-3 rounded-3" style="background:#fff5f5;border:1px solid #fecaca;font-size:13px;color:var(--text-dark);">
                            Your assessment has been cancelled. Please contact the centre for rescheduling.
                        </div>
                    @endif
                    <div class="mt-auto pt-2">
                        <a href="{{ route('child.assessments.show', $a) }}" class="btn-teal w-100 justify-content-center" style="border-radius:10px;">
                            <i class="fa-solid fa-eye"></i> View details
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
