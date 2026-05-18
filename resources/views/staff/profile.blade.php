@extends('layouts.app')
@section('title', 'My profile')
@section('page-title', 'My profile')

@section('content')
@php
    $roleLabel = $user->role?->display_name ?? $user->role?->name ?? '—';
    $statusBadge = match ($user->status) {
        'active' => 'approved',
        'approved' => 'approved',
        'pending' => 'pending',
        'inactive' => 'draft',
        default => $user->status ?? 'draft',
    };
@endphp

<div class="staff-profile-page">
    <div class="row g-3 staff-profile-page__grid">
        <div class="col-12 col-lg-7">
            <article class="card-frc staff-profile-card h-100">
                <div class="staff-profile-card__body">
                    <header class="staff-profile-hero">
                        <div class="staff-profile-avatar" aria-hidden="true">
                            {{ strtoupper(substr($user->full_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="staff-profile-hero-text">
                            <h2 class="staff-profile-name">{{ $user->full_name }}</h2>
                            <p class="staff-profile-email mb-0">{{ $user->email }}</p>
                            <div class="staff-profile-badges">
                                <span class="badge-status badge-publish staff-profile-role-badge">{{ $roleLabel }}</span>
                                <span class="badge-status badge-{{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $user->status ?? '')) }}</span>
                            </div>
                        </div>
                    </header>

                    <hr class="staff-profile-divider">

                    <h3 class="staff-profile-section-title">
                        <i class="fa-regular fa-id-card" aria-hidden="true"></i> Profile details
                    </h3>

                    <div class="frc-profile-detail-grid staff-profile-detail-grid">
                        <div class="frc-profile-detail-item">
                            <div class="frc-profile-detail-label">Full name</div>
                            <div class="frc-profile-detail-value">{{ $user->full_name }}</div>
                        </div>
                        <div class="frc-profile-detail-item">
                            <div class="frc-profile-detail-label">Father name</div>
                            <div class="frc-profile-detail-value">{{ $user->father_name ?: '—' }}</div>
                        </div>
                        <div class="frc-profile-detail-item">
                            <div class="frc-profile-detail-label">Gender</div>
                            <div class="frc-profile-detail-value">{{ $user->gender ? ucfirst($user->gender) : '—' }}</div>
                        </div>
                        <div class="frc-profile-detail-item">
                            <div class="frc-profile-detail-label">Date of birth</div>
                            <div class="frc-profile-detail-value">{{ $user->date_of_birth?->format('d M Y') ?? '—' }}</div>
                        </div>
                        <div class="frc-profile-detail-item">
                            <div class="frc-profile-detail-label">Email</div>
                            <div class="frc-profile-detail-value staff-profile-break-all">{{ $user->email }}</div>
                        </div>
                        <div class="frc-profile-detail-item">
                            <div class="frc-profile-detail-label">Phone</div>
                            <div class="frc-profile-detail-value">
                                @if($user->phone_number)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $user->phone_number) }}" class="staff-profile-contact-link">{{ $user->phone_number }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="frc-profile-detail-item">
                            <div class="frc-profile-detail-label">WhatsApp</div>
                            <div class="frc-profile-detail-value">
                                @if($user->whatsapp_number)
                                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $user->whatsapp_number) }}" class="staff-profile-contact-link" target="_blank" rel="noopener noreferrer">{{ $user->whatsapp_number }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="frc-profile-detail-item frc-profile-detail-item--full">
                            <div class="frc-profile-detail-label">Address</div>
                            <div class="frc-profile-detail-value">{{ $user->address ?: '—' }}</div>
                        </div>
                    </div>

                    <p class="staff-profile-footnote">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Profile details are maintained by administration. You can update your password on the right.
                    </p>
                </div>
            </article>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card-frc staff-profile-password-card h-100">
                <div class="card-header-frc staff-profile-password-card__header">
                    <h3 class="card-title-frc mb-0">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i> Change password
                    </h3>
                </div>
                <form action="{{ $passwordUrl }}" method="post" class="form-frc staff-profile-password-form">
                    @csrf
                    @method('PUT')

                    <div class="staff-profile-field">
                        <label for="staff-current-password">Current password <span class="staff-profile-required">*</span></label>
                        <input id="staff-current-password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="staff-profile-field">
                        <label for="staff-new-password">New password <span class="staff-profile-required">*</span></label>
                        <input id="staff-new-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password" minlength="8">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <p class="staff-profile-hint mb-0">Minimum 8 characters.</p>
                    </div>

                    <div class="staff-profile-field">
                        <label for="staff-confirm-password">Confirm new password <span class="staff-profile-required">*</span></label>
                        <input id="staff-confirm-password" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password" minlength="8">
                    </div>

                    <div class="staff-profile-form-actions">
                        <button type="submit" class="btn-teal staff-profile-btn">
                            <i class="fa-solid fa-key" aria-hidden="true"></i> Update password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
