@extends('layouts.app')
@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')

@section('content')
<div class="card-frc">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0">
            <i class="fa-solid fa-shield-halved me-2" style="color:var(--teal);"></i>Role permissions
        </h6>
    </div>
    <p class="px-3 pt-3 mb-0 small text-muted">
        Assign what each role can do in the system. Changes apply immediately for all users with that role.
        Super Admin always has full access and cannot be edited here.
    </p>
    <div class="table-responsive p-3">
        <table class="table table-frc mb-0">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Permissions assigned</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>
                            <strong>{{ $role->display_name }}</strong>
                            <div class="small text-muted">{{ $role->name }}</div>
                        </td>
                        <td>
                            @if($role->name === 'super_admin')
                                <span class="badge-status badge-approved">All permissions</span>
                            @else
                                {{ $role->permissions_count }} permission{{ $role->permissions_count === 1 ? '' : 's' }}
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('super-admin.roles.edit', $role) }}" class="btn-outline-teal" style="font-size:12px;padding:5px 12px;">
                                <i class="fa-solid fa-{{ $role->name === 'super_admin' ? 'eye' : 'pen' }} me-1"></i>
                                {{ $role->name === 'super_admin' ? 'View' : 'Edit' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
