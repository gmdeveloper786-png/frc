@extends('layouts.app')
@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="child-profile-page">
    <div class="row g-3 child-profile-page__grid">
        <div class="col-12 col-lg-7">
            <div class="card-frc child-profile-card h-100">
                <form action="{{ route('child.profile.update') }}" method="POST" class="form-frc child-profile-form">
                    @csrf
                    @method('PUT')

                    <div class="child-profile-hero">
                        <div class="child-profile-avatar" aria-hidden="true">
                            {{ strtoupper(substr($user->full_name ?? 'C', 0, 1)) }}
                        </div>
                        <div class="child-profile-hero-text">
                            <h2 class="child-profile-display-name">{{ $user->full_name ?? '—' }}</h2>
                            @if($user->gr_number)
                                <p class="child-profile-gr mb-1" style="font-family:monospace;font-size:14px;color:var(--navy);">GR No. {{ $user->gr_number ?? '—' }}</p>
                            @endif
                            <p class="child-profile-email mb-0">{{ $user->email ?? '—' }}</p>
                            @if($user->status)
                                <span class="badge-status badge-{{ $user->status }} child-profile-status">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $user->status)) }}</span>
                            @endif
                        </div>
                    </div>

                    <hr class="child-profile-divider">

                    <h3 class="child-profile-section-title">
                        <i class="fa-regular fa-user" aria-hidden="true"></i> Profile details
                    </h3>

                    <div class="child-profile-fields">
                        <div class="child-profile-field">
                            <label for="child-profile-name-input">Full name <span class="child-profile-required">*</span></label>
                            <input id="child-profile-name-input" type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="form-control @error('full_name') is-invalid @enderror" autocomplete="name">
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="child-profile-field">
                            <label for="child-profile-father-name">Father's name</label>
                            <input id="child-profile-father-name" type="text" name="father_name" value="{{ old('father_name', $user->father_name) }}" class="form-control @error('father_name') is-invalid @enderror" placeholder="Father's name" autocomplete="off">
                            @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="child-profile-field">
                            <label for="child-profile-phone">Phone</label>
                            <input id="child-profile-phone" type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="form-control @error('phone_number') is-invalid @enderror" placeholder="e.g. 03121234567" autocomplete="tel">
                            @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="child-profile-field">
                            <label for="child-profile-whatsapp">WhatsApp</label>
                            <input id="child-profile-whatsapp" type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" class="form-control @error('whatsapp_number') is-invalid @enderror" placeholder="e.g. 03121234567" autocomplete="tel">
                            @error('whatsapp_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="child-profile-field child-profile-field--readonly">
                            <label>Email</label>
                            <input type="email" value="{{ $user->email }}" class="form-control child-profile-input--readonly" readonly tabindex="-1" aria-readonly="true">
                        </div>

                        <div class="child-profile-field child-profile-field--readonly">
                            <label>Gender</label>
                            <input type="text" value="{{ $user->gender ? ucfirst($user->gender) : '—' }}" class="form-control child-profile-input--readonly" readonly tabindex="-1" aria-readonly="true">
                        </div>

                        <div class="child-profile-field child-profile-field--readonly">
                            <label>Date of birth</label>
                            <input type="text" value="{{ $user->date_of_birth?->format('d M Y') ?? '—' }}" class="form-control child-profile-input--readonly" readonly tabindex="-1" aria-readonly="true">
                        </div>

                        <div class="child-profile-field child-profile-field--readonly">
                            <label>Age</label>
                            <input type="text" value="{{ $user->age !== null ? $user->age . ' years' : '—' }}" class="form-control child-profile-input--readonly" readonly tabindex="-1" aria-readonly="true">
                        </div>

                        <div class="child-profile-field child-profile-field--readonly">
                            <label>Branch</label>
                            <input type="text" value="{{ $user->branch?->displayLabel() ?? '—' }}" class="form-control child-profile-input--readonly" readonly tabindex="-1" aria-readonly="true">
                        </div>

                        <div class="child-profile-field child-profile-field--full">
                            <label for="child-profile-address">Address</label>
                            <textarea id="child-profile-address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" placeholder="Home address">{{ old('address', $user->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="child-profile-field child-profile-field--readonly child-profile-field--full">
                            <label>Present Complaints</label>
                            @if($user->disabilities->isEmpty())
                                <p class="child-profile-readonly-value text-muted mb-0">—</p>
                            @else
                                <div class="child-show-tag-list child-profile-tag-list">
                                    @foreach($user->disabilities as $disability)
                                        <span class="child-show-tag">{{ $user->disabilityLabel($disability) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <p class="child-profile-hint child-profile-field--full mb-0">Fields marked as read-only can only be updated by administration.</p>
                    </div>

                    <div class="child-profile-form-actions">
                        <button type="submit" class="btn-teal child-profile-btn">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card-frc child-profile-password-card h-100">
                <div class="card-header-frc child-profile-password-card__header">
                    <h3 class="card-title-frc mb-0">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i> Change password
                    </h3>
                </div>
                <form action="{{ route('child.profile.password') }}" method="POST" class="form-frc child-profile-password-form">
                    @csrf
                    @method('PUT')

                    <div class="child-profile-field">
                        <label for="child-current-password">Current password <span class="child-profile-required">*</span></label>
                        <div class="auth-pass-wrap">
                            <input id="child-current-password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                            <button type="button" class="auth-pass-toggle" data-pass-toggle="child-current-password" data-pass-icon="childCurrentPassIcon" aria-label="Show password">
                                <i class="fa-regular fa-eye" id="childCurrentPassIcon"></i>
                            </button>
                        </div>
                        @error('current_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="child-profile-field">
                        <label for="child-new-password">New password <span class="child-profile-required">*</span></label>
                        <div class="auth-pass-wrap">
                            <input id="child-new-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                            <button type="button" class="auth-pass-toggle" data-pass-toggle="child-new-password" data-pass-icon="childNewPassIcon" aria-label="Show password">
                                <i class="fa-regular fa-eye" id="childNewPassIcon"></i>
                            </button>
                        </div>
                        @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="child-profile-field">
                        <label for="child-confirm-password">Confirm new password <span class="child-profile-required">*</span></label>
                        <div class="auth-pass-wrap">
                            <input id="child-confirm-password" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                            <button type="button" class="auth-pass-toggle" data-pass-toggle="child-confirm-password" data-pass-icon="childConfirmPassIcon" aria-label="Show password">
                                <i class="fa-regular fa-eye" id="childConfirmPassIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="child-profile-form-actions">
                        <button type="submit" class="btn-teal child-profile-btn">
                            <i class="fa-solid fa-key" aria-hidden="true"></i> Update password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
