@extends('layouts.app')
@section('title', 'Completed Sessions Missing Progress Notes')
@section('page-title', 'Completed Sessions Missing Progress Notes')

@section('content')
<p class="small text-muted mb-3">Completed sessions in the last {{ $pendingLookbackDays ?? 90 }} days that still need professional progress documentation (missing note or draft only).</p>

<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-file-circle-exclamation me-2" style="color:var(--teal);"></i>Pending documentation ({{ $rows->total() }})</h6>
    </div>
    @if($rows->isEmpty())
        <div class="empty-state py-5">
            <i class="fa-solid fa-circle-check empty-icon" style="font-size:32px;color:var(--teal);"></i>
            <p class="mb-0 fw-semibold" style="color:var(--navy);">No pending notes.</p>
            <p class="small text-muted mb-0 mt-2">All completed sessions are documented.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>Session date</th>
                        <th>Child</th>
                        <th>Service</th>
                        <th>Time slot</th>
                        <th>Branch</th>
                        <th>Session status</th>
                        <th>Progress note status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $sch = $row['schedule'];
                            $cid = $sch->enrollment?->child_id;
                            $dateIso = $row['effective_date']->toDateString();
                            $pnCreate = $cid
                                ? route('therapist.progress-notes.create', array_filter([
                                    'child_id' => $cid,
                                    'session_date' => $dateIso,
                                    'service_id' => $sch->enrollment?->service_id,
                                    'enrollment_id' => $sch->enrollment_id,
                                    'enrollment_schedule_id' => $sch->id,
                                ], fn ($v) => $v !== null && $v !== ''))
                                : null;
                            $draftId = $row['draft_progress_note_id'] ?? null;
                            $pnRow = $row['progress_note_row_status'] ?? 'missing';
                        @endphp
                        <tr>
                            <td style="font-weight:600;color:var(--navy);">{{ $row['effective_date']->format('d M Y') }}</td>
                            <td>{{ $row['child_name'] }}</td>
                            <td class="small">{{ $row['service_name'] }}</td>
                            <td class="small">{{ $row['time_slot'] }}</td>
                            <td class="small">{{ $row['branch_name'] }}</td>
                            <td><span class="badge-status badge-session-completed" style="font-size:10px;">Completed</span></td>
                            <td>
                                @if($pnRow === 'draft')
                                    <span class="badge-status badge-draft" style="font-size:10px;">Draft</span>
                                @else
                                    <span class="badge-status badge-session-scheduled" style="font-size:10px;">Missing</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($draftId)
                                    <a href="{{ route('therapist.progress-notes.edit', $draftId) }}" class="btn-teal" style="font-size:11px;padding:5px 12px;text-decoration:none;">Continue draft note</a>
                                @elseif($pnCreate)
                                    <a href="{{ $pnCreate }}" class="btn-teal" style="font-size:11px;padding:5px 12px;text-decoration:none;">Add progress note</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="frc-list-pagination" aria-label="Pending progress notes pages">
                {{ $rows->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

<div class="mt-3">
    <a href="{{ route('therapist.progress-notes.index') }}" class="btn-outline-teal" style="padding:10px 18px;text-decoration:none;"><i class="fa-solid fa-arrow-left me-1"></i> Back to list</a>
</div>
@endsection
