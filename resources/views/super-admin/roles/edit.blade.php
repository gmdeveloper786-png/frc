@extends('layouts.app')
@section('title', 'Edit Role Permissions')
@section('page-title', 'Edit Role Permissions')

@push('styles')
<style>
.role-perm-module {
    border: 1px solid var(--border-soft, #e5eaf0);
    border-radius: 10px;
    margin-bottom: 1rem;
    overflow: hidden;
}
.role-perm-module-header {
    background: #f6f9fc;
    padding: 0.65rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border-bottom: 1px solid var(--border-soft, #e5eaf0);
}
.role-perm-module-header h6 {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: var(--navy, #11517c);
    text-transform: capitalize;
}
.role-perm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.5rem 1rem;
    padding: 1rem;
}
.role-perm-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 13px;
}
.role-perm-item input {
    margin-top: 3px;
    accent-color: var(--teal, #008080);
}
.role-perm-item label {
    cursor: pointer;
    margin: 0;
    line-height: 1.35;
}
.role-perm-item .perm-name {
    display: block;
    font-size: 11px;
    color: #6b7c8f;
    font-family: ui-monospace, monospace;
}
.role-perm-toggle-module {
    background: none;
    border: none;
    padding: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--teal);
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    flex-shrink: 0;
}
.role-perm-toggle-module:hover {
    color: var(--teal-dark);
    text-decoration: underline;
}
</style>
@endpush

@section('content')
<div class="card-frc mb-3">
    <div class="card-header-frc d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="card-title-frc mb-0">
            <i class="fa-solid fa-shield-halved me-2" style="color:var(--teal);"></i>{{ $role->display_name }}
        </h6>
        <a href="{{ route('super-admin.roles.index') }}" class="btn-outline-teal" style="font-size:12px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to roles
        </a>
    </div>

    @if(!$editable)
        <div class="alert alert-info border-0 m-3 mb-0" style="border-radius:10px;background:#e8f4fc;color:#11517c;">
            <i class="fa-solid fa-circle-info me-1"></i>
            Super Admin has every permission automatically. These cannot be changed from this screen.
        </div>
    @endif

    <form
        action="{{ $editable ? route('super-admin.roles.update', $role) : '#' }}"
        method="POST"
        class="p-md-4"
        id="rolePermissionsForm"
    >
        @csrf
        @if($editable)
            @method('PUT')
        @endif

        @foreach($permissionsByModule as $module => $permissions)
            <div class="role-perm-module" data-module="{{ $module }}">
                <div class="role-perm-module-header">
                    <h6>{{ str_replace('_', ' ', $module) }}</h6>
                    @if($editable)
                        <button type="button" class="role-perm-toggle-module" data-module="{{ $module }}">
                            Select all
                        </button>
                    @endif
                </div>
                <div class="role-perm-grid">
                    @foreach($permissions as $permission)
                        <div class="role-perm-item">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                id="perm_{{ $permission->id }}"
                                value="{{ $permission->id }}"
                                @checked(in_array($permission->id, $assignedIds, true))
                                @disabled(!$editable)
                            >
                            <label for="perm_{{ $permission->id }}">
                                {{ $permission->display_name }}
                                <span class="perm-name">{{ $permission->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($editable)
            <div class="d-flex flex-wrap gap-2 pt-2 border-top" style="border-color:var(--border-soft)!important;">
                <button type="submit" class="btn-teal">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save permissions
                </button>
                <button type="button" class="btn-outline-teal" id="selectAllPermissions">Select all</button>
                <button type="button" class="btn-outline-teal" id="clearAllPermissions">Clear all</button>
                <a href="{{ route('super-admin.roles.index') }}" class="btn-outline-teal ms-auto">Cancel</a>
            </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
@if($editable)
<script>
(function () {
    const form = document.getElementById('rolePermissionsForm');
    if (!form) return;

    function setModule(module, checked) {
        form.querySelectorAll('.role-perm-module[data-module="' + module + '"] input[type="checkbox"]')
            .forEach(function (cb) { cb.checked = checked; });
    }

    document.querySelectorAll('.role-perm-toggle-module').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const module = btn.getAttribute('data-module');
            const boxes = form.querySelectorAll('.role-perm-module[data-module="' + module + '"] input[type="checkbox"]');
            const allChecked = Array.from(boxes).every(function (cb) { return cb.checked; });
            setModule(module, !allChecked);
            btn.textContent = allChecked ? 'Select all' : 'Clear all';
        });
    });

    document.getElementById('selectAllPermissions')?.addEventListener('click', function () {
        form.querySelectorAll('input[type="checkbox"][name="permissions[]"]').forEach(function (cb) {
            cb.checked = true;
        });
    });

    document.getElementById('clearAllPermissions')?.addEventListener('click', function () {
        form.querySelectorAll('input[type="checkbox"][name="permissions[]"]').forEach(function (cb) {
            cb.checked = false;
        });
    });
})();
</script>
@endif
@endpush
