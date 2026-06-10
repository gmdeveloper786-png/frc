@extends('layouts.app')
@section('title', 'My Assessments')
@section('page-title', 'My Assessments')

@section('content')
@php
    $statuses = [
        '' => 'All statuses',
        'publish' => 'Publish',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="card-frc card-frc--list-page mb-4">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-clipboard-list me-2" style="color:var(--teal);"></i>My Assessments ({{ $assessments->total() }})</h6>
    </div>

    <div class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        <form method="get" action="{{ route('therapist.assessments.index') }}" id="therapistAssessmentsFilterForm" class="frc-sessions-filters">
            <div class="frc-sessions-filters-row">
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_start_date">Start date</label>
                    <input type="date" id="filter_start_date" name="start_date" class="form-control form-control-sm w-100"
                        value="{{ request('start_date') }}">
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_end_date">End date</label>
                    <input type="date" id="filter_end_date" name="end_date" class="form-control form-control-sm w-100"
                        value="{{ request('end_date') }}">
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_branch_id">Branch</label>
                    <select id="filter_branch_id" name="branch_id" class="form-select form-select-sm w-100">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) request('branch_id') === (int) $branch->id)>{{ $branch->displayLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_status">Status</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm w-100">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected((request('status', '') ?: '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_child_id">Child</label>
                    <select id="filter_child_id" name="child_id" class="form-select form-select-sm w-100">
                        <option value="">All children</option>
                        @foreach($filterChildren ?? [] as $child)
                            <option value="{{ $child->id }}" @selected((int) request('child_id') === (int) $child->id)>{{ $child->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="frc-sessions-filter-field frc-sessions-filter-actions">

                    <div class="filter-actions">
                        <button type="submit" class="btn-teal">Filter</button>
                        @if($hasActiveFilters ?? false)
                            <a href="{{ route('therapist.assessments.index') }}" class="btn-outline-teal">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if($assessments->isEmpty())
        <div class="empty-state py-5">
            <i class="fa-solid fa-clipboard-list empty-icon" style="font-size:28px;"></i>
            <p class="mb-0 mt-2">{{ ($hasActiveFilters ?? false) ? 'No assessments match your filters.' : 'No assessments found.' }}</p>
        </div>
    @else
        <div class="table-responsive frc-sessions-table-wrap">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Branch</th>
                        <th>Children</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessments as $row)
                        @php
                            $childNames = $row->children->pluck('full_name')->filter()->join(', ');
                        @endphp
                        <tr>
                            <td style="color:var(--text-muted); white-space:nowrap;">{{ $assessments->firstItem() + $loop->index }}</td>
                            <td style="font-weight:500;white-space:nowrap;">{{ $row->date->format('d M Y') }}</td>
                            <td style="white-space:nowrap;">{{ $row->day }}</td>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($row->time)->format('h:i A') }}</td>
                            <td style="white-space:nowrap;">{{ $row->branch?->name ?? '—' }}</td>
                            <td style="font-size:13px;white-space:nowrap;">
                                @if($childNames !== '')
                                    {{ $childNames }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-status badge-{{ $row->status === 'cancelled' ? 'cancelled' : $row->status }}">
                                    {{ $row->status === 'cancelled' ? 'Cancelled' : ucfirst($row->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('therapist.assessments.show', $row) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;">
                                    <i class="fa-solid fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($assessments->hasPages())
            <div class="frc-list-pagination" aria-label="Assessments list pages">
                {{ $assessments->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
(function () {
    var form = document.getElementById('therapistAssessmentsFilterForm');
    if (!form) return;
    form.addEventListener('submit', function () {
        form.querySelectorAll('input[type="date"], select').forEach(function (el) {
            if (!el.value) {
                el.removeAttribute('name');
            }
        });
    });
})();
</script>
@endpush
