@extends('layouts.auth')
@section('title', 'Login')

@section('content')
<div class="auth-card">
    {{-- Left Panel --}}
    <div class="auth-left">
        <div class="auth-logo">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}">
        </div>
        <h2>{{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}</h2>
        <p class="auth-tagline">"Care, Support, Growth & Hope"</p>
        <div class="auth-pills">
            <span class="auth-pill"><i class="fa-solid fa-star me-1"></i> Speech Therapy</span>
            <span class="auth-pill"><i class="fa-solid fa-heart me-1"></i> Occupational Therapy</span>
            <span class="auth-pill"><i class="fa-solid fa-leaf me-1"></i> Behavioral Therapy</span>
            <span class="auth-pill"><i class="fa-solid fa-puzzle-piece me-1"></i> Special Education</span>
        </div>
    </div>

    {{-- Right Panel --}}
    <div class="auth-right">
        <div class="auth-mobile-brand">
            <div class="auth-mobile-logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}">
            </div>
        </div>

        <h3>Welcome Back</h3>
        <p class="auth-subtitle">Sign in to your account to continue</p>

        @if(session('success'))
            <div class="alert-frc success">
                <i class="fa-solid fa-circle-check"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="form-frc">
            @csrf
            <div class="mb-3">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="you@example.com" autocomplete="email" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password">Password</label>
                <div class="auth-pass-wrap">
                    <input type="password" id="password" name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="auth-pass-toggle" onclick="togglePass()" aria-label="Show password">
                        <i class="fa-regular fa-eye" id="passIcon"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth-form-options">
                <label>
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="{{ route('password.request') }}" style="color:var(--teal);font-size:13px;font-weight:500;white-space:nowrap;">Forgot password?</a>
            </div>

            <button type="submit" class="btn-teal auth-btn-full">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        @if($frc['child_registration_enabled'] ?? true)
            <div class="auth-register-link">
                New patient? <a href="{{ route('register') }}">Register here</a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePass() {
    const el = document.getElementById('password');
    const icon = document.getElementById('passIcon');
    if (el.type === 'password') {
        el.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        el.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}
</script>
@endpush
