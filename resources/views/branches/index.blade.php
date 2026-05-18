@extends('layouts.app')
@section('title', 'Branches')
@section('page-title', 'Branches')

@push('styles')
<style>
.branches-index-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.branches-index-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.branches-index-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.branches-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.branches-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.branches-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.branches-index-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.branches-index-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.branches-index-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.branches-index-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-building me-2" style="color:var(--teal);"></i>Branches ({{ $branches->total() }})</h6>
        <a href="{{ route('branches.create') }}" class="btn-teal btn-view-all" style="white-space:nowrap;"><i class="fa-solid fa-plus"></i> Add Branch</a>
    </div>
    <form method="GET" class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        <div class="row g-2 align-items-end form-frc">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or city...">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="publish" {{ request('status') == 'publish' ? 'selected' : '' }}>Published</option>
                </select>
            </div>
            <div class="col-12 col-md-4 filter-actions">
                <button type="submit" class="btn-teal">Filter</button>
                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('branches.index') }}" class="btn-outline-teal" style="justify-content:center;">Clear</a>
                @endif
            </div>
        </div>
    </form>
    @if($branches->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-building empty-icon"></i><h5>No Branches Found</h5></div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead><tr><th>#</th><th>Name</th><th>City</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($branches as $item)
                        <tr>
                            <td style="color:var(--text-muted);">{{ $branches->firstItem() + $loop->index }}</td>
                            <td style="font-weight:500;">{{ $item->name }}</td>
                            <td>{{ $item->city ?? '—' }}</td>
                            <td style="font-size:13px;">{{ $item->phone ?? '—' }}</td>
                            <td><span class="badge-status badge-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="{{ route('branches.edit', $item) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-pen"></i></a>
                                    <form action="{{ route('branches.destroy', $item) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="background:none;border:1.5px solid var(--danger);color:var(--danger);border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($branches->hasPages())
            <div class="branches-index-pagination" aria-label="Branches list pages">
                {{ $branches->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
