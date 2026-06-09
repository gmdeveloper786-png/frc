@extends('layouts.app')
@section('title', 'Assessments')
@section('page-title', 'Assessments')

@push('styles')
<style>
.assessments-index-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.assessments-index-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.assessments-index-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.assessments-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.assessments-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.assessments-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.assessments-index-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.assessments-index-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.assessments-index-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.assessments-index-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-clipboard-list me-2" style="color:var(--teal);"></i>Assessments ({{ $assessments->total() }})</h6>
        <a href="{{ route('assessments.create') }}" class="btn-teal btn-view-all" style="white-space:nowrap;"><i class="fa-solid fa-plus"></i> Schedule Assessment</a>
    </div>
    <form method="GET" class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        @if($branches->count() === 1)
            <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
        @endif
        @if(request()->filled('child_id'))
            <input type="hidden" name="child_id" value="{{ request('child_id') }}">
        @endif
        <div class="row g-2 align-items-end form-frc">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-1">Search child</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Child name or GR number">
            </div>
            @if($branches->count() > 1)
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-1">Branch</label>
                <select name="branch_id" class="form-control">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->displayLabel() }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    @foreach(['draft','publish','completed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small text-muted mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small text-muted mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-12 col-md-2 filter-actions">
                <button type="submit" class="btn-teal">Filter</button>
                @if(request()->hasAny(['branch_id','status','date_from','date_to','search','child_id']))
                    <a href="{{ route('assessments.index') }}" class="btn-outline-teal" style="justify-content:center;">Clear</a>
                @endif
            </div>
        </div>
    </form>
    @if($assessments->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-clipboard-list empty-icon"></i><h5>No Assessments Found</h5></div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead><tr><th>#</th><th>Date</th><th>Day</th><th>Time</th><th>Branch</th><th>Therapist</th><th>Children</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($assessments as $item)
                        @php
                            $childrenList = $item->children->filter(fn ($c) => filled($c->full_name));
                            $firstChild = $childrenList->first();
                            $extraChildCount = max(0, $childrenList->count() - 1);
                            $childNames = $childrenList->pluck('full_name')->join(', ');
                        @endphp
                        <tr>
                            <td style="color:var(--text-muted);">{{ $assessments->firstItem() + $loop->index }}</td>
                            <td style="font-weight:500;white-space:nowrap;">{{ $item->date->format('d M Y') }}</td>
                            <td style="white-space:nowrap;">{{ $item->day }}</td>
                            <td style="white-space:nowrap;">{{ date('g:i A', strtotime($item->time)) }}</td>
                            <td style="white-space:nowrap;">{{ $item->branch?->name ?? '—' }}</td>
                            <td>
                                @if($item->therapist)
                                    {{ $item->therapist->full_name }}
                                @elseif($item->status === 'draft')
                                    <span class="badge-status badge-draft">Not Assigned</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td style="font-size:13px; white-space:nowrap;" @if($childNames !== '') title="{{ $childNames }}" @endif>
                                @if($firstChild)
                                    <a href="{{ route('children.show', $firstChild->id) }}" style="color:var(--navy);font-weight:500;">{{ $firstChild->full_name }}</a>
                                    @if($extraChildCount > 0)
                                        <span class="text-muted"> +{{ $extraChildCount }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge-status badge-{{ $item->status === 'cancelled' ? 'cancelled' : $item->status }}">{{ $item->status === 'cancelled' ? 'Cancelled' : ucfirst($item->status) }}</span></td>
                            <td>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                    <a href="{{ route('assessments.show', $item) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-eye"></i></a>
                                    @if(in_array($item->status, ['draft','publish'], true))
                                        <a href="{{ route('assessments.edit', $item) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-pen"></i></a>
                                    @endif
                                    @if(auth()->user()->isSuperAdmin() && $item->status === 'publish')
                                        <form action="{{ route('assessments.complete', $item) }}" method="POST">@csrf
                                            <button type="submit" style="background:var(--success);color:#fff;border:none;border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;" onclick="return confirm('Mark as completed?')"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->isSuperAdmin() && ! in_array($item->status, ['completed','cancelled'], true))
                                        <button type="button" style="background:none;border:1.5px solid var(--danger);color:var(--danger);border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;"
                                            data-bs-toggle="modal" data-bs-target="#cancelModal{{ $item->id }}"><i class="fa-solid fa-ban"></i></button>
                                    @endif
                                    <form action="{{ route('assessments.destroy', $item) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="background:none;border:1.5px solid var(--danger);color:var(--danger);border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                                @if(auth()->user()->isSuperAdmin() && ! in_array($item->status, ['completed','cancelled'], true))
                                    <div class="modal fade" id="cancelModal{{ $item->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content" style="border-radius:12px;">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Cancel assessment</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('assessments.cancel', $item) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                                                        <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Why is this assessment being cancelled?"></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn-teal">Confirm cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($assessments->hasPages())
            <div class="assessments-index-pagination" aria-label="Assessments list pages">
                {{ $assessments->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
