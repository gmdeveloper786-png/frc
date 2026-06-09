@extends('layouts.app')
@section('title', $child->full_name)
@section('page-title', 'Child profile')

@section('content')
@php
    $primaryEnrollment = $child->enrollments->first();
    $enrollmentBadge = match (true) {
        $primaryEnrollment && in_array($primaryEnrollment->status, ['approved', 'active'], true) => 'approved',
        $primaryEnrollment && str_contains(strtolower((string) $primaryEnrollment->status), 'pending') => 'pending',
        default => 'draft',
    };
@endphp

<div class="row g-3 therapist-child-show-page child-show-page">
    <div class="col-12">
        <div class="card-frc child-show-profile-card">
            <div class="child-show-profile-header">
                <div class="child-show-profile-hero">
                    <div class="child-show-avatar" aria-hidden="true">
                        {{ strtoupper(substr($child->full_name ?? '—', 0, 1)) }}
                    </div>
                    <h5 class="child-show-name mb-2">{{ $child->full_name }}</h5>
                    @if($primaryEnrollment)
                        <span class="badge-status badge-{{ $enrollmentBadge }}" style="font-size:11px;">
                            {{ ucfirst(str_replace('_', ' ', $primaryEnrollment->status)) }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('therapist.children.index') }}" class="btn-outline-teal btn-view-all child-show-edit-btn text-decoration-none">
                    <i class="fa-solid fa-arrow-left"></i> Back to list
                </a>
            </div>

            @if($child->phone_number || $child->whatsapp_number)
                <div class="therapist-child-contact-row">
                    @if($child->phone_number)
                        <a href="tel:{{ preg_replace('/\s+/', '', $child->phone_number) }}" class="therapist-child-contact-link">
                            <span class="therapist-child-contact-icon"><i class="fa-solid fa-phone"></i></span>
                            <span>
                                <span class="therapist-child-contact-label">Phone</span>
                                <span class="therapist-child-contact-value">{{ $child->phone_number }}</span>
                            </span>
                        </a>
                    @endif
                    @if($child->whatsapp_number)
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $child->whatsapp_number) }}" class="therapist-child-contact-link" target="_blank" rel="noopener noreferrer">
                            <span class="therapist-child-contact-icon therapist-child-contact-icon--wa"><i class="fa-brands fa-whatsapp"></i></span>
                            <span>
                                <span class="therapist-child-contact-label">WhatsApp</span>
                                <span class="therapist-child-contact-value">{{ $child->whatsapp_number }}</span>
                            </span>
                        </a>
                    @endif
                </div>
            @endif

            <hr class="child-show-divider">

            <div class="frc-profile-detail-grid">
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">GR number</div>
                    <div class="frc-profile-detail-value" style="font-family:monospace;">{{ $child->gr_number ?? '—' }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Father name</div>
                    <div class="frc-profile-detail-value">{{ $child->father_name ?: '—' }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Date of birth</div>
                    <div class="frc-profile-detail-value">{{ $child->date_of_birth?->format('d M Y') ?? '—' }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Age</div>
                    <div class="frc-profile-detail-value">{{ $child->age ? $child->age.' years' : '—' }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Gender</div>
                    <div class="frc-profile-detail-value">{{ $child->gender ? ucfirst($child->gender) : '—' }}</div>
                </div>
                <div class="frc-profile-detail-item frc-profile-detail-item--full">
                    <div class="frc-profile-detail-label">Address</div>
                    <div class="frc-profile-detail-value">{{ $child->address ?: '—' }}</div>
                </div>
            </div>

            @if($child->disabilities->isNotEmpty())
                <hr class="child-show-divider">
                <div class="child-show-section-title">Disabilities</div>
                <div class="child-show-tag-list">
                    @foreach($child->disabilities as $disability)
                        <span class="child-show-tag">{{ $child->disabilityLabel($disability) }}</span>
                    @endforeach
                </div>
            @endif

            @if($child->parent_notes)
                <hr class="child-show-divider">
                <div class="child-show-section-title">Parent notes</div>
                <p class="child-show-notes therapist-child-parent-notes">{{ $child->parent_notes }}</p>
            @endif

            <hr class="child-show-divider">
            <div class="child-show-section-title">Assigned services (your enrollments)</div>
            @if($child->enrollments->isEmpty())
                <p class="text-muted small mb-0">—</p>
            @else
                <div class="therapist-child-enrollment-list">
                    @foreach($child->enrollments as $enrollment)
                        @php
                            $statusKey = match (true) {
                                in_array($enrollment->status, ['approved', 'active'], true) => 'approved',
                                str_contains(strtolower((string) $enrollment->status), 'pending') => 'pending',
                                str_contains(strtolower((string) $enrollment->status), 'cancel') => 'cancelled',
                                default => 'draft',
                            };
                        @endphp
                        <div class="therapist-child-enrollment-card">
                            <div class="therapist-child-enrollment-card__main">
                                <span class="therapist-child-enrollment-card__service">{{ $enrollment->service?->name ?? '—' }}</span>
                                <span class="therapist-child-enrollment-card__branch">{{ $enrollment->branch?->name ?? '—' }}</span>
                            </div>
                            <span class="badge-status badge-{{ $statusKey }}" style="font-size:10px;flex-shrink:0;">
                                {{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
