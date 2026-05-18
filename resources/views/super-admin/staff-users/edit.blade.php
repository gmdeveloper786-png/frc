@extends('layouts.app')
@section('title', 'Edit Staff User')
@section('page-title', 'Edit Staff User')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger border-0 mb-3" style="border-radius:10px;">
        <ul class="mb-0 ps-3 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-frc">
            <div class="card-header-frc">
                <h6 class="card-title-frc mb-0">{{ $user->full_name }}</h6>
            </div>
            <form action="{{ route('super-admin.staff-users.update', $user) }}" method="POST" class="form-frc p-3 p-md-4">
                @csrf
                @method('PUT')
                @include('super-admin.staff-users._form', ['user' => $user])
                <div class="frc-form-actions">
                    <button type="submit" class="btn-teal">Save changes</button>
                    <a href="{{ route('super-admin.staff-users.index') }}" class="btn-outline-teal">Back to list</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
