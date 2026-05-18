@extends('layouts.auth')
@section('title', 'Forgot Password — FRC')

@section('content')
<div class="auth-card" style="max-width:500px;">
    <div class="auth-right" style="padding:48px 40px;">
        <div style="text-align:center;margin-bottom:28px;">
            <div style="width:60px;height:60px;background:var(--teal-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fa-solid fa-key" style="font-size:24px;color:var(--teal);"></i>
            </div>
            <h3>Forgot Password?</h3>
            <p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>
        </div>

        @if(session('status'))
            <div class="alert-frc success">
                <i class="fa-solid fa-circle-check"></i>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="form-frc">
            @csrf
            <div class="mb-3">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="you@example.com" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn-teal" style="width:100%;padding:12px;justify-content:center;">
                <i class="fa-solid fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div style="text-align:center;margin-top:20px;font-size:14px;">
            <a href="{{ route('login') }}" style="color:var(--teal);font-weight:500;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>
</div>
@endsection
