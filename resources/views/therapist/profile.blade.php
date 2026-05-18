@extends('layouts.app')
@section('title', 'My profile')
@section('page-title', 'My profile')

@section('content')
@php
    $tp = $user->therapistProfile;
    $workingDayList = collect();
    if ($tp && is_array($tp->working_days)) {
        $workingDayList = collect($tp->working_days)->filter(fn ($d) => is_string($d) && trim($d) !== '')->values();
    }
    $breakLabel = $tp?->formattedBreakTimeLabel();
    $slotPills = collect();
    if ($tp && is_array($tp->available_time_slots)) {
        foreach ($tp->available_time_slots as $s) {
            if (is_string($s) && $s !== '') {
                $slotPills->push(['label' => $s, 'disabled' => false]);
                continue;
            }
            if (is_array($s)) {
                $label = trim((string) ($s['slot'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $disabled = filter_var($s['disabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $slotPills->push(['label' => $label, 'disabled' => $disabled]);
            }
        }
    }
@endphp

<div class="row g-3 therapist-profile-page therapist-show-page child-show-page">
    <div class="col-12 col-lg-7">
        <div class="card-frc therapist-show-profile-card h-100">
            <div class="card-header-frc pb-0 border-0">
                <h6 class="card-title-frc mb-0">Therapist profile</h6>
            </div>

            <div class="therapist-show-hero therapist-profile-hero">
                <div class="therapist-show-avatar" aria-hidden="true">
                    {{ strtoupper(substr($user->full_name ?? 'T', 0, 1)) }}
                </div>
                <h5 class="therapist-show-name mb-2">{{ $user->full_name }}</h5>
                <span class="badge-status badge-{{ ($tp?->status ?? 'active') === 'active' ? 'active' : 'draft' }}">
                    {{ ucfirst($tp?->status ?? 'active') }}
                </span>
            </div>

            <hr class="therapist-show-divider">

            <div class="frc-profile-detail-grid therapist-profile-detail-grid">
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Email</div>
                    <div class="frc-profile-detail-value therapist-show-break-all">{{ $user->email }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Phone</div>
                    <div class="frc-profile-detail-value">
                        @if($user->phone_number)
                            <a href="tel:{{ preg_replace('/\s+/', '', $user->phone_number) }}" class="therapist-profile-contact-link">{{ $user->phone_number }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">WhatsApp</div>
                    <div class="frc-profile-detail-value">
                        @if($user->whatsapp_number)
                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $user->whatsapp_number) }}" class="therapist-profile-contact-link" target="_blank" rel="noopener noreferrer">{{ $user->whatsapp_number }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Branch</div>
                    <div class="frc-profile-detail-value">{{ $tp?->branch?->name ?? '—' }}</div>
                </div>
                <div class="frc-profile-detail-item frc-profile-detail-item--full">
                    <div class="frc-profile-detail-label">Qualification</div>
                    <div class="frc-profile-detail-value">{{ $tp?->qualification ?: '—' }}</div>
                </div>
            </div>

            <hr class="therapist-show-divider">

            <div class="therapist-profile-section">
                <div class="therapist-show-section-title">Working days</div>
                @if($workingDayList->isEmpty())
                    <p class="text-muted small mb-0">—</p>
                @else
                    <div class="therapist-show-tags therapist-show-tags--left">
                        @foreach($workingDayList as $day)
                            <span class="therapist-show-tag">{{ $day }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($breakLabel)
                <div class="therapist-profile-section">
                    <div class="therapist-show-section-title">Break time</div>
                    <span class="therapist-show-tag therapist-show-tag--break">{{ $breakLabel }}</span>
                </div>
            @endif

            <div class="therapist-profile-section therapist-show-slots-card">
                <div class="therapist-show-section-title mb-3">Available time slots</div>
                @if($slotPills->isEmpty())
                    <p class="therapist-show-slots-empty mb-0">—</p>
                @else
                    <div class="therapist-show-slots">
                        @foreach($slotPills as $pill)
                            <span class="therapist-show-slot{{ $pill['disabled'] ? ' therapist-show-slot--break' : '' }}">
                                {{ $pill['label'] }}
                                @if($pill['disabled'])
                                    <span class="therapist-show-slot-note">Break</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="therapist-profile-section">
                <div class="therapist-show-section-title">Services you deliver</div>
                <div class="child-show-tag-list">
                    @forelse($user->therapistServices as $service)
                        <span class="child-show-tag">{{ $service->name }}</span>
                    @empty
                        <span class="text-muted small">—</span>
                    @endforelse
                </div>
            </div>

            @if($tp && is_array($tp->documents) && count($tp->documents) > 0)
                <div class="therapist-profile-section">
                    <div class="therapist-show-section-title">Documents</div>
                    <ul class="mb-0 ps-3 small">
                        @foreach($tp->documents as $doc)
                            @if(is_string($doc))
                                <li class="mb-1"><a href="{{ asset('storage/'.$doc) }}" target="_blank" rel="noopener">{{ basename($doc) }}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="small text-muted mb-0 mt-3 pt-3 border-top" style="border-color:var(--border-soft)!important;">
                Profile details are maintained by administration. You can update your password below.
            </p>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card-frc therapist-profile-password-card">
            <div class="card-header-frc">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-lock me-2" style="color:var(--teal);"></i>Change password</h6>
            </div>
            <form action="{{ route('therapist.profile.password') }}" method="post" class="form-frc p-3">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Current password</label>
                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <input type="password" name="password" class="form-control" required autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm new password</label>
                    <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn-teal">Update password</button>
            </form>
        </div>
    </div>
</div>
@endsection
