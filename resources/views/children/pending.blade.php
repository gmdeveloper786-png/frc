@extends('layouts.app')
@section('title', 'Pending Child Approvals')
@section('page-title', 'Pending Child Approvals')

@push('styles')
<style>
.pending-children-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.pending-children-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.pending-children-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.pending-children-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.pending-children-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.pending-children-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.pending-children-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.pending-children-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.pending-children-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.pending-children-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc">
            <i class="fa-solid fa-user-clock me-2" style="color:var(--teal);"></i>
            Pending Approvals ({{ $children->total() }})
        </h6>
        <a href="{{ route('children.index') }}" class="btn-outline-teal btn-view-all">All Children</a>
    </div>

    @if($children->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-user-check empty-icon"></i>
            <h5>No Pending Approvals</h5>
            <p>All child registrations have been reviewed.</p>
        </div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Child Name</th>
                        <th>Father's Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Disabilities</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($children as $child)
                        <tr>
                            <td style="color:var(--text-muted);">{{ $children->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('children.show', $child->id) }}" style="font-weight:600;color:var(--navy);">{{ $child->full_name }}</a>
                                @if($child->age) <span style="font-size:12px;color:var(--text-muted);">({{ $child->age }}y)</span> @endif
                            </td>
                            <td>{{ $child->father_name ?? '—' }}</td>
                            <td style="font-size:13px;">{{ $child->email }}</td>
                            <td style="font-size:13px;">{{ $child->phone_number ?? '—' }}</td>
                            <td style="font-size:12px;color:var(--text-muted);">
                                {{ $child->disabilities->pluck('name')->join(', ') ?: '—' }}
                            </td>
                            <td style="font-size:13px;color:var(--text-muted);">{{ $child->created_at->diffForHumans() }}</td>
                            <td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                    {{-- Approve --}}
                                    <form action="{{ route('children.approve', $child->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-teal" style="padding:5px 12px;font-size:12px;"
                                            onclick="return confirm('Approve {{ $child->full_name }}?')">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    </form>
                                    {{-- Reject --}}
                                    <button class="btn-outline-teal" style="padding:5px 12px;font-size:12px;color:var(--danger);border-color:var(--danger);"
                                        data-bs-toggle="modal" data-bs-target="#rejectModal{{ $child->id }}">
                                        <i class="fa-solid fa-xmark"></i> Reject
                                    </button>
                                </div>

                                {{-- Reject Modal --}}
                                <div class="modal fade" id="rejectModal{{ $child->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content" style="border-radius:16px;border:1px solid var(--border-soft);">
                                            <div class="modal-header" style="border-bottom:1px solid var(--border-soft);">
                                                <h6 class="modal-title" style="font-family:'Poppins',sans-serif;color:var(--navy);">Reject Registration</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('children.reject', $child->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body form-frc">
                                                    <p style="font-size:14px;color:var(--text-muted);">You are rejecting <strong>{{ $child->full_name }}</strong>'s registration.</p>
                                                    <label>Rejection Reason <span style="color:var(--danger)">*</span></label>
                                                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Provide a reason..."></textarea>
                                                </div>
                                                <div class="modal-footer" style="border-top:1px solid var(--border-soft);">
                                                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn-navy">Confirm Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($children->hasPages())
            <div class="pending-children-pagination" aria-label="Pending approvals pages">
                {{ $children->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
