@extends('layouts.app')
@section('title', 'Progress notes')
@section('page-title', 'Progress notes')

@section('content')
@php
    $statuses = [
        '' => 'All statuses',
        'draft' => 'Draft',
        'completed' => 'Completed',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="mb-0 small text-muted">Notes are visible to authorized staff. You can edit only notes you created.</p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('therapist.progress-notes.pending') }}" class="btn-teal" style="font-size:13px;text-decoration:none;"><i class="fa-solid fa-list-check me-1"></i>Pending Notes</a>
        {{-- <a href="{{ route('therapist.progress-notes.create') }}" class="btn-teal" style="font-size:13px;text-decoration:none;"><i class="fa-solid fa-plus me-1"></i>Add note</a> --}}
    </div>
</div>

<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-file-lines me-2" style="color:var(--teal);"></i>Progress notes ({{ $notes->total() }})</h6>
    </div>

    <div class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        <form method="get" action="{{ route('therapist.progress-notes.index') }}" id="therapistProgressNotesFilterForm" class="frc-sessions-filters">
            <div class="frc-sessions-filters-row">
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_child_id">Child</label>
                    <select id="filter_child_id" name="child_id" class="form-select form-select-sm w-100">
                        <option value="">All children</option>
                        @foreach($filterChildren ?? [] as $child)
                            <option value="{{ $child->id }}" @selected((int) request('child_id') === (int) $child->id)>{{ $child->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="frc-sessions-filter-field">
                    <label class="form-label small text-muted mb-1" for="filter_service_id">Service</label>
                    <select id="filter_service_id" name="service_id" class="form-select form-select-sm w-100">
                        <option value="">All services</option>
                        @foreach($filterServices ?? [] as $service)
                            <option value="{{ $service->id }}" @selected((int) request('service_id') === (int) $service->id)>{{ $service->name }}</option>
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
                <div class="frc-sessions-filter-field frc-sessions-filter-actions">
                    <label class="form-label small text-muted mb-1 d-block" style="visibility:hidden;" aria-hidden="true">&nbsp;</label>
                    <div class="filter-actions">
                        <button type="submit" class="btn-teal">Filter</button>
                        @if($hasActiveFilters ?? false)
                            <a href="{{ route('therapist.progress-notes.index') }}" class="btn-outline-teal">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if($notes->isEmpty())
        <div class="empty-state py-5">
            <i class="fa-solid fa-file-lines empty-icon" style="font-size:28px;"></i>
            <p class="mb-0 mt-2">{{ ($hasActiveFilters ?? false) ? 'No progress notes match your filters.' : 'No progress notes yet.' }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Child</th>
                        <th>Service</th>
                        <th>Progress</th>
                        <th style="white-space:nowrap;">Added by</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notes as $note)
                        <tr>
                            <td style="font-weight:600;color:var(--navy);white-space:nowrap;">{{ $note->session_date?->format('d M Y') }}</td>
                            <td style="white-space:nowrap;">{{ $note->child?->full_name }}</td>
                            <td class="small" style="white-space:nowrap;">{{ $note->service?->name ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ \App\Models\ProgressNote::labelForProgressLevel($note->progress_level) }}</td>
                            <td class="small" style="white-space:nowrap;">{{ $note->createdBy?->full_name ?? '—' }}</td>
                            <td style="white-space:nowrap;">
                                <span class="badge-status badge-{{ $note->status === 'completed' ? 'approved' : 'draft' }}" style="font-size:10px;">{{ ucfirst($note->status) }}</span>
                            </td>
                            <td style="white-space:nowrap;">
                                <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                    <a href="{{ route('therapist.progress-notes.show', $note) }}" class="btn-outline-teal" style="font-size:11px;padding:4px 10px;text-decoration:none;">View</a>
                                    <a href="{{ route('therapist.progress-notes.edit', $note) }}" class="btn-teal" style="font-size:11px;padding:4px 10px;text-decoration:none;">Edit</a>
                                    <form action="{{ route('therapist.progress-notes.destroy', $note) }}" method="post" class="d-inline m-0" onsubmit="return confirm('Delete this note?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-outline-danger" style="font-size:11px;padding:4px 10px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($notes->hasPages())
            <div class="frc-list-pagination" aria-label="Progress notes pages">
                {{ $notes->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
