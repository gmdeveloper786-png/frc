@extends('layouts.app')
@section('title', 'Assigned Children')
@section('page-title', 'Assigned Children')

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
        <div class="table-responsive">
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
                        <th>Next session</th>
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
                            <td class="small" style="white-space:nowrap;">{{ $child->disabilities->pluck('name')->join(', ') ?: '—' }}</td>
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
