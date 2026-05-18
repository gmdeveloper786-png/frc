@extends('layouts.auth')
@section('title', 'Registration closed')

@section('content')
<div class="auth-card auth-card--single">
    <div class="auth-right auth-right--centered">
        <div class="auth-status-icon auth-status-icon--warning">
            <i class="fa-solid fa-user-lock"></i>
        </div>

        <h3>Registration closed</h3>
        <p class="auth-subtitle" style="margin-bottom:24px;">
            New child self-registration is temporarily disabled. Please contact
            <strong style="color:var(--navy);">{{ $frc['organisation_name'] }}</strong>
            for assistance.
        </p>

        @if($frc['contact_phone'] || $frc['contact_email'])
            <div class="auth-contact-box">
                @if($frc['contact_phone'])
                    <div class="auth-contact-row">
                        <i class="fa-solid fa-phone"></i>
                        <span>{{ $frc['contact_phone'] }}</span>
                    </div>
                @endif
                @if($frc['contact_email'])
                    <div class="auth-contact-row">
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:{{ $frc['contact_email'] }}">{{ $frc['contact_email'] }}</a>
                    </div>
                @endif
            </div>
        @endif

        <a href="{{ route('login') }}" class="btn-teal" style="width:100%;padding:12px;justify-content:center;margin-top:8px;">
            <i class="fa-solid fa-arrow-left"></i> Back to login
        </a>
    </div>
</div>
@endsection
