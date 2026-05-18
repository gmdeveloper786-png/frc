@extends('layouts.app')
@section('title', 'Children')
@section('page-title', 'Children')

@push('styles')
<style>
.children-index-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.children-index-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.children-index-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.children-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.children-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.children-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.children-index-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.children-index-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.children-index-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.children-index-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
{{-- Filters --}}
<div class="card-frc card-frc--list-page mb-3">
    <form method="GET" class="list-filters row g-2 align-items-end form-frc">
        <div class="col-12 col-md-4">
            <label>Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, email, phone...">
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                @foreach(['pending','approved','rejected','active','inactive'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 filter-actions">
            <button type="submit" class="btn-teal">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('children.index') }}" class="btn-outline-teal" style="justify-content:center;">Clear</a>
            @endif
        </div>
    </form>
</div>

<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-children me-2" style="color:var(--teal);"></i>All Children ({{ $children->total() }})</h6>
        <a href="{{ route('children.pending') }}" class="btn-teal btn-view-all" style="font-size:13px;white-space:nowrap;">
            <i class="fa-solid fa-user-clock"></i> Pending Approvals
        </a>
    </div>

    @if($children->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-children empty-icon"></i>
            <h5>No Children Found</h5>
            <p>No children match your current filters.</p>
        </div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Age</th><th>Gender</th><th>Status</th><th>Registered</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($children as $child)
                        <tr>
                            <td style="color:var(--text-muted);">{{ $children->firstItem() + $loop->index }}</td>
                            <td style="font-weight:600;color:var(--navy); white-space:nowrap;">
                                {{ $child->full_name ?? '—' }}
                            </td>
                            <td style="font-size:13px; white-space:nowrap;">{{ $child->email ?? '—' }}</td>
                            <td style="font-size:13px; white-space:nowrap;">{{ $child->phone_number ?? '—' }}</td>
                            <td style="font-size:13px; white-space:nowrap;">{{ $child->age ? $child->age.'y' : '—' }}</td>
                            <td style="font-size:13px; white-space:nowrap;">{{ ucfirst($child->gender ?? '—') }}</td>
                            <td style="white-space:nowrap;"><span class="badge-status badge-{{ $child->status }}">{{ ucfirst(str_replace('_',' ', $child->status)) }}</span></td>
                            <td style="font-size:13px;color:var(--text-muted); white-space:nowrap;">{{ $child->created_at?->format('d M Y') ?? '—' }}</td>
                            <td style="white-space:nowrap;">
                                <div style="display:flex;gap:6px;">
                                    <a href="{{ route('children.show', $child->id) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="{{ route('children.edit', $child->id) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <form action="{{ route('children.destroy', $child->id) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete" style="background:none;border:1.5px solid var(--danger);color:var(--danger);border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;"
                                            onclick="return confirm('Delete {{ $child->full_name }}? This archives the account.')"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($children->hasPages())
            <div class="children-index-pagination" aria-label="Children list pages">
                {{ $children->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
