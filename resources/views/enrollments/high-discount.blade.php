@extends('layouts.app')
@section('title', 'High Discount Approvals')
@section('page-title', 'High Discount Approval Queue')

@push('styles')
<style>
.high-discount-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.high-discount-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.high-discount-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.high-discount-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.high-discount-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.high-discount-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.high-discount-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.high-discount-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.high-discount-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.high-discount-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
.high-discount-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.high-discount-actions form {
    display: inline-flex;
    margin: 0;
}
.high-discount-actions .hd-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    padding: 6px 12px;
    font-size: 12px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    line-height: 1;
    border: none;
    border-radius: var(--radius-btn, 8px);
    cursor: pointer;
}
.high-discount-actions .hd-action-btn--approve {
    background: var(--success);
    color: #fff;
}
.high-discount-actions .hd-action-btn--reject {
    background: var(--danger);
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-percent me-2" style="color:var(--warning);"></i>Pending High Discount Approvals ({{ $enrollments->total() }})</h6>
    </div>
    @if($enrollments->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-check empty-icon" style="color:var(--success);"></i>
            <h5>No Pending High Discount Requests</h5>
            <p>All high discount enrollments have been reviewed.</p>
        </div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Child</th>
                        <th>Branch / Therapist</th>
                        <th>Total Sessions</th>
                        <th>Before Discount / Subtotal</th>
                        <th>Discount</th>
                        <th>After Discount / Final Total</th>
                        <th>Reason</th>
                        <th>Document</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrollments as $e)
                        <tr>
                            <td style="color:var(--text-muted);">{{ $enrollments->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="font-weight:600;">{{ $e->child?->full_name }}</div>
                                <div style="font-size:12px;color:var(--text-muted);">{{ $e->child?->phone_number }}</div>
                            </td>
                            <td style="font-size:13px;">
                                <div>{{ $e->branch?->name }}</div>
                                <div style="color:var(--text-muted);">{{ $e->therapist?->full_name }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--navy);">{{ $e->total_sessions }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--navy);">{{ frc_pkr($e->subtotal) }}</div>
                            </td>
                            <td>
                                <span style="background:rgba(220,53,69,.1);color:var(--danger);padding:4px 10px;border-radius:20px;font-weight:600;font-size:13px;">
                                    {{ $e->discount_percentage }}%
                                </span>
                                <div style="font-size:12px;color:var(--danger);margin-top:4px;">- {{ frc_pkr($e->discount_amount) }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--navy);">{{ frc_pkr($e->final_total) }}</div>
                            </td>
                            <td style="max-width:200px;">
                                <p style="font-size:12px;color:var(--text-muted);margin:0;white-space:pre-wrap;">{{ Str::limit($e->discount_reason, 80) }}</p>
                                @if(strlen($e->discount_reason) > 80)
                                    <a href="{{ route('enrollments.show', $e->id) }}" style="font-size:12px;color:var(--teal);">See more</a>
                                @endif
                            </td>
                            <td>
                                @if($e->discount_file)
                                    <a href="{{ frc_storage_url($e->discount_file) }}" target="_blank" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;">
                                        <i class="fa-solid fa-file"></i> View
                                    </a>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">None</span>
                                @endif
                            </td>
                            <td>
                                <div class="high-discount-actions">
                                    <form action="{{ route('enrollments.approve', $e->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="hd-action-btn hd-action-btn--approve" onclick="return confirm('Approve this high discount enrollment?')">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button" class="hd-action-btn hd-action-btn--reject btn-reject-{{ $e->id }}"
                                        data-bs-toggle="modal" data-bs-target="#rejectModal{{ $e->id }}">
                                        <i class="fa-solid fa-xmark"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($enrollments->hasPages())
            <div class="high-discount-pagination" aria-label="High discount queue pages">
                {{ $enrollments->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Reject Modals --}}
@foreach($enrollments as $e)
<div class="modal fade" id="rejectModal{{ $e->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border-soft);">
                <h6 class="modal-title" style="font-family:'Poppins',sans-serif;color:var(--navy);">Reject Enrollment — {{ $e->child?->full_name }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('enrollments.reject', $e->id) }}" method="POST">
                @csrf
                <div class="modal-body form-frc">
                    <label>Rejection Reason <span style="color:var(--danger)">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Provide a reason for rejection..."></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-soft);">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-navy">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
