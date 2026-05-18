@extends('layouts.auth')
@section('title', 'Login')

@section('content')
<div class="auth-card">
    {{-- Left Panel --}}
    <div class="auth-left">
        <div class="auth-logo">
            <i class="fa-solid fa-hands-holding-child"></i>
        </div>
        <h2>{{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}</h2>
        <p class="auth-tagline">"Care, Support, Growth & Hope"</p>
        <div class="auth-pills">
            <span class="auth-pill"><i class="fa-solid fa-star me-1"></i> Speech Therapy</span>
            <span class="auth-pill"><i class="fa-solid fa-heart me-1"></i> Occupational Therapy</span>
            <span class="auth-pill"><i class="fa-solid fa-leaf me-1"></i> Behavioral Therapy</span>
            <span class="auth-pill"><i class="fa-solid fa-puzzle-piece me-1"></i> Special Education</span>
        </div>
        {{-- Soft decorative doodles --}}
        <div style="position:absolute;bottom:24px;left:0;right:0;text-align:center;color:rgba(255,255,255,.3);font-size:22px;">
            ✦ &nbsp; ❤ &nbsp; ✿ &nbsp; ✦
        </div>
    </div>

    {{-- Right Panel --}}
    <div class="auth-right">
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
                <div style="position:relative;">
                    <input type="password" id="password" name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                        <i class="fa-regular fa-eye" id="passIcon"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;font-size:14px;">
                    <input type="checkbox" name="remember" style="width:15px;height:15px;accent-color:var(--teal);">
                    Remember me
                </label>
                <a href="{{ route('password.request') }}" style="color:var(--teal);font-size:13px;font-weight:500;">Forgot password?</a>
            </div>

            <button type="submit" class="btn-teal" style="width:100%;padding:12px;font-size:15px;justify-content:center;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        @if($frc['child_registration_enabled'] ?? true)
            <div style="text-align:center;margin-top:20px;font-size:14px;color:var(--text-muted);">
                New patient? <a href="{{ route('register') }}" style="color:var(--teal);font-weight:600;">Register here</a>
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
