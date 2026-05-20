@extends('layouts.app')
@section('title', 'Group session details')
@section('page-title', 'Group session details')

@php
    $first = $memberRows[0]['detail'] ?? [];
@endphp

@section('content')
<div class="card-frc mb-4" style="border-radius:14px;overflow:hidden;">
    <div class="card-header-frc flex-wrap gap-2">
        <h6 class="card-title-frc mb-0"><i class="fa-solid fa-people-group me-2" style="color:var(--teal);"></i>Group session</h6>
        <span class="badge-status badge-draft" style="font-size:12px;">{{ count($memberRows) }} children</span>
    </div>
    <div class="p-3 p-md-4">
        <dl class="row mb-0 small">
            <dt class="col-sm-4 text-muted mb-2">Session date</dt>
            <dd class="col-sm-8 mb-2 fw-semibold" style="color:var(--navy);">{{ $first['session_date_label'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Day</dt>
            <dd class="col-sm-8 mb-2">{{ $first['day_label'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Time</dt>
            <dd class="col-sm-8 mb-2">{{ $first['time_slot'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Branch</dt>
            <dd class="col-sm-8 mb-2">{{ $first['branch_name'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Therapist</dt>
            <dd class="col-sm-8 mb-2">{{ $first['therapist_name'] ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted mb-2">Service</dt>
            <dd class="col-sm-8 mb-2">{{ $first['service_name'] ?? '—' }}</dd>
        </dl>


        <hr class="my-4" style="opacity:.15;">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--teal);letter-spacing:.04em;">Session lifecycle</h6>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted mb-2">Started</dt>
                    <dd class="col-sm-8 mb-2">
                        @if(! empty($first['started_at']))
                        {{ \Illuminate\Support\Carbon::parse($first['started_at'])->timezone(config('app.timezone'))->format('d M Y,
                        H:i') }}
                        @if(! empty($first['started_by_name']))
                        <span class="text-muted"> — {{ $first['started_by_name'] }}</span>
                        @endif
                        @else
                        —
                        @endif
                    </dd>
                    <dt class="col-sm-4 text-muted mb-2">Completed</dt>
                    <dd class="col-sm-8 mb-2">
                        @if(! empty($first['completed_at']))
                        {{ \Illuminate\Support\Carbon::parse($first['completed_at'])->timezone(config('app.timezone'))->format('d M Y,
                        H:i') }}
                        @if(! empty($first['completed_by_name']))
                        <span class="text-muted"> — {{ $first['completed_by_name'] }}</span>
                        @endif
                        @else
                        —
                        @endif
                    </dd>
                    <dt class="col-sm-4 text-muted mb-2">Completion note</dt>
                    <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;">{{ ! empty($first['completion_note']) ?
                        $first['completion_note'] : '—' }}</dd>
                    <dt class="col-sm-4 text-muted mb-2">Cancelled</dt>
                    <dd class="col-sm-8 mb-2">
                        @if(! empty($first['cancelled_at']))
                        {{ \Illuminate\Support\Carbon::parse($first['cancelled_at'])->timezone(config('app.timezone'))->format('d M Y,
                        H:i') }}
                        @if(! empty($first['cancelled_by_name']))
                        <span class="text-muted"> — {{ $first['cancelled_by_name'] }}</span>
                        @endif
                        @else
                        —
                        @endif
                    </dd>
                    <dt class="col-sm-4 text-muted mb-2">Cancellation reason</dt>
                    <dd class="col-sm-8 mb-2" style="white-space:pre-wrap;">{{ ! empty($first['cancellation_reason']) ?
                        $first['cancellation_reason'] : '—' }}</dd>
                </dl>
        

        <hr class="my-4" style="opacity:.15;">
        <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--teal);letter-spacing:.04em;">Children in this session</h6>
        <div class="table-responsive">
            <table class="table-frc mb-0">
                <thead>
                    <tr>
                        <th>Child</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($memberRows as $mr)
                        @php
                            $s = $mr['schedule'];
                            $d = $mr['detail'];
                            $cid = (int) ($d['child_id'] ?? 0);
                        @endphp
                        <tr>
                            <td class="fw-semibold" style="color:var(--navy);">
                                @if($cid > 0)
                                    <a href="{{ route('therapist.children.show', $cid) }}" style="color:var(--navy);text-decoration:underline;">{{ $d['child_name'] ?? '—' }}</a>
                                @else
                                    {{ $d['child_name'] ?? '—' }}
                                @endif
                            </td>
                            <td><span class="badge-status {{ $mr['status_badge'] }}" style="font-size:11px;">{{ $d['status_label'] ?? '—' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>



        <div class="mt-4 d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('therapist.sessions.index') }}" class="btn-outline-teal d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i> Back to sessions
            </a>
        </div>
    </div>
</div>

@if($canCancelGroup ?? false)
<div class="modal fade" id="frcGroupCancelModal" tabindex="-1" aria-labelledby="frcGroupCancelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border-soft);overflow:hidden;">
            <div class="modal-header" style="background:#fde8e8;border-bottom:1px solid var(--border-soft);">
                <h5 class="modal-title" id="frcGroupCancelLabel" style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--danger);font-size:17px;">Cancel group session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('therapist.sessions.group-cancel', $anchorSchedule) }}">
                @csrf
                <input type="hidden" name="session_date" value="{{ $sessionDate }}">
                <div class="modal-body">
                    <p class="small text-muted mb-2">This cancels the session for <strong>all {{ count($memberRows) }} children</strong> in this slot.</p>
                    <label class="form-label small fw-semibold" style="color:var(--navy);">Cancellation reason <span class="text-danger">*</span></label>
                    <textarea name="cancellation_reason" class="form-control" rows="4" required minlength="1" maxlength="5000" placeholder="Explain why this group session is being cancelled…"></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-soft);gap:8px;">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal" style="border-radius:10px;">Close</button>
                    <button type="submit" class="btn-teal" style="border-radius:10px;background:var(--danger);border-color:var(--danger);">Confirm cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
