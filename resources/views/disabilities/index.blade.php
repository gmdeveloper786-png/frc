@extends('layouts.app')
@section('title', 'Present Complaints')
@section('page-title', 'Present Complaints')

@push('styles')
<style>
.disabilities-index-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.disabilities-index-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.disabilities-index-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.disabilities-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.disabilities-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.disabilities-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.disabilities-index-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.disabilities-index-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.disabilities-index-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.disabilities-index-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-heart-pulse me-2" style="color:var(--teal);"></i>Present Complaints ({{ $disabilities->total() }})</h6>
        <a href="{{ route('disabilities.create') }}" class="btn-teal btn-view-all" style="white-space:nowrap;">
            <i class="fa-solid fa-plus"></i> Add Present Complaint
        </a>
    </div>

    <form method="GET" class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        <div class="row g-2 align-items-end form-frc">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search present complaints...">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="publish" {{ request('status')==='publish' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                </select>
            </div>
            <div class="col-12 col-md-4 filter-actions">
                <button type="submit" class="btn-teal">Filter</button>
                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('disabilities.index') }}" class="btn-outline-teal" style="justify-content:center;">Clear</a>
                @endif
            </div>
        </div>
    </form>

    @if($disabilities->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-heart-pulse empty-icon"></i>
            <h5>No Present Complaints Found</h5>
            <p>Add your first present complaint to get started.</p>
            <a href="{{ route('disabilities.create') }}" class="btn-teal mt-2">Add Present Complaint</a>
        </div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Status</th><th>Created By</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($disabilities as $item)
                        <tr>
                            <td style="color:var(--text-muted);">{{ $disabilities->firstItem() + $loop->index }}</td>
                            <td style="font-weight:500;">{{ $item->name }}</td>
                            <td><span class="badge-status badge-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                            <td style="font-size:13px;color:var(--text-muted);">{{ $item->createdBy?->full_name ?? '—' }}</td>
                            <td style="font-size:13px;color:var(--text-muted);">{{ $item->created_at->format('d M Y') }}</td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="{{ route('disabilities.edit', $item) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    @unless(strcasecmp($item->name, 'Other') === 0)
                                    <form action="{{ route('disabilities.destroy', $item) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="background:none;border:1.5px solid var(--danger);color:var(--danger);border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;"
                                            data-confirm="Permanently delete {{ e($item->name) }}? This cannot be undone.">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($disabilities->hasPages())
            <div class="disabilities-index-pagination" aria-label="Present complaints list pages">
                {{ $disabilities->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
