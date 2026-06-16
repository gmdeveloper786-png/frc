@extends('layouts.app')
@section('title', 'Assigned Children')
@section('page-title', 'Assigned Children')

@push('styles')
<style>
.therapist-children-table .therapist-children-disabilities-cell {
    white-space: normal;
    min-width: 9rem;
    max-width: 14rem;
}
.therapist-children-table .therapist-children-disabilities-cell .child-show-tag-list {
    gap: 4px;
}
.therapist-children-table .therapist-children-disabilities-cell .child-show-tag {
    font-size: 11px;
    padding: 3px 8px;
    line-height: 1.35;
    max-width: 100%;
    overflow-wrap: anywhere;
    word-break: break-word;
    white-space: normal;
}
.therapist-children-disabilities-more {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.35;
    color: var(--teal-dark, #006666);
    background: var(--teal-light, #e6f5f5);
    border: 1px solid rgba(0, 128, 128, 0.3);
    white-space: nowrap;
    flex-shrink: 0;
    cursor: default;
}
</style>
@endpush

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-children me-2" style="color:var(--teal);"></i>Assigned Children ({{ $rows->total() }})</h6>
    </div>
    @if($rows->isEmpty())
        <div class="empty-state py-5">
            <i class="fa-solid fa-user-slash empty-icon" style="font-size:28px;"></i>
            <p class="mb-0">No children assigned yet.</p>
        </div>
    @else
        <div class="table-responsive therapist-children-table">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>Child</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Disabilities</th>
                        <th>Branch</th>
                        <th style="white-space:nowrap;">Last assessment</th>
                        <th style="white-space:nowrap;">Last session</th>
                        <th style="white-space:nowrap;">Next session</th>
                        <th>Enrollment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $child = $row['child'];
                            $lastAssess = $row['last_assessment'];
                            $next = $row['next_session'];
                            $sortedDisabilities = $child->disabilities->sortBy(fn ($d) => [strcasecmp($d->name, 'Other') === 0 ? 1 : 0, strtolower($child->disabilityLabel($d))]);
                            $visibleDisabilities = $sortedDisabilities->take(2);
                            $extraDisabilityCount = max(0, $sortedDisabilities->count() - 2);
                            $allDisabilityLabels = $sortedDisabilities->map(fn ($d) => $child->disabilityLabel($d))->join(', ');
                        @endphp
                        <tr>
                            <td style="font-weight:600;color:var(--navy); white-space:nowrap;">
                                {{ $child->full_name }}
                                @if($child->gr_number)
                                    <span style="display:block;font-size:11px;font-family:monospace;color:var(--text-muted);font-weight:500;">{{ $child->gr_number }}</span>
                                @endif
                            </td>
                            <td>{{ $child->age ? $child->age.'y' : '—' }}</td>
                            <td>{{ $child->gender ? ucfirst($child->gender) : '—' }}</td>
                            <td class="small therapist-children-disabilities-cell">
                                @if($sortedDisabilities->isNotEmpty())
                                    <div class="child-show-tag-list">
                                        @foreach($visibleDisabilities as $disability)
                                            <span class="child-show-tag">{{ $child->disabilityLabel($disability) }}</span>
                                        @endforeach
                                        @if($extraDisabilityCount > 0)
                                            <span class="therapist-children-disabilities-more" title="{{ $allDisabilityLabels }}">+{{ $extraDisabilityCount }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small" style="white-space:nowrap;">{{ $row['branch_name'] }}</td>
                            <td class="small" style="white-space:nowrap;">{{ $lastAssess?->date?->format('d M Y') ?? '—' }}</td>
                            <td class="small" style="white-space:nowrap;">{{ $row['last_session_date'] ? \Carbon\Carbon::parse($row['last_session_date'])->format('d M Y') : '—' }}</td>
                            <td class="small" style="white-space:nowrap;">
                                @if($next)
                                    {{ $next->session_date ? \Carbon\Carbon::parse($next->session_date)->format('d M Y') : ($next->day.' '.$next->time_slot) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="white-space:nowrap;"><span class="badge-status badge-{{ str_contains(strtolower((string) $row['enrollment_status']), 'active') ? 'approved' : (str_contains(strtolower((string) $row['enrollment_status']), 'approv') ? 'pending' : 'draft') }}" style="font-size:10px;">{{ $row['enrollment_status'] }}</span></td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('therapist.children.show', $child) }}" class="btn-outline-teal" style="font-size:11px;padding:4px 8px;"><i class="fa-solid fa-eye"></i> Profile</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="frc-list-pagination" aria-label="Assigned children pages">
                {{ $rows->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
