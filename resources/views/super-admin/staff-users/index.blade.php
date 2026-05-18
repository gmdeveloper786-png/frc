@extends('layouts.app')
@section('title', 'Staff Users')
@section('page-title', 'Staff Users')

@push('styles')
<style>
.staff-users-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.staff-users-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.staff-users-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.staff-users-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.staff-users-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.staff-users-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.staff-users-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.staff-users-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.staff-users-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.staff-users-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 mb-3" style="border-radius:10px;background:#e8f8ee;color:#1a7a3a;">{{ session('success') }}</div>
@endif
<div class="card-frc card-frc--list-page mb-3">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-user-tie me-2" style="color:var(--teal);"></i>Admin &amp; Finance users</h6>
        <a href="{{ route('super-admin.staff-users.create') }}" class="btn-teal" style="font-size:13px;white-space:nowrap;"><i class="fa-solid fa-plus"></i> Add staff user</a>
    </div>
    <form method="GET" class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, email, or phone" value="{{ request('search') }}">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Role</label>
                <select name="role" class="form-select">
                    <option value="">All roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    <option value="finance" @selected(request('role') === 'finance')>Finance</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-2 filter-actions">
                <button type="submit" class="btn-teal">Filter</button>
                <a href="{{ route('super-admin.staff-users.index') }}" class="btn-outline-teal">Reset</a>
            </div>
        </div>
    </form>

    @if($users->isEmpty())
        <div class="empty-state py-5"><p class="text-muted mb-0">No staff users match your filters.</p></div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $row)
                        <tr>
                            <td class="text-muted">{{ $users->firstItem() + $loop->index }}</td>
                            <td style="font-weight:500;color:var(--navy); white-space:nowrap;">{{ $row->full_name ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ $row->email ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ $row->phone_number ?? '—' }}</td>
                            <td><span class="badge-status badge-publish" style="text-transform:capitalize;">{{ $row->role?->name ?? '—' }}</span></td>
                            <td>
                                @if($row->status === 'active')
                                    <span class="badge-status badge-approved">Active</span></span>
                                @else
                                    <span class="badge-status badge-draft">Inactive</span></span>
                                @endif</span>
                            </td>
                            <td style="white-space:nowrap;">{{ $row->created_at?->format('d M Y') ?? '—' }}</td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('super-admin.staff-users.edit', $row) }}" class="btn-outline-teal" style="font-size:11px;padding:4px 8px;"><i class="fa-solid fa-pen"></i> Edit</a>
                                @if((int) $row->id !== (int) auth()->id())
                                    <button type="button" class="btn-outline-teal border-warning text-warning" style="font-size:11px;padding:4px 8px;"
                                        data-bs-toggle="modal" data-bs-target="#toggleStaffModal"
                                        data-action="{{ route('super-admin.staff-users.toggle-status', $row) }}"
                                        data-name="{{ $row->full_name }}"
                                        data-next="{{ $row->status === 'active' ? 'deactivate' : 'activate' }}">
                                        {{ $row->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button type="button" class="btn-outline-teal border-danger text-danger" style="font-size:11px;padding:4px 8px;"
                                        data-bs-toggle="modal" data-bs-target="#deleteStaffModal"
                                        data-action="{{ route('super-admin.staff-users.destroy', $row) }}"
                                        data-name="{{ $row->full_name }}">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="staff-users-pagination" aria-label="Staff users pages">
                {{ $users->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Toggle status --}}
<div class="modal fade" id="toggleStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h6 class="modal-title">Confirm status change</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="toggleStaffForm" method="post">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p class="mb-0 small" id="toggleStaffMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-teal">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete --}}
<div class="modal fade" id="deleteStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h6 class="modal-title">Delete staff user</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteStaffForm" method="post">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p class="mb-0 small" id="deleteStaffMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-teal" style="background:var(--danger);border-color:var(--danger);">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleModal = document.getElementById('toggleStaffModal');
    if (toggleModal) {
        toggleModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var form = document.getElementById('toggleStaffForm');
            var msg = document.getElementById('toggleStaffMessage');
            if (!btn || !form || !msg) return;
            form.action = btn.getAttribute('data-action') || '#';
            var name = btn.getAttribute('data-name') || '';
            var next = btn.getAttribute('data-next') || '';
            msg.textContent = next === 'deactivate'
                ? 'Deactivate ' + name + '? They will not be able to sign in until reactivated.'
                : 'Activate ' + name + '? They will be able to sign in again.';
        });
    }
    var delModal = document.getElementById('deleteStaffModal');
    if (delModal) {
        delModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var form = document.getElementById('deleteStaffForm');
            var msg = document.getElementById('deleteStaffMessage');
            if (!btn || !form || !msg) return;
            form.action = btn.getAttribute('data-action') || '#';
            msg.textContent = 'Permanently delete ' + (btn.getAttribute('data-name') || 'this user') + '? This cannot be undone.';
        });
    }
});
</script>
@endpush
@endsection
