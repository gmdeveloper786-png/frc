@extends('layouts.app')
@section('title', $therapist->full_name)
@section('page-title', 'Therapist Profile')

@section('content')
<div class="row g-3 therapist-show-page">
    <div class="col-12 col-md-4">
        <div class="card-frc therapist-show-profile-card">
            <div class="therapist-show-hero">
                <div class="therapist-show-avatar">
                    {{ strtoupper(substr($therapist->full_name ?? '', 0, 1)) ?: '—' }}
                </div>
                <h5 class="therapist-show-name">{{ $therapist->full_name ?? '—' }}</h5>
                <span class="badge-status badge-{{ $therapist->therapistProfile?->status == 'active' ? 'active' : 'draft' ?? '—' }}">
                    {{ ucfirst($therapist->therapistProfile?->status ?? '—') }}
                </span>
                <div class="therapist-show-tags">
                    @forelse($therapist->therapistServices ?? [] as $svc)
                        <span class="therapist-show-tag">{{ $svc->name ?? '—' }}</span>
                    @empty
                        <span class="therapist-show-tag therapist-show-tag--muted">No services assigned</span>
                    @endforelse
                </div>
            </div>
            <hr class="therapist-show-divider">
            <table class="enrollment-detail-kv therapist-show-detail-kv">
                <tr><td>Email</td><td class="therapist-show-break-all">{{ $therapist->email ?? '—' }}</td></tr>
                <tr><td>Phone</td><td>{{ $therapist->phone_number ?? '—' }}</td></tr>
                <tr><td>WhatsApp</td><td>{{ $therapist->whatsapp_number ?? '—' }}</td></tr>
                <tr><td>Gender</td><td>{{ ucfirst($therapist->gender ?? '—') }}</td></tr>
                <tr><td>DOB</td><td>{{ $therapist->date_of_birth?->format('d M Y') ?? '—' }}</td></tr>
                <tr><td>Address</td><td>{{ $therapist->address ?? '—' }}</td></tr>
                <tr><td>CNIC</td><td>{{ $therapist->therapistProfile?->cnic_number ?? '—' }}</td></tr>
                <tr><td>Qualification</td><td>{{ $therapist->therapistProfile?->qualification ?? '—' }}</td></tr>
                <tr>
                    <td>Branch</td>
                    <td>{{ $therapist->therapistProfile?->branch?->name ?? '—' }}</td>
                </tr>
            </table>
            <hr class="therapist-show-divider">
            <div class="therapist-show-section-title">Working Days</div>
            <div class="therapist-show-tags therapist-show-tags--left">
                @foreach($therapist->therapistProfile?->working_days ?? [] as $day)
                    <span class="therapist-show-tag">{{ $day ?? '—' }}</span>
                @endforeach
            </div>
            @if($therapist->therapistProfile?->formattedBreakTimeLabel())
                <p class="therapist-show-break">
                    <span class="text-muted">Break:</span>
                    <strong>{{ $therapist->therapistProfile->formattedBreakTimeLabel() ?? '—' }}</strong>
                </p>
            @endif
            <div class="frc-form-actions therapist-show-actions">
                <a href="{{ route('therapists.edit', $therapist->id) }}" class="btn-teal"><i class="fa-solid fa-pen"></i> Edit</a>
                <a href="{{ route('therapists.index') }}" class="btn-outline-teal"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-8">
        <div class="card-frc therapist-show-slots-card">
            <div class="card-header-frc card-header-frc--stack-sm">
                <h6 class="card-title-frc mb-0">Available Time Slots</h6>
            </div>
            @php $displaySlots = $therapist->therapistProfile?->normalizedAvailableSlotsForDisplay() ?? []; @endphp
            @if($displaySlots === [])
                <p class="therapist-show-slots-empty">No slots configured.</p>
            @else
                <div class="therapist-show-slots">
                    @foreach($displaySlots as $slot)
                        <span class="therapist-show-slot{{ $slot['disabled'] ? ' therapist-show-slot--break' : '' }}">
                            {{ $slot['slot'] }}
                            @if($slot['disabled'])
                                <span class="therapist-show-slot-note">(Break)</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
