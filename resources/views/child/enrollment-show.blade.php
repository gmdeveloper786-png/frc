@extends('layouts.app')
@section('title', 'Enrollment details')
@section('page-title', 'Enrollment details')

@section('content')
<div class="child-enrollment-detail-page">
    <a href="{{ route('child.enrollment') }}" class="child-enrollment-detail-page__back">
        <i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> Back to My Enrollment
    </a>

    @include('child.partials.enrollment-detail-card', ['row' => $row])
</div>
@endsection
