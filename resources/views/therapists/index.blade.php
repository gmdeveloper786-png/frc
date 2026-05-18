@extends('layouts.app')
@section('title', 'Therapists')
@section('page-title', 'Therapists')

@push('styles')
<style>
.therapists-index-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.therapists-index-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.therapists-index-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.therapists-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.therapists-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.therapists-index-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.therapists-index-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.therapists-index-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.therapists-index-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.therapists-index-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-user-doctor me-2" style="color:var(--teal);"></i>Therapists ({{ $therapists->total() }})</h6>
        <a href="{{ route('therapists.create') }}" class="btn-teal btn-view-all" style="white-space:nowrap;"><i class="fa-solid fa-plus"></i> Add Therapist</a>
    </div>
    <form method="GET" class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search...">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">Branch</label>
                <select name="branch_id" class="form-control">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 filter-actions">
                <button type="submit" class="btn-teal">Filter</button>
                @if(request()->hasAny(['search','branch_id']))
                    <a href="{{ route('therapists.index') }}" class="btn-outline-teal" style="justify-content:center;">Clear</a>
                @endif
            </div>
        </div>
    </form>
    @if($therapists->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-user-doctor empty-icon"></i><h5>No Therapists Found</h5>
            <a href="{{ route('therapists.create') }}" class="btn-teal mt-2">Add Therapist</a>
        </div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Branch</th><th>Services</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($therapists as $t)
                        <tr>
                            <td style="color:var(--text-muted);">{{ $therapists->firstItem() + $loop->index }}</td>
                            <td style="font-weight:500;color:var(--navy);">{{ $t->full_name ?? '—' }}</td>
                            <td style="font-size:13px;">{{ $t->email ?? '—' }}</td>
                            <td>{{ $t->therapistProfile?->branch?->name ?? '—' }}</td>
                            <td style="font-size:12px;max-width:220px;">
                                @php
                                    $services = $t->therapistServices ?? [];
                                    $serviceCount = count($services);
                                @endphp
                                @if($serviceCount === 0)
                                    <span style="color:var(--text-muted);">—</span>
                                @elseif($serviceCount === 1)
                                    <span style="display:inline-block;background:var(--teal-light);color:var(--teal-dark);padding:3px 8px;border-radius:6px;margin:2px 4px 2px 0;font-size:11px;">
                                        {{ $services[0]->name ?? '—' }}
                                    </span>
                                @else
                                    <span style="display:inline-block;background:var(--teal-light);color:var(--teal-dark);padding:3px 8px;border-radius:6px;margin:2px 4px 2px 0;font-size:11px;">
                                        {{ $services[0]->name ?? '—' }}, (+{{ $serviceCount - 1 ?? '—' }})
                                    </span>
                                @endif
                            </td>
                       
                            <td>
                                <span class="badge-status {{ $t->therapistProfile?->status == 'active' ? 'badge-active' : 'badge-inactive' ?? '—' }}">
                                    {{ ucfirst($t->therapistProfile?->status ?? '—') }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="{{ route('therapists.show', $t->id) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-eye"></i></a>
                                    <a href="{{ route('therapists.edit', $t->id) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-pen"></i></a>
                                    <form action="{{ route('therapists.destroy', $t->id) }}" method="POST">
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
        @if($therapists->hasPages())
            <div class="therapists-index-pagination" aria-label="Therapists list pages">
                {{ $therapists->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
