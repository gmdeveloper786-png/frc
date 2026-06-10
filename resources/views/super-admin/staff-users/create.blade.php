@extends('layouts.app')
@section('title', 'Add Staff User')
@section('page-title', 'Add Staff User')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-frc">
            <div class="card-header-frc">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-user-plus me-2" style="color:var(--teal);"></i>New staff user</h6>
            </div>
            <form action="{{ route('super-admin.staff-users.store') }}" method="POST" class="form-frc">
                @csrf
                @include('super-admin.staff-users._form')
                <div class="frc-form-actions">
                    <button type="submit" class="btn-teal"><i class="fa-solid fa-plus me-1"></i>Create user</button>
                    <a href="{{ route('super-admin.staff-users.index') }}" class="btn-outline-teal"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
