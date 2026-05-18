@extends('layouts.auth')
@section('title', 'Reset Password — FRC')

@section('content')
<div class="auth-card auth-card--single" style="max-width:500px;">
    <div class="auth-right auth-right--centered" style="padding:48px 40px 44px;">
        <div class="auth-form-header">
            <div class="auth-status-icon" style="width:60px;height:60px;background:var(--teal-light);color:var(--teal);border:none;margin-bottom:16px;">
                <i class="fa-solid fa-lock" style="font-size:24px;"></i>
            </div>
            <h3>Reset Password</h3>
            <p class="auth-subtitle" style="margin-bottom:0;">Enter your new password below.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="form-frc" style="width:100%;text-align:left;">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email">Email Address <span style="color:var(--danger)">*</span></label>
                <input type="email" id="email" name="email" value="{{ $email ?? old('email') }}"
                    class="form-control @error('email') is-invalid @enderror" readonly>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password">New Password <span style="color:var(--danger)">*</span></label>
                <input type="password" id="password" name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Min 8 characters" autofocus>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password_confirmation">Confirm Password <span style="color:var(--danger)">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    class="form-control" placeholder="Repeat new password">
            </div>

            <button type="submit" class="btn-teal" style="width:100%;padding:12px;justify-content:center;">
                <i class="fa-solid fa-check"></i> Reset Password
            </button>
        </form>
    </div>
</div>
@endsection
