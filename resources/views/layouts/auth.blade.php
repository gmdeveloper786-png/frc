<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Login') | {{ $frc['organisation_name'] ?? 'Faizan Rehabilitation Centre' }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/frc.css') }}">
@stack('styles')
</head>
<body>
<div class="auth-wrapper">
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
